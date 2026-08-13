<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Email Templates Manager
 * Handles creation, retrieval, and rendering of customizable email templates
 */

/**
 * Register email template settings
 */
function wps_register_email_template_settings() {
    // Lead form submission email template
    register_setting('wps_email', 'wps_lead_email_subject', array(
        'sanitize_callback' => 'sanitize_text_field',
        'default' => '[New Lead] {property_title} — from {name}',
    ));
    
    register_setting('wps_email', 'wps_lead_email_template', array(
        'sanitize_callback' => 'wp_kses_post',
        'default' => wps_get_default_lead_email_template(),
    ));
}
add_action('init', 'wps_register_email_template_settings');

/**
 * Get default lead email template HTML
 */
function wps_get_default_lead_email_template() {
    return <<<'HTML'
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; font-family: Arial, Helvetica, sans-serif; }
        table { border-collapse: collapse; }
        img { border: 0; outline: none; text-decoration: none; }
        @media only screen and (max-width: 600px) {
            .container { width: 100% !important; }
            .content-padding { padding: 20px !important; }
            .stat-box { width: 100% !important; display: block !important; margin-bottom: 10px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background: #f4f4f4;">
    <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f4f4f4; padding: 30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" class="container" width="600" border="0" cellspacing="0" cellpadding="0" style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 30px; text-align: center;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px; font-weight: bold;">🏠 New Lead Received!</h1>
                            <p style="margin: 10px 0 0 0; color: #ffffff; font-size: 16px; opacity: 0.9;">{site_name}</p>
                        </td>
                    </tr>
                    
                    <!-- Lead Details -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 22px;">📋 Lead Information</h2>
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f8f9fa; border-radius: 8px; padding: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 15px 0;"><strong>Name:</strong> {name}</p>
                                        <p style="margin: 0 0 15px 0;"><strong>Email:</strong> <a href="mailto:{email}">{email}</a></p>
                                        <p style="margin: 0 0 15px 0;"><strong>Phone:</strong> {phone}</p>
                                        <p style="margin: 0;"><strong>Message:</strong></p>
                                        <p style="margin: 10px 0; padding: 15px; background: #ffffff; border-left: 4px solid #667eea; border-radius: 4px;">{message}</p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Property Details -->
                    <tr>
                        <td style="padding: 0 30px 40px 30px;">
                            <h2 style="margin: 0 0 20px 0; color: #333333; font-size: 22px;">🏡 Property Details</h2>
                            <table role="presentation" width="100%" border="0" cellspacing="0" cellpadding="0" style="background: #f0f6ff; border-radius: 8px; border: 2px solid #667eea; padding: 20px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 10px 0;"><strong>Property:</strong> {property_title}</p>
                                        <p style="margin: 0;"><a href="{property_url}" style="color: #667eea; text-decoration: none; font-weight: bold;">View Property →</a></p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    
                    <!-- Meta Info -->
                    <tr>
                        <td style="padding: 0 30px 40px 30px;">
                            <p style="margin: 0; font-size: 13px; color: #666666;">
                                <strong>Lead ID:</strong> {lead_id} | <strong>Date:</strong> {date}
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background: #f8f9fa; padding: 30px; text-align: center; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0 0 10px 0; color: #666666;"><strong>{site_name}</strong></p>
                            <p style="margin: 0; font-size: 12px; color: #999999;">© {year} {site_name}. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
}

/**
 * Get all available template variables with descriptions
 */
function wps_get_email_template_variables() {
    return array(
        '{name}' => __('Visitor full name', 'wps'),
        '{email}' => __('Visitor email address', 'wps'),
        '{phone}' => __('Visitor phone number', 'wps'),
        '{message}' => __('Visitor message', 'wps'),
        '{property_title}' => __('Property listing title', 'wps'),
        '{property_id}' => __('Property ID number', 'wps'),
        '{property_url}' => __('Link to property page', 'wps'),
        '{lead_id}' => __('Unique lead ID', 'wps'),
        '{date}' => __('Submission date and time', 'wps'),
        '{site_name}' => __('Your website name', 'wps'),
        '{site_url}' => __('Your website URL', 'wps'),
        '{year}' => __('Current year', 'wps'),
    );
}

/**
 * Process and render email template with variables
 */
function wps_render_email_template($template, $variables = array()) {
    // Default variables
    $defaults = array(
        'name' => 'Guest',
        'email' => '',
        'phone' => '',
        'message' => '',
        'property_title' => 'Property',
        'property_id' => 0,
        'property_url' => get_site_url(),
        'lead_id' => 0,
        'date' => current_time('F j, Y \a\t g:i a'),
        'site_name' => get_bloginfo('name'),
        'site_url' => get_site_url(),
        'year' => date('Y'),
    );
    
    // Merge variables
    $variables = wp_parse_args($variables, $defaults);
    
    // Escape variables for HTML output
    $escaped_vars = array();
    foreach ($variables as $key => $value) {
        // Don't escape URLs and HTML content
        if (in_array($key, array('property_url', 'site_url'))) {
            $escaped_vars['{' . $key . '}'] = esc_url($value);
        } elseif ($key === 'message') {
            $escaped_vars['{' . $key . '}'] = wp_kses_post(nl2br($value));
        } else {
            $escaped_vars['{' . $key . '}'] = esc_html($value);
        }
    }
    
    // Replace variables in template
    return strtr($template, $escaped_vars);
}

/**
 * Get the lead email template (from settings or default)
 */
function wps_get_lead_email_template() {
    $template = get_option('wps_lead_email_template');
    if (empty($template)) {
        $template = wps_get_default_lead_email_template();
    }
    return $template;
}

/**
 * Get the lead email subject (from settings or default)
 */
function wps_get_lead_email_subject() {
    $subject = get_option('wps_lead_email_subject');
    if (empty($subject)) {
        $subject = '[New Lead] {property_title} — from {name}';
    }
    return $subject;
}

/**
 * Process subject line with variables
 */
function wps_process_email_subject($subject, $variables = array()) {
    $defaults = array(
        'name' => 'Guest',
        'property_title' => 'Property',
    );
    
    $variables = wp_parse_args($variables, $defaults);
    
    $escaped_vars = array();
    foreach ($variables as $key => $value) {
        $escaped_vars['{' . $key . '}'] = esc_html($value);
    }
    
    return strtr($subject, $escaped_vars);
}

/**
 * Reset email template to default
 */
function wps_reset_email_template() {
    update_option('wps_lead_email_template', wps_get_default_lead_email_template());
    update_option('wps_lead_email_subject', '[New Lead] {property_title} — from {name}');
}
