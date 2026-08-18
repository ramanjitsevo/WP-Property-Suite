<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API routes.
 */
function wps_register_routes() {

    // Get all properties.
    register_rest_route( 'wps/v1', '/properties', array(
        'methods'             => 'GET',
        'callback'            => 'wps_get_properties',
        'permission_callback' => '__return_true',
    ) );

    // Get single property.
    register_rest_route( 'wps/v1', '/properties/(?P<id>\d+)', array(
        'methods'             => 'GET',
        'callback'            => 'wps_get_property',
        'permission_callback' => '__return_true',
    ) );

    // Get taxonomy terms.
    register_rest_route( 'wps/v1', '/taxonomies', array(
        'methods'             => 'GET',
        'callback'            => 'wps_get_taxonomies',
        'permission_callback' => '__return_true',
    ) );

    // Submit lead form.
    register_rest_route( 'wps/v1', '/leads', array(
        'methods'             => 'POST',
        'callback'            => 'wps_submit_lead',
        'permission_callback' => 'wps_leads_permission_callback',
    ) );
}

/**
 * Permission callback for lead submissions.
 *
 * Lead submissions are intentionally public because visitors
 * do not need to log in to submit a property enquiry.
 *
 * Additional validation and anti-spam protection are handled
 * inside wps_submit_lead().
 *
 * @param WP_REST_Request $request REST API request.
 * @return bool
 */



/**
 * Permission callback for lead submissions.
 *
 * Lead submissions are available to visitors, but requests
 * must contain a valid WordPress REST nonce.
 *
 * @param WP_REST_Request $request REST API request.
 * @return true|WP_Error
 */
function wps_leads_permission_callback( WP_REST_Request $request ) {

    $nonce = $request->get_header( 'X-WP-Nonce' );

    if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
        return new WP_Error(
            'invalid_nonce',
            __( 'Security check failed. Please refresh the page and try again.', 'wps' ),
            array( 'status' => 403 )
        );
    }

    return true;
}

/**
 * Get all properties from WordPress.
 */
function wps_get_properties( $request ) {

    // Ensure the custom post type is registered.
    if ( ! post_type_exists( 'wps_property' ) ) {
        wps_register_post_type();
        wps_register_taxonomies();
    }

    $args = array(
        'post_type'      => 'wps_property',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    $posts      = get_posts( $args );
    $properties = array();

    foreach ( $posts as $post ) {
        $property_data = wps_build_property_data( $post );

        if ( ! empty( $property_data ) ) {
            $properties[] = $property_data;
        }
    }

    return rest_ensure_response( $properties );
}

/**
 * Get single property.
 */
function wps_get_property( $request ) {

    // Ensure the custom post type is registered.
    if ( ! post_type_exists( 'wps_property' ) ) {
        wps_register_post_type();
        wps_register_taxonomies();
    }

    $id   = absint( $request['id'] );
    $post = get_post( $id );

    if ( ! $post || 'wps_property' !== $post->post_type ) {
        return new WP_Error(
            'not_found',
            __( 'Property not found.', 'wps' ),
            array( 'status' => 404 )
        );
    }

    $property_data = wps_build_property_data( $post );

    if ( ! $property_data ) {
        wps_debug_log(
            '[WP Property Suite] Error getting property ' . $id
        );

        return new WP_Error(
            'processing_error',
            __( 'Error processing property.', 'wps' ),
            array( 'status' => 500 )
        );
    }

    return rest_ensure_response( $property_data );
}

/**
 * Get all taxonomy terms.
 */
function wps_get_taxonomies( $request ) {

    $taxonomies = array(
        'property_types' => get_terms(
            array(
                'taxonomy'   => 'property-type',
                'hide_empty' => true,
            )
        ),

        'locations' => get_terms(
            array(
                'taxonomy'   => 'property-location',
                'hide_empty' => true,
            )
        ),

        'bedrooms' => get_terms(
            array(
                'taxonomy'   => 'bedrooms',
                'hide_empty' => true,
            )
        ),

        'bathrooms' => get_terms(
            array(
                'taxonomy'   => 'bathrooms',
                'hide_empty' => true,
            )
        ),

        'floors' => get_terms(
            array(
                'taxonomy'   => 'property-floor',
                'hide_empty' => true,
            )
        ),
    );

    return rest_ensure_response( $taxonomies );
}

/**
 * Create property leads table.
 */
function wps_create_leads_table() {

    global $wpdb;

    $table_name    = $wpdb->prefix . 'property_leads';
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

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';

    dbDelta( $sql );
}

/**
 * Handle lead form submission via REST API.
 */
function wps_submit_lead( $request ) {

    $params = $request->get_json_params();

    if ( ! is_array( $params ) ) {
        return new WP_Error(
            'invalid_request',
            __( 'Invalid request data.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    /*
     * Sanitize input.
     */
    $name = sanitize_text_field(
        $params['name'] ?? ''
    );

    $email = sanitize_email(
        $params['email'] ?? ''
    );

    $phone = sanitize_text_field(
        $params['phone'] ?? ''
    );

    $message = sanitize_textarea_field(
        $params['message'] ?? ''
    );

    $property_id = absint(
        $params['propertyId'] ?? 0
    );

    $property_title = sanitize_text_field(
        $params['propertyTitle'] ?? ''
    );

    /*
     * Honeypot anti-spam field.
     *
     * Legitimate users should leave this field empty.
     */
    $honeypot = sanitize_text_field(
        $params['website'] ?? ''
    );

    if ( ! empty( $honeypot ) ) {
        return new WP_Error(
            'spam_detected',
            __( 'Spam submission detected.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    /*
     * Required fields.
     */
    if ( empty( $name ) || empty( $email ) ) {

        wps_debug_log(
            '[WP Property Suite Lead] ERROR: Missing required fields.'
        );

        return new WP_Error(
            'missing_fields',
            __( 'Name and email are required.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    /*
     * Validate email.
     */
    if ( ! is_email( $email ) ) {

        wps_debug_log(
            '[WP Property Suite Lead] ERROR: Invalid email address.'
        );

        return new WP_Error(
            'invalid_email',
            __( 'Please provide a valid email address.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    /*
     * Validate field lengths.
     */
    if ( strlen( $name ) > 255 ) {
        return new WP_Error(
            'invalid_name',
            __( 'Name is too long.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    if ( strlen( $phone ) > 50 ) {
        return new WP_Error(
            'invalid_phone',
            __( 'Phone number is too long.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    if ( strlen( $message ) > 5000 ) {
        return new WP_Error(
            'invalid_message',
            __( 'Message is too long.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    if ( strlen( $property_title ) > 255 ) {
        return new WP_Error(
            'invalid_property_title',
            __( 'Property title is too long.', 'wps' ),
            array( 'status' => 400 )
        );
    }

    /*
     * Validate property ID.
     */
    if ( $property_id > 0 ) {

        $property = get_post( $property_id );

        if ( ! $property || 'wps_property' !== $property->post_type ) {

            return new WP_Error(
                'invalid_property',
                __( 'Invalid property.', 'wps' ),
                array( 'status' => 400 )
            );
        }
    }

    /*
     * Save lead to database.
     */
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
            'created_at'     => current_time( 'mysql' ),
        ),
        array(
            '%d',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
            '%s',
        )
    );

    if ( false === $inserted ) {

        wps_debug_log(
            '[WP Property Suite Lead] ERROR: Database insert failed.'
        );

        return new WP_Error(
            'db_error',
            __( 'Could not save lead. Please try again.', 'wps' ),
            array( 'status' => 500 )
        );
    }

    $lead_id = $wpdb->insert_id;

    wps_debug_log(
        '[WP Property Suite Lead] SUCCESS: Lead #' . $lead_id . ' saved.'
    );

    /*
     * Send notification email.
     */
    $to          = wps_get_contact_email();
    $current_time = current_time( 'mysql' );

    $template        = wps_get_lead_email_template();
    $subject_template = wps_get_lead_email_subject();

    /*
     * Prepare variables for email template.
     */
    $template_vars = array(
        'name'           => $name,
        'email'          => $email,
        'phone'          => $phone ?: __( 'Not provided', 'wps' ),
        'message'        => $message,
        'property_title' => $property_title ?: __( 'Property', 'wps' ),
        'property_id'    => $property_id,
        'property_url'   => wps_get_property_frontend_url( $property_id ),
        'lead_id'        => $lead_id,
        'date'           => wp_date(
            'F j, Y \a\t g:i a',
            current_time( 'timestamp' )
        ),
    );

    /*
     * Render email template.
     */
    $html_body = wps_render_email_template(
        $template,
        $template_vars
    );

    /*
     * Process email subject.
     */
    $subject = wps_process_email_subject(
        $subject_template,
        array(
            'name'           => $name,
            'property_title' => $property_title ?: __( 'Property', 'wps' ),
        )
    );

    /*
     * Email headers.
     */
    $headers = array(
        'Content-Type: text/html; charset=UTF-8',
        'Reply-To: ' . $name . ' <' . $email . '>',
        'X-Mailer: WP Property Suite WordPress',
    );

    /*
     * Send email.
     */
    $mail_sent = wp_mail(
        $to,
        $subject,
        $html_body,
        $headers
    );

    if ( $mail_sent ) {

        wps_debug_log(
            '[WP Property Suite Lead] Email sent successfully for Lead #' . $lead_id . '.'
        );

    } else {

        wps_debug_log(
            '[WP Property Suite Lead] WARNING: wp_mail() returned false for Lead #' . $lead_id . '.'
        );
    }

    return rest_ensure_response(
        array(
            'success'    => true,
            'leadId'     => $lead_id,
            'emailSent'  => $mail_sent,
            'message'    => __(
                'Your enquiry has been submitted successfully. We will contact you shortly.',
                'wps'
            ),
        )
    );
}

/**
 * AJAX: Return thumbnail URLs for gallery preview in admin meta box.
 */
// Gallery AJAX handler moved to admin/admin.php