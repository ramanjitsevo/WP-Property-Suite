<?php
if (!defined('ABSPATH')) {
    exit;
}

function wps_register_routes() {
    register_rest_route('wps/v1', '/properties', array(
        'methods' => 'GET',
        'callback' => 'wps_get_properties',
        'permission_callback' => '__return_true',
    ));
    
    register_rest_route('wps/v1', '/properties/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'wps_get_property',
        'permission_callback' => '__return_true',
    ));
    
    // Get taxonomy terms
    register_rest_route('wps/v1', '/taxonomies', array(
        'methods' => 'GET',
        'callback' => 'wps_get_taxonomies',
        'permission_callback' => '__return_true',
    ));

    // Submit lead form
    register_rest_route('wps/v1', '/leads', array(
        'methods' => 'POST',
        'callback' => 'wps_submit_lead',
        'permission_callback' => '__return_true',
    ));
}

/**
 * Get all properties from WordPress
 */
function wps_get_properties($request) {
    // Ensure the custom post type is registered
    if (!post_type_exists('wps_property')) {
        wps_register_post_type();
        wps_register_taxonomies();
    }

    $args = array(
        'post_type' => 'wps_property',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
    );
    
    $posts = get_posts($args);
    $properties = array();

    foreach ($posts as $post) {
        $property_data = wps_build_property_data($post);
        if (!empty($property_data)) {
            $properties[] = $property_data;
        }
    }
    
    return rest_ensure_response($properties);
}

/**
 * Get single property
 */
function wps_get_property($request) {
    // Ensure the custom post type is registered
    if (!post_type_exists('wps_property')) {
        wps_register_post_type();
        wps_register_taxonomies();
    }

    $id = $request['id'];
    $post = get_post($id);
    
    if (!$post || $post->post_type !== 'wps_property') {
        return new WP_Error('not_found', 'Property not found', array('status' => 404));
    }
    
    $property_data = wps_build_property_data($post);
    if (!$property_data) {
        wps_debug_log('[WP Property Suite] Error getting property ' . $id);
        return new WP_Error('processing_error', 'Error processing property', array('status' => 500));
    }

    return rest_ensure_response($property_data);
}

/**
 * Get all taxonomy terms
 */
function wps_get_taxonomies($request) {
    $taxonomies = array(
        'property_types' => get_terms(array(
            'taxonomy' => 'property-type',
            'hide_empty' => true,
        )),
        'locations' => get_terms(array(
            'taxonomy' => 'property-location',
            'hide_empty' => true,
        )),
        'bedrooms' => get_terms(array(
            'taxonomy' => 'bedrooms',
            'hide_empty' => true,
        )),
        'bathrooms' => get_terms(array(
            'taxonomy' => 'bathrooms',
            'hide_empty' => true,
        )),
        'floors' => get_terms(array(
            'taxonomy' => 'property-floor',
            'hide_empty' => true,
        )),
    );
    
    return rest_ensure_response($taxonomies);
}

function wps_create_leads_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'property_leads';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        property_id bigint(20) DEFAULT NULL,
        property_title varchar(255) DEFAULT '',
        name varchar(255) NOT NULL,
        email varchar(255) NOT NULL,
        phone varchar(50) DEFAULT '',
        message text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY email (email),
        KEY property_id (property_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

/**
 * Handle lead form submission via REST API
 */
function wps_submit_lead($request) {
    $params = $request->get_json_params();

    // Validate required fields
    $name    = sanitize_text_field($params['name']    ?? '');
    $email   = sanitize_email($params['email']        ?? '');
    $phone   = sanitize_text_field($params['phone']   ?? '');
    $message = sanitize_textarea_field($params['message'] ?? '');
    $property_id    = intval($params['propertyId']    ?? 0);
    $property_title = sanitize_text_field($params['propertyTitle'] ?? '');

    if (empty($name) || empty($email)) {
        wps_debug_log('[WP Property Suite Lead] ERROR: Missing required fields - name or email');
        return new WP_Error('missing_fields', 'Name and email are required.', array('status' => 400));
    }

    if (!is_email($email)) {
        wps_debug_log('[WP Property Suite Lead] ERROR: Invalid email address: ' . $email);
        return new WP_Error('invalid_email', 'Please provide a valid email address.', array('status' => 400));
    }

    // Save lead to database
    global $wpdb;
    $table_name = $wpdb->prefix . 'property_leads';

    $inserted = $wpdb->insert(
        $table_name,
        array(
            'property_id'    => $property_id,
            'property_title' => $property_title,
            'name'           => $name,
            'email'          => $email,
            'phone'          => $phone,
            'message'        => $message,
            'created_at'     => current_time('mysql'),
        ),
        array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
    );

    if ($inserted === false) {
        wps_debug_log('[WP Property Suite Lead] ERROR: Database insert failed. DB error: ' . $wpdb->last_error);
        return new WP_Error('db_error', 'Could not save lead. Please try again.', array('status' => 500));
    }

    $lead_id = $wpdb->insert_id;
    wps_debug_log('[WP Property Suite Lead] SUCCESS: Lead #' . $lead_id . ' saved for ' . $email);

    // --- Send notification email with professional HTML template ---
    $to = wps_get_contact_email();
    $site_name = get_bloginfo('name');
    $site_url = get_bloginfo('url');
    $current_time = current_time('mysql');
    
    // Build HTML email template
    $html_body = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            /* Reset styles */
            body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
            table { border-collapse: collapse; }
            img { border: 0; outline: none; text-decoration: none; }
            
            /* Responsive */
            @media only screen and (max-width: 600px) {
                .container { width: 100% !important; }
                .content-padding { padding: 20px !important; }
                .stat-box { width: 100% !important; display: block !important; margin-bottom: 10px !important; }
            }
        </style>
    </head>
    <body style="margin: 0; padding: 0; background: #f4f4f4; font-family: Arial, Helvetica, sans-serif;">
        
        <!-- Outer wrapper -->
        <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f4f4f4; padding: 30px 0;">
            <tr>
                <td align="center">
                    
                    <!-- Main container -->
                    <table role="presentation" class="container" width="600" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                        
                        <!-- Header with gradient -->
                        <tr>
                            <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="text-align: center;">
                                            <div style="font-size: 48px; margin-bottom: 10px;">🏠</div>
                                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold; line-height: 1.3;">New Lead Received!</h1>
                                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9;">' . esc_html($site_name) . '</p>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Lead Details Section -->
                        <tr>
                            <td class="content-padding" style="padding: 40px 30px;">
                                <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 22px; font-weight: bold;">📋 Lead Information</h2>
                                
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f8f9fa; border-radius: 8px; padding: 20px;">
                                    <tr>
                                        <td style="padding: 15px 20px;">
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase; display: block; margin-bottom: 5px;">Full Name</strong>
                                                        <span style="color: #333333; font-size: 18px; font-weight: bold;">' . esc_html($name) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase; display: block; margin-bottom: 5px;">Email Address</strong>
                                                        <a href="mailto:' . esc_attr($email) . '" style="color: #667eea; font-size: 16px; text-decoration: none;">' . esc_html($email) . '</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px 0; border-bottom: 1px solid #e0e0e0;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase; display: block; margin-bottom: 5px;">Phone Number</strong>
                                                        <a href="tel:' . esc_attr($phone) . '" style="color: #333333; font-size: 16px; text-decoration: none;">' . esc_html($phone ?: "Not provided") . '</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px 0;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase; display: block; margin-bottom: 5px;">Message</strong>
                                                        <p style="color: #333333; font-size: 15px; line-height: 1.6; margin: 0; background: #ffffff; padding: 15px; border-radius: 6px; border-left: 4px solid #667eea;">' . nl2br(esc_html($message)) . '</p>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Property Details Section -->
                        <tr>
                            <td class="content-padding" style="padding: 0 30px 40px 30px;">
                                <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 22px; font-weight: bold;">🏡 Property Details</h2>
                                
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f0f6ff; border-radius: 8px; border: 2px solid #667eea;">
                                    <tr>
                                        <td style="padding: 20px;">
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                                <tr>
                                                    <td style="padding: 8px 0;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase;">Property Title</strong><br>
                                                        <span style="color: #333333; font-size: 17px; font-weight: bold;">' . esc_html($property_title ?: "N/A") . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0; border-top: 1px solid #d0e3ff;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase;">Property ID</strong><br>
                                                        <span style="color: #333333; font-size: 16px;">#' . intval($property_id) . '</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 8px 0; border-top: 1px solid #d0e3ff;">
                                                        <strong style="color: #666666; font-size: 13px; text-transform: uppercase;">View Property</strong><br>
                                                        <a href="' . esc_url(wps_get_property_frontend_url($property_id)) . '" style="color: #667eea; font-size: 15px; font-weight: bold; text-decoration: none;">Click here to view →</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Stats boxes -->
                        <tr>
                            <td style="padding: 0 30px 40px 30px;">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td class="stat-box" width="50%" style="padding: 0 10px 10px 0;">
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; padding: 20px;">
                                                <tr>
                                                    <td style="text-align: center; color: #ffffff;">
                                                        <div style="font-size: 32px; margin-bottom: 8px;">🆔</div>
                                                        <div style="font-size: 12px; text-transform: uppercase; opacity: 0.9; margin-bottom: 5px;">Lead ID</div>
                                                        <div style="font-size: 24px; font-weight: bold;">' . intval($lead_id) . '</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td class="stat-box" width="50%" style="padding: 0 0 10px 10px;">
                                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; padding: 20px;">
                                                <tr>
                                                    <td style="text-align: center; color: #ffffff;">
                                                        <div style="font-size: 32px; margin-bottom: 8px;">📅</div>
                                                        <div style="font-size: 12px; text-transform: uppercase; opacity: 0.9; margin-bottom: 5px;">Submitted</div>
                                                        <div style="font-size: 16px; font-weight: bold;">' . date('M d, Y', strtotime($current_time)) . '</div>
                                                        <div style="font-size: 13px; opacity: 0.9;">' . date('h:i A', strtotime($current_time)) . '</div>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Action buttons -->
                        <tr>
                            <td style="padding: 0 30px 40px 30px;">
                                <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0">
                                    <tr>
                                        <td style="text-align: center;">
                                            <a href="mailto:' . esc_attr($email) . '?subject=Re: ' . urlencode($property_title ?: 'Property Inquiry') . '" style="display: inline-block; background: #667eea; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: bold; font-size: 15px; margin: 5px;">📧 Reply to Lead</a>
                                            <a href="tel:' . esc_attr($phone) . '" style="display: inline-block; background: #28a745; color: #ffffff; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: bold; font-size: 15px; margin: 5px;">📞 Call Now</a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        
                        <!-- Footer -->
                        <tr>
                            <td style="background: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                                <p style="margin: 0 0 10px 0; color: #666666; font-size: 14px;"><strong>' . esc_html($site_name) . '</strong></p>
                                <p style="margin: 0 0 15px 0; color: #999999; font-size: 13px; line-height: 1.5;">This is an automated notification from your WP Property Suite.<br>Please respond to leads promptly for best results.</p>
                                <p style="margin: 0; font-size: 12px; color: #999999;">
                                    <a href="' . esc_url($site_url) . '" style="color: #667eea; text-decoration: none;">Visit Website</a> • 
                                    <a href="' . esc_url($site_url . '/wp-admin') . '" style="color: #667eea; text-decoration: none;">Admin Dashboard</a>
                                </p>
                                <p style="margin: 15px 0 0 0; font-size: 11px; color: #bbbbbb;">© ' . date('Y') . ' ' . esc_html($site_name) . '. All rights reserved.</p>
                            </td>
                        </tr>
                        
                    </table>
                    <!-- End main container -->
                    
                </td>
            </tr>
        </table>
        <!-- End outer wrapper -->
        
    </body>
    </html>';
    
    // Plain text fallback
    $plain_body  = "NEW LEAD RECEIVED!\n\n";
    $plain_body .= "--- Lead Information ---\n";
    $plain_body .= "Name:     $name\n";
    $plain_body .= "Email:    $email\n";
    $plain_body .= "Phone:    $phone\n";
    $plain_body .= "Message:  $message\n\n";
    $plain_body .= "--- Property Details ---\n";
    $plain_body .= "Property ID:    $property_id\n";
    $plain_body .= "Property Title: $property_title\n\n";
    $plain_body .= "--- Meta Information ---\n";
    $plain_body .= "Lead ID:      #$lead_id\n";
    $plain_body .= "Submitted at: $current_time\n";
    $plain_body .= "Website:      $site_name ($site_url)\n";

    $subject = '[New Lead] ' . ($property_title ?: 'Property Inquiry') . ' — from ' . $name;

    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
        'X-Mailer: WP Property Suite WordPress',
    );

    // Send with HTML, WordPress will auto-generate plain text fallback
    $mail_sent = wp_mail($to, $subject, $html_body, $headers);

    if ($mail_sent) {
        wps_debug_log('[WP Property Suite Lead] Email sent successfully to ' . $to . ' (Lead #' . $lead_id . ')');
    } else {
        wps_debug_log('[WP Property Suite Lead] WARNING: wp_mail() returned false for Lead #' . $lead_id . '. Check WP Mail SMTP settings.');
    }

    return rest_ensure_response(array(
        'success' => true,
        'leadId'  => $lead_id,
        'emailSent' => $mail_sent,
        'message' => 'Your enquiry has been submitted successfully. We will contact you shortly.',
    ));
}

/**
 * AJAX: Return thumbnail URLs for gallery preview in admin meta box
 */
// Gallery AJAX handler moved to admin/admin.php
