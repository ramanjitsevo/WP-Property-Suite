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

    // --- Send notification email with customizable template ---
    $to = wps_get_contact_email();
    $current_time = current_time('mysql');
    
    // Get customizable template and subject
    $template = wps_get_lead_email_template();
    $subject_template = wps_get_lead_email_subject();
    
    // Prepare variables for template
    $template_vars = array(
        'name' => $name,
        'email' => $email,
        'phone' => $phone ?: __('Not provided', 'wps'),
        'message' => $message,
        'property_title' => $property_title ?: __('Property', 'wps'),
        'property_id' => $property_id,
        'property_url' => wps_get_property_frontend_url($property_id),
        'lead_id' => $lead_id,
        'date' => date('F j, Y \a\t g:i a', strtotime($current_time)),
    );
    
    // Render template with variables
    $html_body = wps_render_email_template($template, $template_vars);
    
    // Process subject with variables
    $subject = wps_process_email_subject($subject_template, array(
        'name' => $name,
        'property_title' => $property_title ?: __('Property', 'wps'),
    ));
    
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
