<?php
/**
 * WP Property Suite - Admin Settings Page
 * Provides a user-friendly interface to customize all plugin settings
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add admin menu
 */
function wps_add_admin_menu() {
    add_menu_page(
        __('WP Property Suite Settings', 'evo-property-suite'),
        __('WP Property Suite Settings', 'evo-property-suite'),
        'manage_options',
        'wps-settings',
        'wps_settings_page',
        'dashicons-building',
        6
    );
    
    add_submenu_page(
        'wps-settings',
        __('Shortcode Guide', 'evo-property-suite'),
        __('Shortcode Guide', 'evo-property-suite'),
        'manage_options',
        'wps-guide',
        'wps_shortcode_guide_page'
    );
    // Leads submenu - view captured leads
    add_submenu_page(
        'wps-settings',
        __('Leads', 'evo-property-suite'),
        __('Leads', 'evo-property-suite'),
        'manage_options',
        'wps-leads',
        'wps_leads_page'
    );
}
add_action('admin_menu', 'wps_add_admin_menu');

/**
 * Register settings
 */
function wps_register_settings() {
    // General Settings
    register_setting('wps_general', 'wps_header_text', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_general', 'wps_properties_per_page', array('sanitize_callback' => 'absint'));
    register_setting('wps_general', 'wps_default_currency', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_general', 'wps_enable_compare', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_general', 'wps_enable_preloader', array('sanitize_callback' => 'sanitize_text_field'));
    
    // Banner Settings
    register_setting('wps_banner', 'wps_banner_image', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_banner', 'wps_banner_subtitle', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_banner', 'wps_banner_height', array('sanitize_callback' => 'absint'));
    register_setting('wps_banner', 'wps_banner_height_mobile', array('sanitize_callback' => 'absint'));
    register_setting('wps_banner', 'wps_banner_overlay', array('sanitize_callback' => 'absint'));
    register_setting('wps_banner', 'wps_banner_overlay_color', array('sanitize_callback' => 'sanitize_hex_color'));
    
    // Colors & Typography
    register_setting('wps_colors', 'wps_primary_color', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_colors', 'wps_secondary_color', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_colors', 'wps_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_colors', 'wps_background_color', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_colors', 'wps_card_background', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_colors', 'wps_font_family', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_colors', 'wps_font_size', array('sanitize_callback' => 'absint'));
    
    // Card Settings
    register_setting('wps_card', 'wps_show_badge', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_card', 'wps_show_area', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_card', 'wps_show_address', array('sanitize_callback' => 'sanitize_text_field'));
    
    // Contact & Lead Form
    register_setting('wps_contact', 'wps_contact_email', array('sanitize_callback' => 'sanitize_email'));
    register_setting('wps_contact', 'wps_contact_phone', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_contact', 'wps_enable_lead_form', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_contact', 'wps_lead_form_title', array('sanitize_callback' => 'sanitize_text_field'));
    
    // API Keys
    register_setting('wps_api', 'wps_google_api_key', array('sanitize_callback' => 'sanitize_text_field'));
    
    // Advanced Settings
    register_setting('wps_advanced', 'wps_custom_css', array('sanitize_callback' => 'wp_strip_all_tags'));

    // CTA Section Settings
    register_setting('wps_sections', 'wps_cta_image', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_sections', 'wps_cta_title', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_sections', 'wps_cta_description', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_sections', 'wps_cta_button_text', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_sections', 'wps_cta_button_url', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_sections', 'wps_cta_bg_color', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_sections', 'wps_cta_text_color', array('sanitize_callback' => 'sanitize_hex_color'));

    // Features Section Settings
    register_setting('wps_sections', 'wps_features_bg_color', array('sanitize_callback' => 'sanitize_hex_color'));
    register_setting('wps_sections', 'wps_features_text_color', array('sanitize_callback' => 'sanitize_hex_color'));
    for ($i = 1; $i <= 4; $i++) {
        register_setting('wps_sections', "wps_feature_{$i}_icon", array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wps_sections', "wps_feature_{$i}_title", array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('wps_sections', "wps_feature_{$i}_description", array('sanitize_callback' => 'sanitize_text_field'));
    }

    // Single Property Page Settings
    register_setting('wps_single', 'wps_agent_name', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_single', 'wps_agent_photo', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_single', 'wps_agent_role', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_single', 'wps_agent_phone', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_single', 'wps_agent_email', array('sanitize_callback' => 'sanitize_email'));
    register_setting('wps_single', 'wps_contact_form_heading', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_single', 'wps_contact_form_subtitle', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_single', 'wps_featured_label', array('sanitize_callback' => 'sanitize_text_field'));
    register_setting('wps_single', 'wps_schedule_tour_url', array('sanitize_callback' => 'esc_url_raw'));

    // Social links
    register_setting('wps_social', 'wps_social_facebook', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_social', 'wps_social_twitter', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_social', 'wps_social_linkedin', array('sanitize_callback' => 'esc_url_raw'));
    register_setting('wps_social', 'wps_social_instagram', array('sanitize_callback' => 'esc_url_raw'));
}
add_action('admin_init', 'wps_register_settings');

/**
 * AJAX handler to import default demo data on demand from admin UI
 */
function wps_import_defaults_ajax() {
    check_ajax_referer('wps_import_defaults', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(
            array('message' => __('Insufficient permissions.', 'evo-property-suite')),
            403
        );
    }

    if (function_exists('wps_install_default_data')) {
        // Force import when called via admin AJAX
        wps_install_default_data(true);
        wp_send_json_success(array('message' => 'imported'));
    }

    wp_send_json_error(
        array('message' => __('Could not import default data.', 'evo-property-suite')),
        500
    );
}
add_action('wp_ajax_wps_import_defaults', 'wps_import_defaults_ajax');

/**
 * Settings page HTML
 */
function wps_settings_page() {
    ?>
    <div class="wrap wps-settings">
        <div class="wps-header">
            <h1><?php _e('WP Property Suite Settings', 'evo-property-suite'); ?></h1>
            <p class="description"><?php _e('Manage all plugin settings from one place. Changes will appear on the frontend after refreshing the page.', 'evo-property-suite'); ?></p>
                <button type="button" class="button button-primary" id="save-all-settings"><?php _e('Save All Changes', 'evo-property-suite'); ?></button>
                <button type="button" class="button button-secondary" id="import-sample-data" style="margin-left:10px;">
                    <?php _e('Import Sample Data', 'evo-property-suite'); ?>
                </button>
        </div>

        <div class="wps-settings-container">
            <!-- Navigation Tabs -->
            <div class="wps-tabs">
                <button class="tab-button active" data-tab="general">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('General', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="banner">
                    <span class="dashicons dashicons-format-image"></span>
                    <?php _e('Banner & Header', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="colors">
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php _e('Colors & Typography', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="card">
                    <span class="dashicons dashicons-layout"></span>
                    <?php _e('Property Card', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="taxonomies">
                    <span class="dashicons dashicons-category"></span>
                    <?php _e('Custom Taxonomies', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="single">
                    <span class="dashicons dashicons-admin-page"></span>
                    <?php _e('Single Property Page', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="contact">
                    <span class="dashicons dashicons-email"></span>
                    <?php _e('Contact & Lead Form', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="api">
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php _e('API Keys', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="advanced">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Advanced', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="sections">
                    <span class="dashicons dashicons-exerpt-view"></span>
                    <?php _e('Homepage Sections', 'evo-property-suite'); ?>
                </button>
                <button class="tab-button" data-tab="email-templates">
                    <span class="dashicons dashicons-email"></span>
                    <?php _e('Email Templates', 'evo-property-suite'); ?>
                </button>
            </div>

            <!-- Settings Content -->
            <div class="wps-settings-content">
                
                <!-- General Settings Tab -->
                <div class="tab-content active" id="general">
                    <div class="settings-section">
                        <h2><?php _e('General Settings', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Configure basic plugin settings', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="header_text"><?php _e('Header Text', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="header_text" name="wps_header_text" 
                                           value="<?php echo esc_attr(get_option('wps_header_text', 'Find Your Dream Property')); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Main heading text for the property listings page', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="properties_per_page"><?php _e('Properties Per Page', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="number" id="properties_per_page" name="wps_properties_per_page" 
                                           value="<?php echo esc_attr(get_option('wps_properties_per_page', '12')); ?>" 
                                           class="small-text" min="1" max="100" />
                                    <p class="description"><?php _e('Number of properties to display per page', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="default_currency"><?php _e('Default Currency', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <select id="default_currency" name="wps_default_currency">
                                        <option value="USD" <?php selected(get_option('wps_default_currency', 'USD'), 'USD'); ?>><?php _e('USD ($) - US Dollar', 'evo-property-suite'); ?></option>
                                        <option value="EUR" <?php selected(get_option('wps_default_currency', 'USD'), 'EUR'); ?>><?php _e('EUR (€) - Euro', 'evo-property-suite'); ?></option>
                                        <option value="GBP" <?php selected(get_option('wps_default_currency', 'USD'), 'GBP'); ?>><?php _e('GBP (£) - British Pound', 'evo-property-suite'); ?></option>
                                        <option value="INR" <?php selected(get_option('wps_default_currency', 'USD'), 'INR'); ?>><?php _e('INR (₹) - Indian Rupee', 'evo-property-suite'); ?></option>
                                        <option value="PKR" <?php selected(get_option('wps_default_currency', 'USD'), 'PKR'); ?>><?php _e('PKR (Rs) - Pakistani Rupee', 'evo-property-suite'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row"><?php _e('Features', 'evo-property-suite'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="wps_enable_compare" 
                                                   <?php checked(get_option('wps_enable_compare', '1'), '1'); ?> />
                                            <?php _e('Enable Property Compare', 'evo-property-suite'); ?>
                                        </label>
                                        <p class="description"><?php _e('Allow users to compare multiple properties', 'evo-property-suite'); ?></p>
                                    </fieldset>
                                    
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="wps_enable_preloader" 
                                                   <?php checked(get_option('wps_enable_preloader', '1'), '1'); ?> />
                                            <?php _e('Enable Preloader', 'evo-property-suite'); ?>
                                        </label>
                                        <p class="description"><?php _e('Show loading animation while properties load', 'evo-property-suite'); ?></p>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                        <h3 style="margin-top:20px;"><?php _e('Social Media Links', 'evo-property-suite'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="social_facebook"><?php _e('Facebook URL', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="url" id="social_facebook" name="wps_social_facebook"
                                           value="<?php echo esc_attr(get_option('wps_social_facebook', '')); ?>" class="large-text" placeholder="https://facebook.com/yourpage" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="social_twitter"><?php _e('Twitter URL', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="url" id="social_twitter" name="wps_social_twitter"
                                           value="<?php echo esc_attr(get_option('wps_social_twitter', '')); ?>" class="large-text" placeholder="https://twitter.com/yourhandle" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="social_linkedin"><?php _e('LinkedIn URL', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="url" id="social_linkedin" name="wps_social_linkedin"
                                           value="<?php echo esc_attr(get_option('wps_social_linkedin', '')); ?>" class="large-text" placeholder="https://linkedin.com/company/yourcompany" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="social_instagram"><?php _e('Instagram URL', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="url" id="social_instagram" name="wps_social_instagram"
                                           value="<?php echo esc_attr(get_option('wps_social_instagram', '')); ?>" class="large-text" placeholder="https://instagram.com/yourhandle" />
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Banner & Header Tab -->
                <div class="tab-content" id="banner">
                    <div class="settings-section">
                        <h2><?php _e('Banner & Header Settings', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Customize the banner image and header appearance', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="banner_image"><?php _e('Banner Image', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <div class="image-upload-container">
                                        <img id="banner_image_preview" src="<?php echo esc_url(get_option('wps_banner_image', '')); ?>" 
                                             style="<?php echo get_option('wps_banner_image') ? '' : 'display:none;'; ?>max-width: 300px; height: auto; margin-bottom: 10px;" />
                                        <br/>
                                        <input type="hidden" id="banner_image" name="wps_banner_image" 
                                               value="<?php echo esc_attr(get_option('wps_banner_image', '')); ?>" />
                                        <button type="button" class="button" id="upload_banner_image"><?php _e('Upload Image', 'evo-property-suite'); ?></button>
                                        <button type="button" class="button" id="remove_banner_image" <?php echo get_option('wps_banner_image') ? '' : 'style="display:none;"'; ?>><?php _e('Remove Image', 'evo-property-suite'); ?></button>
                                        <p class="description"><?php _e('Upload a banner image for the property listings page. Recommended size: 1920x600px', 'evo-property-suite'); ?></p>
                                    </div>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="banner_subtitle"><?php _e('Banner Subtitle', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="banner_subtitle" name="wps_banner_subtitle" 
                                           value="<?php echo esc_attr(get_option('wps_banner_subtitle', 'Discover the perfect home for your family')); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Subtitle text displayed below the main heading', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="banner_height"><?php _e('Banner Height for Desktop', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="banner_height" name="wps_banner_height" 
                                           value="<?php echo esc_attr(get_option('wps_banner_height', '320')); ?>" 
                                           min="200" max="800" class="range-slider" />
                                    <span id="banner_height_value"><?php echo esc_attr(get_option('wps_banner_height', '320')); ?>px</span>
                                    <p class="description"><?php _e('Desktop banner height. Default: 320px.', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <label for="banner_height_mobile"><?php _e('Banner Height for Mobile', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="banner_height_mobile" name="wps_banner_height_mobile"
                                           value="<?php echo esc_attr(get_option('wps_banner_height_mobile', '250')); ?>"
                                           min="160" max="500" class="range-slider" />
                                    <span id="banner_height_mobile_value"><?php echo esc_attr(get_option('wps_banner_height_mobile', '250')); ?>px</span>
                                    <p class="description"><?php _e('Mobile banner height. Default: 250px.', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="banner_overlay"><?php _e('Banner Overlay', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="banner_overlay" name="wps_banner_overlay" 
                                           value="<?php echo esc_attr(get_option('wps_banner_overlay', '50')); ?>" 
                                           min="0" max="100" class="range-slider" />
                                    <span id="banner_overlay_value"><?php echo esc_attr(get_option('wps_banner_overlay', '50')); ?>%</span>
                                    <p class="description"><?php _e('Dark overlay opacity on banner image (0-100%)', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="banner_overlay_color"><?php _e('Overlay Color', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="banner_overlay_color" name="wps_banner_overlay_color" 
                                           value="<?php echo esc_attr(get_option('wps_banner_overlay_color', '#000000')); ?>" 
                                           class="color-picker" />
                                    <p class="description"><?php _e('Choose overlay color for better text readability', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Colors & Typography Tab -->
                <div class="tab-content" id="colors">
                    <div class="settings-section">
                        <h2><?php _e('Colors & Typography', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Customize colors and fonts for your property listings', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="primary_color"><?php _e('Primary Color', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="primary_color" name="wps_primary_color" 
                                           value="<?php echo esc_attr(get_option('wps_primary_color', '#2563eb')); ?>" 
                                           class="color-picker" />
                                    <p class="description"><?php _e('Main brand color used for buttons, links, and accents', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="secondary_color"><?php _e('Secondary Color', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="secondary_color" name="wps_secondary_color" 
                                           value="<?php echo esc_attr(get_option('wps_secondary_color', '#10b981')); ?>" 
                                           class="color-picker" />
                                    <p class="description"><?php _e('Secondary color for badges and highlights', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="text_color"><?php _e('Text Color', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="text_color" name="wps_text_color" 
                                           value="<?php echo esc_attr(get_option('wps_text_color', '#1f2937')); ?>" 
                                           class="color-picker" />
                                    <p class="description"><?php _e('Default text color for content', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="background_color"><?php _e('Background Color', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="background_color" name="wps_background_color" 
                                           value="<?php echo esc_attr(get_option('wps_background_color', '#f3f4f6')); ?>" 
                                           class="color-picker" />
                                    <p class="description"><?php _e('Page background color', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="card_background"><?php _e('Card Background Color', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="color" id="card_background" name="wps_card_background" 
                                           value="<?php echo esc_attr(get_option('wps_card_background', '#ffffff')); ?>" 
                                           class="color-picker" />
                                    <p class="description"><?php _e('Background color for property cards', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="font_family"><?php _e('Font Family', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <select id="font_family" name="wps_font_family">
                                        <option value="Arial, sans-serif" <?php selected(get_option('wps_font_family', 'Arial, sans-serif'), 'Arial, sans-serif'); ?>>Arial</option>
                                        <option value="'Helvetica Neue', sans-serif" <?php selected(get_option('wps_font_family', 'Arial, sans-serif'), "'Helvetica Neue', sans-serif"); ?>>Helvetica</option>
                                        <option value="Georgia, serif" <?php selected(get_option('wps_font_family', 'Arial, sans-serif'), 'Georgia, serif'); ?>>Georgia</option>
                                        <option value="'Times New Roman', serif" <?php selected(get_option('wps_font_family', 'Arial, sans-serif'), "'Times New Roman', serif"); ?>>Times New Roman</option>
                                        <option value="'Courier New', monospace" <?php selected(get_option('wps_font_family', 'Arial, sans-serif'), "'Courier New', monospace"); ?>>Courier New</option>
                                        <option value="Verdana, sans-serif" <?php selected(get_option('wps_font_family', 'Arial, sans-serif'), 'Verdana, sans-serif'); ?>>Verdana</option>
                                    </select>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="font_size"><?php _e('Base Font Size', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="range" id="font_size" name="wps_font_size" 
                                           value="<?php echo esc_attr(get_option('wps_font_size', '16')); ?>" 
                                           min="12" max="24" class="range-slider" />
                                    <span id="font_size_value"><?php echo esc_attr(get_option('wps_font_size', '16')); ?>px</span>
                                    <p class="description"><?php _e('Base font size for all text', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Property Card Tab -->
                <div class="tab-content" id="card">
                    <div class="settings-section">
                        <h2><?php _e('Property Card Settings', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Configure how property cards are displayed', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Display Options', 'evo-property-suite'); ?></th>
                                <td>
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="wps_show_badge" 
                                                   <?php checked(get_option('wps_show_badge', '1'), '1'); ?> />
                                            <?php _e('Show Status Badge (For Sale, For Rent, etc.)', 'evo-property-suite'); ?>
                                        </label>
                                    </fieldset>
                                    
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="wps_show_area" 
                                                   <?php checked(get_option('wps_show_area', '1'), '1'); ?> />
                                            <?php _e('Show Property Area', 'evo-property-suite'); ?>
                                        </label>
                                    </fieldset>
                                    
                                    <fieldset>
                                        <label>
                                            <input type="checkbox" name="wps_show_address" 
                                                   <?php checked(get_option('wps_show_address', '1'), '1'); ?> />
                                            <?php _e('Show Full Address', 'evo-property-suite'); ?>
                                        </label>
                                    </fieldset>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Custom Taxonomies Tab -->
                <div class="tab-content" id="taxonomies">
                    <div class="settings-section">
                        <h2><?php _e('Custom Taxonomies Manager', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Create custom taxonomies for your properties (e.g., Floor, Year Built, Parking, etc.)', 'evo-property-suite'); ?></p>
                        
                        <div class="taxonomy-creator">
                            <h3><?php _e('Create New Taxonomy', 'evo-property-suite'); ?></h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="new_taxonomy_name"><?php _e('Taxonomy Name', 'evo-property-suite'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="new_taxonomy_name" name="new_taxonomy_name" 
                                               class="regular-text" placeholder="e.g., Floors" />
                                        <p class="description"><?php _e('Display name for the taxonomy (e.g., Floors, Year Built, Parking)', 'evo-property-suite'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row">
                                        <label for="new_taxonomy_slug"><?php _e('Taxonomy Slug', 'evo-property-suite'); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="new_taxonomy_slug" name="new_taxonomy_slug" 
                                               class="regular-text" placeholder="e.g., property-floor" />
                                        <p class="description"><?php _e('Unique slug (lowercase, hyphens allowed). Example: property-floor, year-built, amenities', 'evo-property-suite'); ?></p>
                                    </td>
                                </tr>
                                
                                <tr>
                                    <th scope="row"><?php _e('Actions', 'evo-property-suite'); ?></th>
                                    <td>
                                        <button type="button" class="button button-primary" id="create-taxonomy"><?php _e('Create Taxonomy', 'evo-property-suite'); ?></button>
                                        <span class="taxonomy-status" id="taxonomy-status"></span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="taxonomy-list">
                            <h3><?php _e('Existing Custom Taxonomies', 'evo-property-suite'); ?></h3>
                            <div id="custom-taxonomies-list">
                                <?php
                                $custom_taxonomies = get_option('wps_custom_taxonomies', array());
                                if (!empty($custom_taxonomies)) {
                                    echo '<table class="wp-list-table widefat fixed striped">';
                                    echo '<thead><tr><th>' . __('Name', 'evo-property-suite') . '</th><th>' . __('Slug', 'evo-property-suite') . '</th><th>' . __('Actions', 'evo-property-suite') . '</th></tr></thead>';
                                    echo '<tbody>';
                                    foreach ($custom_taxonomies as $tax) {
                                        echo '<tr>';
                                        echo '<td>' . esc_html($tax['name']) . '</td>';
                                        echo '<td><code>' . esc_html($tax['slug']) . '</code></td>';
                                        echo '<td><button type="button" class="button delete-taxonomy" data-slug="' . esc_attr($tax['slug']) . '">' . __('Delete', 'evo-property-suite') . '</button></td>';
                                        echo '</tr>';
                                    }
                                    echo '</tbody></table>';
                                } else {
                                    echo '<p class="description">' . __('No custom taxonomies created yet.', 'evo-property-suite') . '</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Single Property Page Tab -->
                <div class="tab-content" id="single">
                    <div class="settings-section">
                        <h2><?php _e('Single Property Page', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Customize the agent card, contact form, and labels shown on the single property detail page.', 'evo-property-suite'); ?></p>

                        <h3><?php _e('Agent Card', 'evo-property-suite'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="agent_name"><?php _e('Agent Name', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="agent_name" name="wps_agent_name"
                                           value="<?php echo esc_attr(get_option('wps_agent_name', 'John Smith')); ?>"
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="agent_photo"><?php _e('Agent Photo', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <div class="image-upload-container" style="text-align:left;">
                                        <img id="agent_photo_preview"
                                             src="<?php echo esc_url(get_option('wps_agent_photo', '')); ?>"
                                             style="<?php echo get_option('wps_agent_photo') ? '' : 'display:none;'; ?>width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:10px;" />
                                        <br/>
                                        <input type="hidden" id="agent_photo" name="wps_agent_photo"
                                               value="<?php echo esc_attr(get_option('wps_agent_photo', '')); ?>" />
                                        <button type="button" class="button wps-upload-img" data-target="agent_photo"><?php _e('Upload Photo', 'evo-property-suite'); ?></button>
                                        <button type="button" class="button wps-remove-img" data-target="agent_photo"
                                                <?php echo get_option('wps_agent_photo') ? '' : 'style="display:none;"'; ?>><?php _e('Remove', 'evo-property-suite'); ?></button>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="agent_role"><?php _e('Agent Role / Title', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="agent_role" name="wps_agent_role"
                                           value="<?php echo esc_attr(get_option('wps_agent_role', 'Property Agent')); ?>"
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="agent_phone"><?php _e('Agent Phone', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="agent_phone" name="wps_agent_phone"
                                           value="<?php echo esc_attr(get_option('wps_agent_phone', '+1 (555) 123-4567')); ?>"
                                           class="regular-text" placeholder="+1 (555) 123-4567" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="agent_email"><?php _e('Agent Email', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="email" id="agent_email" name="wps_agent_email"
                                           value="<?php echo esc_attr(get_option('wps_agent_email', '')); ?>"
                                           class="regular-text" placeholder="agent@example.com" />
                                </td>
                            </tr>
                        </table>

                        <h3 style="margin-top:30px;"><?php _e('Contact Form', 'evo-property-suite'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="contact_form_heading"><?php _e('Form Heading', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="contact_form_heading" name="wps_contact_form_heading"
                                           value="<?php echo esc_attr(get_option('wps_contact_form_heading', 'Get More Details')); ?>"
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="contact_form_subtitle"><?php _e('Form Subtitle', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="contact_form_subtitle" name="wps_contact_form_subtitle"
                                           value="<?php echo esc_attr(get_option('wps_contact_form_subtitle', 'Schedule a tour or request more information about this property.')); ?>"
                                           class="large-text" />
                                </td>
                            </tr>
                        </table>

                        <h3 style="margin-top:30px;"><?php _e('Labels & Links', 'evo-property-suite'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="featured_label"><?php _e('Featured Label Text', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="featured_label" name="wps_featured_label"
                                           value="<?php echo esc_attr(get_option('wps_featured_label', 'FEATURED PROPERTY')); ?>"
                                           class="regular-text" />
                                    <p class="description"><?php _e('Small label shown above the property title in the sidebar', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="schedule_tour_url"><?php _e('Schedule Tour URL', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="url" id="schedule_tour_url" name="wps_schedule_tour_url"
                                           value="<?php echo esc_attr(get_option('wps_schedule_tour_url', '')); ?>"
                                           class="large-text" placeholder="https://calendly.com/your-link (leave empty to scroll to contact form)" />
                                    <p class="description"><?php _e('External booking link (e.g. Calendly). Leave empty to scroll to the on-page contact form instead.', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Contact & Lead Form Tab -->
                <div class="tab-content" id="contact">
                    <div class="settings-section">
                        <h2><?php _e('Contact & Lead Form', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Configure contact information and lead capture form', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="contact_email"><?php _e('Contact Email', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="email" id="contact_email" name="wps_contact_email" 
                                           value="<?php echo esc_attr(function_exists('wps_get_contact_email') ? wps_get_contact_email() : get_option('wps_contact_email', get_option('admin_email'))); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Email address for contact inquiries', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="contact_phone"><?php _e('Contact Phone', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="tel" id="contact_phone" name="wps_contact_phone" 
                                           value="<?php echo esc_attr(get_option('wps_contact_phone', '')); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Phone number for contact inquiries', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="enable_lead_form"><?php _e('Enable Lead Form', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="checkbox" name="wps_enable_lead_form" 
                                           <?php checked(get_option('wps_enable_lead_form', '1'), '1'); ?> />
                                    <p class="description"><?php _e('Show lead capture form on property details page', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="lead_form_title"><?php _e('Lead Form Title', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="lead_form_title" name="wps_lead_form_title" 
                                           value="<?php echo esc_attr(get_option('wps_lead_form_title', 'Interested in this property?')); ?>" 
                                           class="regular-text" />
                                    <p class="description"><?php _e('Title text for the lead capture form', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- API Keys Tab -->
                <div class="tab-content" id="api">
                    <div class="settings-section">
                        <h2><?php _e('API Keys Configuration', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Configure third-party API keys for enhanced functionality', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="google_api_key"><?php _e('Google Maps API Key', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="google_api_key" name="wps_google_api_key" 
                                           value="<?php echo esc_attr(get_option('wps_google_api_key', '')); ?>" 
                                           class="regular-text code" placeholder="AIzaSy..." />
                                    <p class="description">
                                        <?php _e('Enter your Google Places API key for address autocomplete.', 'evo-property-suite'); ?><br/>
                                        <a href="https://developers.google.com/maps/documentation/javascript/get-api-key" target="_blank"><?php _e('Get API Key →', 'evo-property-suite'); ?></a>
                                    </p>
                                    <div class="api-setup-instructions" style="margin-top: 15px; padding: 15px; background: #fff; border-left: 4px solid #2271b1;">
                                        <h4 style="margin-top: 0;"><?php _e('Setup Instructions:', 'evo-property-suite'); ?></h4>
                                        <ol>
                                            <li><?php _e('Go to Google Cloud Console', 'evo-property-suite'); ?></li>
                                            <li><?php _e('Create a project or select existing', 'evo-property-suite'); ?></li>
                                            <li><?php _e('Enable "Places API" and "Maps JavaScript API"', 'evo-property-suite'); ?></li>
                                            <li><?php _e('Create API credentials', 'evo-property-suite'); ?></li>
                                            <li><?php _e('Paste the API key here', 'evo-property-suite'); ?></li>
                                        </ol>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Advanced Tab -->
                <div class="tab-content" id="advanced">
                    <div class="settings-section">
                        <h2><?php _e('Advanced Settings', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Custom CSS configuration', 'evo-property-suite'); ?></p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="custom_css"><?php _e('Custom CSS', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <textarea id="custom_css" name="wps_custom_css" 
                                              rows="10" class="large-text code"><?php echo esc_textarea(get_option('wps_custom_css', '')); ?></textarea>
                                    <p class="description"><?php _e('Add custom CSS to override plugin styles. Use with caution.', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Homepage Sections Tab -->
                <div class="tab-content" id="sections">

                    <!-- CTA Section -->
                    <div class="settings-section">
                        <h2><?php _e('CTA Section — "Sell or Rent Your Property"', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Customize the call-to-action banner shown at the bottom of the property listings page.', 'evo-property-suite'); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="cta_image"><?php _e('CTA Image', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <div class="image-upload-container">
                                        <img id="cta_image_preview"
                                             src="<?php echo esc_url(get_option('wps_cta_image', '')); ?>"
                                             style="<?php echo get_option('wps_cta_image') ? '' : 'display:none;'; ?>max-width:300px; height:auto; margin-bottom:10px;" />
                                        <br/>
                                        <input type="hidden" id="cta_image" name="wps_cta_image"
                                               value="<?php echo esc_attr(get_option('wps_cta_image', '')); ?>" />
                                        <button type="button" class="button wps-upload-img" data-target="cta_image"><?php _e('Upload Image', 'evo-property-suite'); ?></button>
                                        <button type="button" class="button wps-remove-img" data-target="cta_image"
                                                <?php echo get_option('wps_cta_image') ? '' : 'style="display:none;"'; ?>><?php _e('Remove', 'evo-property-suite'); ?></button>
                                        <p class="description"><?php _e('Recommended size: 600×500 px', 'evo-property-suite'); ?></p>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cta_title"><?php _e('Heading', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="cta_title" name="wps_cta_title"
                                           value="<?php echo esc_attr(get_option('wps_cta_title', 'Want to Sell or Rent Your Property?')); ?>"
                                           class="large-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cta_description"><?php _e('Description', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <textarea id="cta_description" name="wps_cta_description" rows="3"
                                              class="large-text"><?php echo esc_textarea(get_option('wps_cta_description', 'List your property with us and reach thousands of potential buyers and renters.')); ?></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cta_button_text"><?php _e('Button Text', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="text" id="cta_button_text" name="wps_cta_button_text"
                                           value="<?php echo esc_attr(get_option('wps_cta_button_text', 'Add Property Now')); ?>"
                                           class="regular-text" />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cta_button_url"><?php _e('Button URL', 'evo-property-suite'); ?></label></th>
                                <td>
                                    <input type="url" id="cta_button_url" name="wps_cta_button_url"
                                           value="<?php echo esc_attr(get_option('wps_cta_button_url', '/wp-admin/post-new.php?post_type=wps_property')); ?>"
                                           class="large-text" placeholder="https://..." />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cta_bg_color"><?php _e('Background Color', 'evo-property-suite'); ?></label></th>
                                <td><input type="color" id="cta_bg_color" name="wps_cta_bg_color"
                                           value="<?php echo esc_attr(get_option('wps_cta_bg_color', '#f0f9ff')); ?>" class="color-picker" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="cta_text_color"><?php _e('Text Color', 'evo-property-suite'); ?></label></th>
                                <td><input type="color" id="cta_text_color" name="wps_cta_text_color"
                                           value="<?php echo esc_attr(get_option('wps_cta_text_color', '#1e3a5f')); ?>" class="color-picker" /></td>
                            </tr>
                        </table>
                    </div>

                    <!-- Features / Trust Section -->
                    <div class="settings-section" style="margin-top: 40px;">
                        <h2><?php _e('Features Section — "Trusted by Thousands"', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Customize the 4-column feature strip shown at the very bottom of the page.', 'evo-property-suite'); ?></p>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="features_bg_color"><?php _e('Section Background', 'evo-property-suite'); ?></label></th>
                                <td><input type="color" id="features_bg_color" name="wps_features_bg_color"
                                           value="<?php echo esc_attr(get_option('wps_features_bg_color', '#ffffff')); ?>" class="color-picker" /></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="features_text_color"><?php _e('Text Color', 'evo-property-suite'); ?></label></th>
                                <td><input type="color" id="features_text_color" name="wps_features_text_color"
                                           value="<?php echo esc_attr(get_option('wps_features_text_color', '#1f2937')); ?>" class="color-picker" /></td>
                            </tr>
                        </table>

                        <?php
                        $feature_defaults = array(
                            1 => array('icon' => 'fas fa-trophy',       'title' => 'Trusted by Thousands',      'desc' => 'Join thousands of happy clients who found their perfect property.'),
                            2 => array('icon' => 'fas fa-chart-bar',     'title' => 'Wide Range of Properties',  'desc' => 'Explore a wide range of properties for sale and rent.'),
                            3 => array('icon' => 'fas fa-users',         'title' => 'Expert Agents',             'desc' => 'Work with experienced agents to find the best property.'),
                            4 => array('icon' => 'fas fa-shield-alt',    'title' => 'Secure & Easy Process',     'desc' => 'Enjoy a secure and hassle-free property buying or renting process.'),
                        );
                        for ($i = 1; $i <= 4; $i++):
                            $d = $feature_defaults[$i]; ?>
                            <h3 style="margin-top:25px; border-top:1px solid #ddd; padding-top:20px;">
                                <?php printf(__('Feature Item %d', 'evo-property-suite'), $i); ?>
                            </h3>
                            <table class="form-table">
                                <tr>
                                    <th scope="row"><label for="feature_<?php echo $i; ?>_icon"><?php _e('Icon (Font Awesome class)', 'evo-property-suite'); ?></label></th>
                                    <td>
                                        <input type="text" id="feature_<?php echo $i; ?>_icon"
                                               name="wps_feature_<?php echo $i; ?>_icon"
                                               value="<?php echo esc_attr(get_option("wps_feature_{$i}_icon", $d['icon'])); ?>"
                                               class="regular-text" placeholder="fas fa-star" />
                                        <p class="description">
                                            <?php _e('Find icons at', 'evo-property-suite'); ?>
                                            <a href="https://fontawesome.com/icons" target="_blank">fontawesome.com/icons</a>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="feature_<?php echo $i; ?>_title"><?php _e('Title', 'evo-property-suite'); ?></label></th>
                                    <td>
                                        <input type="text" id="feature_<?php echo $i; ?>_title"
                                               name="wps_feature_<?php echo $i; ?>_title"
                                               value="<?php echo esc_attr(get_option("wps_feature_{$i}_title", $d['title'])); ?>"
                                               class="regular-text" />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><label for="feature_<?php echo $i; ?>_description"><?php _e('Description', 'evo-property-suite'); ?></label></th>
                                    <td>
                                        <textarea id="feature_<?php echo $i; ?>_description"
                                                  name="wps_feature_<?php echo $i; ?>_description"
                                                  rows="2" class="large-text"><?php echo esc_textarea(get_option("wps_feature_{$i}_description", $d['desc'])); ?></textarea>
                                    </td>
                                </tr>
                            </table>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Email Templates Tab -->
                <div class="tab-content" id="email-templates">
                    <div class="settings-section">
                        <h2><?php _e('Email Template — Lead Notifications', 'evo-property-suite'); ?></h2>
                        <p class="section-description"><?php _e('Customize the HTML email template sent to admins when a new lead is captured. Available variables are listed below.', 'evo-property-suite'); ?></p>
                        
                        <!-- Available Variables -->
                        <div style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
                            <h3 style="margin-top: 0; color: #1d2327;"><?php _e('Available Variables', 'evo-property-suite'); ?></h3>
                            <p style="margin: 10px 0; font-size: 13px; color: #3c434a;">
                                <?php _e('Use these variables in your email template. They will be replaced with actual values:', 'evo-property-suite'); ?>
                            </p>
                            <table style="width: 100%; border-collapse: collapse; font-size: 13px;">
                                <?php foreach (wps_get_email_template_variables() as $var => $desc): ?>
                                <tr style="border-bottom: 1px solid #d0dce6;">
                                    <td style="padding: 8px; width: 150px;"><code style="background: #fff; padding: 2px 6px; border-radius: 3px; color: #d63638;"><?php echo esc_html($var); ?></code></td>
                                    <td style="padding: 8px; color: #555;"><?php echo esc_html($desc); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="lead_email_subject"><?php _e('Email Subject', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <input type="text" id="lead_email_subject" name="wps_lead_email_subject" 
                                           value="<?php echo esc_attr(wps_get_lead_email_subject()); ?>" 
                                           class="large-text" />
                                    <p class="description"><?php _e('Use variables like {name}, {property_title}, etc. in the subject line.', 'evo-property-suite'); ?></p>
                                </td>
                            </tr>
                            
                            <tr>
                                <th scope="row">
                                    <label for="lead_email_template"><?php _e('Email HTML Template', 'evo-property-suite'); ?></label>
                                </th>
                                <td>
                                    <?php 
                                    wp_editor(
                                        wps_get_lead_email_template(), 
                                        'wps_lead_email_template',
                                        array(
                                            'media_buttons' => false,
                                            'teeny' => false,
                                            'textarea_rows' => 20,
                                            'editor_class' => 'large-text code',
                                            'editor_height' => 400,
                                        )
                                    );
                                    ?>
                                    <p class="description">
                                        <?php _e('Edit the HTML template for lead notification emails. You can customize styling, layout, and content.', 'evo-property-suite'); ?>
                                    </p>
                                    <button type="button" class="button" id="reset-email-template" style="margin-top: 10px;">
                                        <?php _e('Reset to Default Template', 'evo-property-suite'); ?>
                                    </button>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

            </div>
        </div>

        <div class="save-settings-footer">
            <button type="button" class="button button-primary button-large" id="save-all-settings-bottom"><?php _e('Save All Changes', 'evo-property-suite'); ?></button>
            <span class="save-status" id="save-status"></span>
        </div>
    </div>
    <script>
    jQuery(function($){
        $('#import-sample-data').on('click', function(e){
            e.preventDefault();
            if (!confirm('<?php echo esc_js(__('This will import sample properties and settings. Continue?', 'evo-property-suite')); ?>')) return;
            var btn = $(this).prop('disabled', true).text('<?php echo esc_js(__('Importing...', 'evo-property-suite')); ?>');
            $.post(ajaxurl, {
                action: 'wps_import_defaults',
                nonce: '<?php echo wp_create_nonce('wps_import_defaults'); ?>'
            }).done(function(resp){
                if (resp && resp.success) {
                    alert('<?php echo esc_js(__('Sample data imported successfully. Refresh the properties list or visit the homepage to view them.', 'evo-property-suite')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Import failed. Check the debug log for details.', 'evo-property-suite')); ?>');
                    btn.prop('disabled', false).text('<?php echo esc_js(__('Import Sample Data', 'evo-property-suite')); ?>');
                }
            }).fail(function(){
                alert('<?php echo esc_js(__('AJAX request failed. Check your connection and try again.', 'evo-property-suite')); ?>');
                btn.prop('disabled', false).text('<?php echo esc_js(__('Import Sample Data', 'evo-property-suite')); ?>');
            });
        });
    });
    </script>

    <style>
        .wps-settings {
            max-width: 1200px;
            margin: 20px auto;
        }
        
        .wps-header {
            background: #fff;
            padding: 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .wps-header h1 {
            margin: 0;
            flex: 1;
        }
        
        .wps-settings-container {
            display: flex;
            gap: 20px;
        }
        
        .wps-tabs {
            width: 220px;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 10px 0;
            flex-shrink: 0;
        }
        
        .tab-button {
            width: 100%;
            padding: 12px 15px;
            border: none;
            background: none;
            text-align: left;
            cursor: pointer;
            transition: all 0.2s;
            border-left: 3px solid transparent;
        }
        
        .tab-button:hover {
            background: #f0f0f1;
        }
        
        .tab-button.active {
            background: #f0f6fc;
            border-left-color: #2271b1;
            color: #2271b1;
            font-weight: 600;
        }
        
        .tab-button .dashicons {
            margin-right: 8px;
            vertical-align: middle;
        }
        
        .wps-settings-content {
            flex: 1;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .settings-section {
            margin-bottom: 30px;
        }
        
        .settings-section h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #2271b1;
        }
        
        .section-description {
            color: #646970;
            margin-top: -5px;
        }
        
        .form-table th {
            width: 200px;
            padding: 15px 10px 15px 0;
            vertical-align: top;
        }
        
        .form-table td {
            padding: 15px 10px;
        }
        
        .form-table tr {
            border-bottom: 1px solid #f0f0f1;
        }
        
        fieldset {
            margin-bottom: 15px;
        }
        
        fieldset:last-child {
            margin-bottom: 0;
        }
        
        .range-slider {
            width: 300px;
            margin-right: 10px;
        }
        
        .color-picker {
            width: 100px;
            height: 40px;
            padding: 0;
            border: 1px solid #8c8f94;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .image-upload-container {
            text-align: center;
        }
        
        .image-upload-container img {
            border: 2px dashed #ccd0d4;
            padding: 5px;
            border-radius: 4px;
        }
        
        .save-settings-footer {
            background: #fff;
            padding: 15px 20px;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            margin-top: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .save-status {
            color: #00a32a;
            font-weight: 600;
            padding: 8px 12px;
            background: #f0f6fc;
            border-radius: 4px;
            display: inline-block;
        }
        
        .taxonomy-creator {
            background: #f0f6fc;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 30px;
            border-left: 4px solid #2271b1;
        }
        
        .taxonomy-creator h3 {
            margin-top: 0;
            color: #2271b1;
        }
        
        .taxonomy-list h3 {
            margin-bottom: 15px;
        }
        
        .taxonomy-status {
            margin-left: 10px;
            font-weight: 600;
        }
        
        .delete-taxonomy {
            color: #d63638;
            border-color: #d63638;
        }
        
        .delete-taxonomy:hover {
            background: #d63638;
            color: #fff;
        }
        
        @media (max-width: 782px) {
            .wps-settings-container {
                flex-direction: column;
            }
            
            .wps-tabs {
                width: 100%;
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
                padding: 10px;
            }
            
            .tab-button {
                flex: 1;
                min-width: 120px;
                border-left: none;
                border-bottom: 3px solid transparent;
            }
            
            .tab-button.active {
                border-left: none;
                border-bottom-color: #2271b1;
            }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Initialize color picker
        $('.color-picker').wpColorPicker();
        
        // Tab switching
        $('.tab-button').on('click', function() {
            var tab = $(this).data('tab');
            
            // Update active tab
            $('.tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Show corresponding content
            $('.tab-content').removeClass('active');
            $('#' + tab).addClass('active');
        });
        
        // Range slider value display
        $('.range-slider').on('input', function() {
            var id = $(this).attr('id');
            var value = $(this).val();
            var suffix = id === 'banner_overlay' || id === 'overlay' ? '%' : 'px';
            $('#' + id + '_value').text(value + suffix);
        });
        
        // Image uploader
        var mediaUploader;
        $('#upload_banner_image').on('click', function(e) {
            e.preventDefault();
            
            if (mediaUploader) {
                mediaUploader.open();
                return;
            }
            
            mediaUploader = wp.media({
                title: 'Choose Banner Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false
            });
            
            mediaUploader.on('select', function() {
                var attachment = mediaUploader.state().get('selection').first().toJSON();
                $('#banner_image').val(attachment.url);
                $('#banner_image_preview').attr('src', attachment.url).show();
                $('#remove_banner_image').show();
            });
            
            mediaUploader.open();
        });
        
        $('#remove_banner_image').on('click', function() {
            $('#banner_image').val('');
            $('#banner_image_preview').attr('src', '').hide();
            $(this).hide();
        });

        // Generic image uploader for .wps-upload-img / .wps-remove-img buttons
        $(document).on('click', '.wps-upload-img', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var target = $btn.data('target'); // e.g. 'cta_image'

            var frame = wp.media({
                title: 'Select Image',
                button: { text: 'Use this image' },
                multiple: false,
                library: { type: 'image' }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#' + target).val(attachment.url);
                $('#' + target + '_preview').attr('src', attachment.url).show();
                $btn.siblings('.wps-remove-img').show();
            });

            frame.open();
        });

        $(document).on('click', '.wps-remove-img', function(e) {
            e.preventDefault();
            var target = $(this).data('target');
            $('#' + target).val('');
            $('#' + target + '_preview').attr('src', '').hide();
            $(this).hide();
        });
        
        // Save all settings
        function saveSettings() {
            var data = {
                action: 'wps_save_all_settings',
                nonce: '<?php echo wp_create_nonce('wps_nonce'); ?>',
                settings: {}
            };
            
            // Collect all settings from form fields
            $('.wps-settings').find('input, select, textarea').each(function() {
                var name = $(this).attr('name');
                if (name) {
                    if ($(this).attr('type') === 'checkbox') {
                        data.settings[name] = $(this).is(':checked') ? '1' : '0';
                    } else if ($(this).attr('type') === 'color') {
                        // Color picker values
                        data.settings[name] = $(this).val() || '';
                    } else {
                        data.settings[name] = $(this).val();
                    }
                }
            });
            
            $('#save-status').text('Saving...').css('color', '#646970');
            
            // Disable save buttons during save
            $('#save-all-settings, #save-all-settings-bottom').prop('disabled', true);
            
            $.post(ajaxurl, data, function(response) {
                if (response.success) {
                    $('#save-status').text('✓ Settings saved successfully! Refresh page to see changes on frontend.').css('color', '#00a32a');
                    
                    // Re-enable buttons
                    $('#save-all-settings, #save-all-settings-bottom').prop('disabled', false);
                    
                    setTimeout(function() {
                        $('#save-status').text('');
                    }, 5000);
                } else {
                    $('#save-status').text('✗ Error: ' + (response.data || 'Unknown error')).css('color', '#d63638');
                    $('#save-all-settings, #save-all-settings-bottom').prop('disabled', false);
                }
            }).fail(function() {
                $('#save-status').text('✗ Error saving settings. Please try again.').css('color', '#d63638');
                $('#save-all-settings, #save-all-settings-bottom').prop('disabled', false);
            });
        }
        
        $('#save-all-settings, #save-all-settings-bottom').on('click', saveSettings);
        
        // Reset email template
        $('#reset-email-template').on('click', function(e) {
            e.preventDefault();
            if (!confirm('<?php echo esc_js(__('Are you sure you want to reset the email template to default? Your current template will be replaced.', 'evo-property-suite')); ?>')) {
                return;
            }
            
            $.post(ajaxurl, {
                action: 'wps_reset_email_template',
                nonce: '<?php echo wp_create_nonce('wps_nonce'); ?>'
            }, function(response) {
                if (response.success) {
                    alert('<?php echo esc_js(__('Email template reset to default successfully. Please refresh the page.', 'evo-property-suite')); ?>');
                    location.reload();
                } else {
                    alert('<?php echo esc_js(__('Error resetting template: ', 'evo-property-suite')); ?>' + response.data);
                }
            }).fail(function() {
                alert('<?php echo esc_js(__('AJAX request failed. Please try again.', 'evo-property-suite')); ?>');
            });
        });
        
        // Keyboard shortcut: Ctrl+S to save
        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveSettings();
            }
        });
        
        // Auto-generate slug from name
        $('#new_taxonomy_name').on('input', function() {
            var name = $(this).val();
            var slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            if (slug && !$('#new_taxonomy_slug').val()) {
                $('#new_taxonomy_slug').val('property-' + slug);
            }
        });
        
        // Create taxonomy
        $('#create-taxonomy').on('click', function() {
            var name = $('#new_taxonomy_name').val().trim();
            var slug = $('#new_taxonomy_slug').val().trim();
            
            if (!name || !slug) {
                $('#taxonomy-status').text('✗ Please fill in both name and slug').css('color', '#d63638');
                return;
            }
            
            // Validate slug format
            if (!/^[a-z0-9-]+$/.test(slug)) {
                $('#taxonomy-status').text('✗ Slug can only contain lowercase letters, numbers, and hyphens').css('color', '#d63638');
                return;
            }
            
            $('#taxonomy-status').text('Creating...').css('color', '#646970');
            
            $.post(ajaxurl, {
                action: 'wps_create_taxonomy',
                nonce: '<?php echo wp_create_nonce('wps_nonce'); ?>',
                name: name,
                slug: slug
            }, function(response) {
                if (response.success) {
                    $('#taxonomy-status').text('✓ ' + response.data).css('color', '#00a32a');
                    $('#new_taxonomy_name').val('');
                    $('#new_taxonomy_slug').val('');
                    
                    // Reload page after 1.5 seconds to show new taxonomy
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    $('#taxonomy-status').text('✗ ' + response.data).css('color', '#d63638');
                }
            }).fail(function() {
                $('#taxonomy-status').text('✗ Error creating taxonomy').css('color', '#d63638');
            });
        });
        
        // Delete taxonomy
        $(document).on('click', '.delete-taxonomy', function() {
            if (!confirm('Are you sure you want to delete this taxonomy? All associated terms will be removed.')) {
                return;
            }
            
            var slug = $(this).data('slug');
            var $button = $(this);
            
            $.post(ajaxurl, {
                action: 'wps_delete_taxonomy',
                nonce: '<?php echo wp_create_nonce('wps_nonce'); ?>',
                slug: slug
            }, function(response) {
                if (response.success) {
                    $button.closest('tr').fadeOut();
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + response.data);
                }
            }).fail(function() {
                alert('Error deleting taxonomy');
            });
        });
    });
    </script>
    <?php
}

/**
 * Enqueue admin scripts and styles for settings page
 */
function wps_admin_enqueue_scripts($hook) {
    // Only load on our settings pages
    if (!in_array($hook, array('toplevel_page_wps-settings', 'wps-settings_page_wps-guide'))) {
        return;
    }
    
    // Enqueue WordPress media uploader
    wp_enqueue_media();
    wps_enqueue_fontawesome();
    
    // Enqueue color picker
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('wp-color-picker');
    
    // Enqueue clipboard.js for copy shortcode button
    wp_enqueue_script('clipboard');
}
add_action('admin_enqueue_scripts', 'wps_admin_enqueue_scripts');

/**
 * AJAX handler to save all settings
 */
function wps_save_all_settings_ajax() {
    check_ajax_referer('wps_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $settings = isset($_POST['settings']) && is_array($_POST['settings']) ? wp_unslash($_POST['settings']) : array();
    
    // Define which settings should use which sanitization
    $sanitize_functions = array(
        'wps_header_text' => 'sanitize_text_field',
        'wps_properties_per_page' => 'absint',
        'wps_default_currency' => 'sanitize_text_field',
        'wps_enable_compare' => 'sanitize_text_field',
        'wps_enable_preloader' => 'sanitize_text_field',
        'wps_banner_image' => 'esc_url_raw',
        'wps_banner_subtitle' => 'sanitize_text_field',
        'wps_banner_height' => 'absint',
        'wps_banner_height_mobile' => 'absint',
        'wps_banner_overlay' => 'absint',
        'wps_banner_overlay_color' => 'sanitize_hex_color',
        'wps_primary_color' => 'sanitize_hex_color',
        'wps_secondary_color' => 'sanitize_hex_color',
        'wps_text_color' => 'sanitize_hex_color',
        'wps_background_color' => 'sanitize_hex_color',
        'wps_card_background' => 'sanitize_hex_color',
        'wps_font_family' => 'sanitize_text_field',
        'wps_font_size' => 'absint',
        'wps_card_layout' => 'sanitize_text_field',
        'wps_show_badge' => 'sanitize_text_field',
        'wps_show_area' => 'sanitize_text_field',
        'wps_show_address' => 'sanitize_text_field',
        'wps_contact_email' => 'sanitize_email',
        'wps_contact_phone' => 'sanitize_text_field',
        'wps_enable_lead_form' => 'sanitize_text_field',
        'wps_lead_form_title' => 'sanitize_text_field',
        'wps_google_api_key' => 'sanitize_text_field',
        'wps_custom_css' => 'wp_strip_all_tags',
        // CTA section
        'wps_cta_image' => 'esc_url_raw',
        'wps_cta_title' => 'sanitize_text_field',
        'wps_cta_description' => 'sanitize_text_field',
        'wps_cta_button_text' => 'sanitize_text_field',
        'wps_cta_button_url' => 'esc_url_raw',
        'wps_cta_bg_color' => 'sanitize_hex_color',
        'wps_cta_text_color' => 'sanitize_hex_color',
        // Features section
        'wps_features_bg_color' => 'sanitize_hex_color',
        'wps_features_text_color' => 'sanitize_hex_color',
        'wps_feature_1_icon' => 'sanitize_text_field',
        'wps_feature_1_title' => 'sanitize_text_field',
        'wps_feature_1_description' => 'sanitize_text_field',
        'wps_feature_2_icon' => 'sanitize_text_field',
        'wps_feature_2_title' => 'sanitize_text_field',
        'wps_feature_2_description' => 'sanitize_text_field',
        'wps_feature_3_icon' => 'sanitize_text_field',
        'wps_feature_3_title' => 'sanitize_text_field',
        'wps_feature_3_description' => 'sanitize_text_field',
        'wps_feature_4_icon' => 'sanitize_text_field',
        'wps_feature_4_title' => 'sanitize_text_field',
        'wps_feature_4_description' => 'sanitize_text_field',
        // Single property page
        'wps_agent_name' => 'sanitize_text_field',
        'wps_agent_photo' => 'esc_url_raw',
        'wps_agent_role' => 'sanitize_text_field',
        'wps_agent_phone' => 'sanitize_text_field',
        'wps_agent_email' => 'sanitize_email',
        'wps_contact_form_heading' => 'sanitize_text_field',
        'wps_contact_form_subtitle' => 'sanitize_text_field',
        'wps_featured_label' => 'sanitize_text_field',
        'wps_schedule_tour_url' => 'esc_url_raw',
        'wps_social_facebook' => 'esc_url_raw',
        'wps_social_twitter' => 'esc_url_raw',
        'wps_social_linkedin' => 'esc_url_raw',
        'wps_social_instagram' => 'esc_url_raw',
    );
    
    foreach ($settings as $key => $value) {
        $key = sanitize_key($key);
        if (array_key_exists($key, $sanitize_functions)) {
            $sanitized_value = call_user_func($sanitize_functions[$key], $value);
            update_option($key, $sanitized_value);
        }
    }
    
    wp_send_json_success('Settings saved');
}
add_action('wp_ajax_wps_save_all_settings', 'wps_save_all_settings_ajax');

/**
 * AJAX handler to create custom taxonomy
 */
function wps_create_taxonomy_ajax() {
    check_ajax_referer('wps_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
    $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';
    
    if (empty($name) || empty($slug)) {
        wp_send_json_error('Name and slug are required');
    }
    
    // Validate slug format
    if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        wp_send_json_error('Invalid slug format. Use only lowercase letters, numbers, and hyphens');
    }
    
    // Check if taxonomy already exists
    if (taxonomy_exists($slug)) {
        wp_send_json_error('Taxonomy "' . $slug . '" already exists');
    }
    
    // Get existing custom taxonomies
    $custom_taxonomies = get_option('wps_custom_taxonomies', array());
    
    // Check if slug is already in our list
    foreach ($custom_taxonomies as $tax) {
        if ($tax['slug'] === $slug) {
            wp_send_json_error('Taxonomy slug "' . $slug . '" is already registered');
        }
    }
    
    // Add to custom taxonomies list
    $custom_taxonomies[] = array(
        'name' => $name,
        'slug' => $slug,
    );
    
    update_option('wps_custom_taxonomies', $custom_taxonomies);
    
    // Register the taxonomy immediately
    wps_register_custom_taxonomy($slug, $name);
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    wp_send_json_success('Taxonomy "' . $name . '" created successfully!');
}
add_action('wp_ajax_wps_create_taxonomy', 'wps_create_taxonomy_ajax');

/**
 * AJAX handler to delete custom taxonomy
 */
function wps_delete_taxonomy_ajax() {
    check_ajax_referer('wps_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $slug = isset($_POST['slug']) ? sanitize_key($_POST['slug']) : '';
    
    if (empty($slug)) {
        wp_send_json_error('Taxonomy slug is required');
    }
    
    // Get existing custom taxonomies
    $custom_taxonomies = get_option('wps_custom_taxonomies', array());
    
    // Remove the taxonomy
    $found = false;
    foreach ($custom_taxonomies as $key => $tax) {
        if ($tax['slug'] === $slug) {
            $name = $tax['name'];
            unset($custom_taxonomies[$key]);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        wp_send_json_error('Taxonomy not found');
    }
    
    // Update option
    update_option('wps_custom_taxonomies', array_values($custom_taxonomies));
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    wp_send_json_success('Taxonomy deleted successfully');
}
add_action('wp_ajax_wps_delete_taxonomy', 'wps_delete_taxonomy_ajax');

/**
 * AJAX handler to reset email template to default
 */
function wps_reset_email_template_ajax() {
    check_ajax_referer('wps_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    wps_reset_email_template();
    wp_send_json_success('Email template reset to default');
}
add_action('wp_ajax_wps_reset_email_template', 'wps_reset_email_template_ajax');

/**
 * Leads admin page callback — display captured leads
 */
function wps_leads_page() {
    global $wpdb;
    $table = $wpdb->prefix . 'property_leads';

    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have permission to view leads.', 'evo-property-suite'));
    }

    if (
        isset($_GET['action'], $_GET['lead_id'], $_GET['_wpnonce'])
        && $_GET['action'] === 'delete'
    ) {
        $lead_id = absint($_GET['lead_id']);
        
        // Validate lead_id
        if ( ! $lead_id || $lead_id <= 0 ) {
            wp_safe_redirect( add_query_arg( 'page', 'wps-leads', admin_url( 'admin.php' ) ) );
            exit;
        }
        
        $nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
        if ( ! wp_verify_nonce( $nonce, 'wps_delete_lead_' . $lead_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'evo-property-suite' ) );
        }
        
        $deleted = $wpdb->delete($table, array('id' => $lead_id), array('%d'));
        $lead_notice = $deleted ? 'deleted' : 'not_deleted';

        wp_safe_redirect(add_query_arg(
            array(
                'page' => 'wps-leads',
                's' => isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '',
                'order' => isset($_GET['order']) ? sanitize_text_field(wp_unslash($_GET['order'])) : 'desc',
                'paged' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
                'lead_notice' => $lead_notice,
            ),
            admin_url('admin.php')
        ));
        exit;
    }

    $notice = '';
    if (isset($_GET['lead_notice'])) {
        $lead_notice = sanitize_text_field(wp_unslash($_GET['lead_notice']));
        if ($lead_notice === 'deleted') {
            $notice = __('Lead deleted successfully.', 'evo-property-suite');
        } elseif ($lead_notice === 'not_deleted') {
            $notice = __('Lead could not be deleted or was already removed.', 'evo-property-suite');
        } elseif ($lead_notice === 'invalid') {
            $notice = __('Invalid delete request.', 'evo-property-suite');
        }
    }

    $search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
    $order = isset($_GET['order']) && strtolower(sanitize_text_field(wp_unslash($_GET['order']))) === 'asc' ? 'ASC' : 'DESC';
    $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
    $per_page = 20;
    $offset = ($paged - 1) * $per_page;

    $where = 'WHERE 1=1';
    $where_params = array();
    if ($search !== '') {
        $like = '%' . $wpdb->esc_like($search) . '%';
        $where .= ' AND (property_title LIKE %s OR name LIKE %s OR email LIKE %s OR phone LIKE %s OR message LIKE %s OR CAST(property_id AS CHAR) LIKE %s)';
        $where_params = array($like, $like, $like, $like, $like, $like);
    }

    $count_sql = "SELECT COUNT(*) FROM $table $where";
    $total_items = !empty($where_params)
        ? (int) $wpdb->get_var($wpdb->prepare($count_sql, $where_params))
        : (int) $wpdb->get_var($count_sql);

    $query_params = array_merge($where_params, array($per_page, $offset));
    $leads_sql = "SELECT * FROM $table $where ORDER BY created_at $order, id $order LIMIT %d OFFSET %d";
    $leads = $wpdb->get_results($wpdb->prepare($leads_sql, $query_params));
    $total_pages = max(1, (int) ceil($total_items / $per_page));
    $date_sort_url = add_query_arg(
        array(
            'page' => 'wps-leads',
            's' => $search,
            'order' => $order === 'ASC' ? 'desc' : 'asc',
            'paged' => 1,
        ),
        admin_url('admin.php')
    );
    ?>
    <div class="wrap wps-leads">
        <h1><?php _e('Captured Leads', 'evo-property-suite'); ?></h1>
        <p class="description"><?php _e('Leads captured from the property detail pages are listed below.', 'evo-property-suite'); ?></p>

        <?php if ($notice): ?>
            <div class="notice notice-info is-dismissible"><p><?php echo esc_html($notice); ?></p></div>
        <?php endif; ?>

        <style>
            .wps-leads .column-id,
            .wps-leads .column-property-id {
                width: 72px;
            }
            .wps-leads .column-actions {
                width: 92px;
            }
            .wps-leads .column-message {
                width: 28%;
                white-space: normal;
                word-break: break-word;
            }
            .wps-leads .lead-controls {
                align-items: center;
                display: flex;
                gap: 10px;
                justify-content: space-between;
                margin: 16px 0;
            }
            .wps-leads .lead-search {
                display: flex;
                gap: 8px;
            }
            .wps-leads .tablenav-pages {
                margin: 12px 0;
                text-align: right;
            }
        </style>

        <div class="lead-controls">
            <form class="lead-search" method="get" action="<?php echo esc_url(admin_url('admin.php')); ?>">
                <input type="hidden" name="page" value="wps-leads" />
                <input type="hidden" name="order" value="<?php echo esc_attr(strtolower($order)); ?>" />
                <label class="screen-reader-text" for="property-lead-search"><?php _e('Search leads', 'evo-property-suite'); ?></label>
                <input type="search" id="property-lead-search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php esc_attr_e('Search leads...', 'evo-property-suite'); ?>" />
                <button type="submit" class="button"><?php _e('Search', 'evo-property-suite'); ?></button>
                <?php if ($search !== ''): ?>
                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=wps-leads')); ?>"><?php _e('Clear', 'evo-property-suite'); ?></a>
                <?php endif; ?>
            </form>
            <span>
                <?php
                printf(
                    esc_html(_n('%s lead', '%s leads', $total_items, 'evo-property-suite')),
                    esc_html(number_format_i18n($total_items))
                );
                ?>
            </span>
        </div>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th class="column-id"><?php _e('ID', 'evo-property-suite'); ?></th>
                    <th class="column-property-id"><?php _e('Property ID', 'evo-property-suite'); ?></th>
                    <th><?php _e('Property', 'evo-property-suite'); ?></th>
                    <th><?php _e('Name', 'evo-property-suite'); ?></th>
                    <th><?php _e('Email', 'evo-property-suite'); ?></th>
                    <th><?php _e('Phone', 'evo-property-suite'); ?></th>
                    <th class="column-message"><?php _e('Message', 'evo-property-suite'); ?></th>
                    <th>
                        <a href="<?php echo esc_url($date_sort_url); ?>">
                            <?php _e('Submitted', 'evo-property-suite'); ?>
                            <span aria-hidden="true"><?php echo $order === 'ASC' ? '↑' : '↓'; ?></span>
                        </a>
                    </th>
                    <th class="column-actions"><?php _e('Actions', 'evo-property-suite'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($leads)) : ?>
                    <tr><td colspan="9"><?php _e('No leads found.', 'evo-property-suite'); ?></td></tr>
                <?php else: foreach ($leads as $lead): ?>
                    <?php
                    $delete_url = wp_nonce_url(
                        add_query_arg(
                            array(
                                'page' => 'wps-leads',
                                'action' => 'delete',
                                'lead_id' => absint($lead->id),
                                's' => $search,
                                'order' => strtolower($order),
                                'paged' => $paged,
                            ),
                            admin_url('admin.php')
                        ),
                        'wps_delete_lead_' . absint($lead->id)
                    );
                    ?>
                    <tr>
                        <td class="column-id"><?php echo esc_html($lead->id); ?></td>
                        <td class="column-property-id"><?php echo esc_html($lead->property_id); ?></td>
                        <td><?php echo esc_html($lead->property_title); ?></td>
                        <td><?php echo esc_html($lead->name); ?></td>
                        <td><a href="mailto:<?php echo esc_attr($lead->email); ?>"><?php echo esc_html($lead->email); ?></a></td>
                        <td><?php echo esc_html($lead->phone); ?></td>
                        <td class="column-message"><?php echo esc_html($lead->message); ?></td>
                        <td><?php echo esc_html($lead->created_at); ?></td>
                        <td class="column-actions">
                            <a
                                href="<?php echo esc_url($delete_url); ?>"
                                class="submitdelete"
                                onclick="return confirm('<?php echo esc_js(__('Delete this lead permanently?', 'evo-property-suite')); ?>');"
                            >
                                <?php _e('Delete', 'evo-property-suite'); ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
            <div class="tablenav-pages">
                <?php
                echo paginate_links(array(
                    'base' => add_query_arg(
                        array(
                            'page' => 'wps-leads',
                            's' => $search,
                            'order' => strtolower($order),
                            'paged' => '%#%',
                        ),
                        admin_url('admin.php')
                    ),
                    'format' => '',
                    'current' => $paged,
                    'total' => $total_pages,
                    'prev_text' => __('&laquo;', 'evo-property-suite'),
                    'next_text' => __('&raquo;', 'evo-property-suite'),
                ));
                ?>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Register a custom taxonomy
 */
function wps_register_custom_taxonomy($slug, $name) {
    $labels = array(
        'name'              => $name,
        'singular_name'     => $name,
        'search_items'      => 'Search ' . $name,
        'all_items'         => 'All ' . $name,
        'edit_item'         => 'Edit ' . $name,
        'update_item'       => 'Update ' . $name,
        'add_new_item'      => 'Add New ' . $name,
        'new_item_name'     => 'New ' . $name . ' Name',
        'menu_name'         => $name,
    );
    
    $args = array(
        'hierarchical'      => true,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => $slug),
        'show_in_rest'      => true,
    );
    
    register_taxonomy($slug, 'wps_property', $args);
}

/**
 * Register all custom taxonomies on init
 */
function wps_register_all_custom_taxonomies() {
    $custom_taxonomies = get_option('wps_custom_taxonomies', array());
    
    if (!empty($custom_taxonomies)) {
        foreach ($custom_taxonomies as $tax) {
            wps_register_custom_taxonomy($tax['slug'], $tax['name']);
        }
    }
}
add_action('init', 'wps_register_all_custom_taxonomies');

/**
 * Shortcode Guide Page
 */
function wps_shortcode_guide_page() {
    ?>
    <div class="wrap wps-guide">
        <div class="ppg-header">
            <div class="ppg-header-inner">
                <span class="dashicons dashicons-building ppg-logo"></span>
                <div>
                    <h1><?php _e('WP Property Suite — Shortcode Guide', 'evo-property-suite'); ?></h1>
                    <p class="ppg-subtitle"><?php _e('Everything you need to know to get your property listings up and running.', 'evo-property-suite'); ?></p>
                </div>
            </div>
        </div>

        <!-- Quick Start Banner -->
        <div class="ppg-quickstart">
            <h2><span class="dashicons dashicons-rocket"></span> <?php _e('Quick Start in 2 Steps', 'evo-property-suite'); ?></h2>
            <div class="ppg-steps">
                <div class="ppg-step">
                    <div class="ppg-step-num">1</div>
                    <div class="ppg-step-body">
                        <h3><?php _e('Create or Edit a Page', 'evo-property-suite'); ?></h3>
                        <p><?php _e('Go to', 'evo-property-suite'); ?> <strong><?php _e('Pages → Add New', 'evo-property-suite'); ?></strong> <?php _e('or open an existing page where you want to show property listings.', 'evo-property-suite'); ?></p>
                    </div>
                </div>
                <div class="ppg-step">
                    <div class="ppg-step-num">2</div>
                    <div class="ppg-step-body">
                        <h3><?php _e('Paste the Shortcode', 'evo-property-suite'); ?></h3>
                        <p><?php _e('Add a <strong>Shortcode block</strong> (or Classic Editor) and paste:', 'evo-property-suite'); ?></p>
                        <div class="ppg-shortcode-box">
                            <code id="ppg-main-shortcode">[wps_search]</code>
                            <button type="button" class="button button-primary ppg-copy-btn" data-copy="[wps_search]">
                                <span class="dashicons dashicons-clipboard"></span> <?php _e('Copy', 'evo-property-suite'); ?>
                            </button>
                        </div>
                        <p class="ppg-note"><?php _e('Publish / Update the page and visit it — you\'ll see your full property listing with banner, search bar, filters, and property cards.', 'evo-property-suite'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shortcode Reference -->
        <div class="ppg-section">
            <h2><span class="dashicons dashicons-editor-code"></span> <?php _e('Available Shortcodes', 'evo-property-suite'); ?></h2>
            <table class="wp-list-table widefat fixed striped ppg-table">
                <thead>
                    <tr>
                        <th style="width:220px;"><?php _e('Shortcode', 'evo-property-suite'); ?></th>
                        <th><?php _e('Description', 'evo-property-suite'); ?></th>
                        <th style="width:200px;"><?php _e('Where to Use', 'evo-property-suite'); ?></th>
                        <th style="width:100px;"><?php _e('Copy', 'evo-property-suite'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code class="ppg-code">[wps_search]</code></td>
                        <td>
                            <strong><?php _e('Full Property Listings', 'evo-property-suite'); ?></strong><br>
                            <?php _e('Displays the complete property listing page including:', 'evo-property-suite'); ?>
                            <ul style="margin:6px 0 0 18px; list-style:disc; color:#50575e;">
                                <li><?php _e('Hero banner image with overlay text', 'evo-property-suite'); ?></li>
                                <li><?php _e('Search bar with location autocomplete (Google Places API)', 'evo-property-suite'); ?></li>
                                <li><?php _e('Filter sidebar — price range, property type, rent/sold status', 'evo-property-suite'); ?></li>
                                <li><?php _e('Property cards grid/list with badges, images, area, address', 'evo-property-suite'); ?></li>
                                <li><?php _e('Dynamic pagination (respects "Properties Per Page" setting)', 'evo-property-suite'); ?></li>
                                <li><?php _e('Individual property detail pages with lead capture form', 'evo-property-suite'); ?></li>
                                <li><?php _e('Property compare feature (if enabled)', 'evo-property-suite'); ?></li>
                            </ul>
                        </td>
                        <td><?php _e('Any WordPress Page or Post', 'evo-property-suite'); ?></td>
                        <td>
                            <button type="button" class="button ppg-copy-btn" data-copy="[wps_search]">
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><code class="ppg-code">[wps_recent_properties]</code></td>
                        <td>
                            <strong><?php _e('Recently Added Properties', 'evo-property-suite'); ?></strong><br>
                            <?php _e('Displays a compact grid or slider of the latest published properties.', 'evo-property-suite'); ?>
                            <ul style="margin:6px 0 0 18px; list-style:disc; color:#50575e;">
                                <li><?php _e('<code>posts</code> — number of properties to display, for example <code>posts="6"</code>', 'evo-property-suite'); ?></li>
                                <li><?php _e('<code>columns</code> — number of cards per row when slider is disabled, for example <code>columns="4"</code>', 'evo-property-suite'); ?></li>
                                <li><?php _e('<code>slider</code> — use <code>slider="yes"</code> to enable a horizontal slider. Columns are ignored when slider is enabled.', 'evo-property-suite'); ?></li>
                            </ul>
                            <p style="margin:8px 0 0;">
                                <code>[wps_recent_properties posts="6" columns="4"]</code><br>
                                <code>[wps_recent_properties posts="8" slider="yes"]</code>
                            </p>
                        </td>
                        <td><?php _e('Homepage, sidebar areas, landing pages, or blog posts', 'evo-property-suite'); ?></td>
                        <td>
                            <button type="button" class="button ppg-copy-btn" data-copy='[wps_recent_properties posts="6" columns="4"]'>
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td><code class="ppg-code">[wps_featured_properties]</code></td>
                        <td>
                            <strong><?php _e('Featured Properties', 'evo-property-suite'); ?></strong><br>
                            <?php _e('Displays properties marked as featured from the Property edit screen.', 'evo-property-suite'); ?>
                            <ul style="margin:6px 0 0 18px; list-style:disc; color:#50575e;">
                                <li><?php _e('<code>posts</code> — number of featured properties to display, for example <code>posts="6"</code>', 'evo-property-suite'); ?></li>
                                <li><?php _e('<code>columns</code> — number of cards per row when slider is disabled, for example <code>columns="4"</code>', 'evo-property-suite'); ?></li>
                                <li><?php _e('<code>slider</code> — use <code>slider="yes"</code> to enable a horizontal slider. Columns are ignored when slider is enabled.', 'evo-property-suite'); ?></li>
                            </ul>
                            <p style="margin:8px 0 0;">
                                <code>[wps_featured_properties posts="6" columns="4"]</code><br>
                                <code>[wps_featured_properties posts="8" slider="yes"]</code>
                            </p>
                        </td>
                        <td><?php _e('Homepage, landing pages, sidebar areas, or featured sections', 'evo-property-suite'); ?></td>
                        <td>
                            <button type="button" class="button ppg-copy-btn" data-copy='[wps_featured_properties posts="6" columns="4"]'>
                                <span class="dashicons dashicons-clipboard"></span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- What You Can Customize -->
        <div class="ppg-section">
            <h2><span class="dashicons dashicons-admin-customizer"></span> <?php _e('What Can You Customize?', 'evo-property-suite'); ?></h2>
            <p class="ppg-intro"><?php printf(__('All settings are available at <strong>%s</strong>. Here\'s what each section controls:', 'evo-property-suite'), '<a href="' . admin_url('admin.php?page=wps-settings') . '">WP Property Suite Settings</a>'); ?></p>
            <div class="ppg-cards">

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-format-image"></span></div>
                    <h3><?php _e('Banner &amp; Header', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Upload or change the <strong>banner image</strong> (recommended 1920×600px)', 'evo-property-suite'); ?></li>
                        <li><?php _e('Set banner subtitle text', 'evo-property-suite'); ?></li>
                        <li><?php _e('Adjust banner height (200–800px)', 'evo-property-suite'); ?></li>
                        <li><?php _e('Change overlay opacity and color for text readability', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → Banner &amp; Header tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-admin-appearance"></span></div>
                    <h3><?php _e('Colors &amp; Typography', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Primary, secondary, text, and background colors', 'evo-property-suite'); ?></li>
                        <li><?php _e('Property card background color', 'evo-property-suite'); ?></li>
                        <li><?php _e('Font family (Arial, Helvetica, Georgia, etc.)', 'evo-property-suite'); ?></li>
                        <li><?php _e('Base font size (12–24px)', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → Colors &amp; Typography tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-admin-generic"></span></div>
                    <h3><?php _e('General Settings', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Header text (main title on the listings page)', 'evo-property-suite'); ?></li>
                        <li><?php _e('Properties per page (pagination updates automatically)', 'evo-property-suite'); ?></li>
                        <li><?php _e('Default currency — USD, EUR, GBP, INR, PKR', 'evo-property-suite'); ?></li>
                        <li><?php _e('Enable / disable preloader animation', 'evo-property-suite'); ?></li>
                        <li><?php _e('Enable / disable property compare feature', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → General tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-layout"></span></div>
                    <h3><?php _e('Property Card', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Show / hide status badge (For Sale, For Rent, etc.)', 'evo-property-suite'); ?></li>
                        <li><?php _e('Show / hide property area', 'evo-property-suite'); ?></li>
                        <li><?php _e('Show / hide full address', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → Property Card tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-email"></span></div>
                    <h3><?php _e('Contact &amp; Lead Form', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Set contact email and phone displayed on property pages', 'evo-property-suite'); ?></li>
                        <li><?php _e('Enable / disable the lead capture form on property detail pages', 'evo-property-suite'); ?></li>
                        <li><?php _e('Customize the lead form title text', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → Contact &amp; Lead Form tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-category"></span></div>
                    <h3><?php _e('Custom Taxonomies', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Create extra property categories like <em>Floors</em>, <em>Year Built</em>, <em>Parking</em>', 'evo-property-suite'); ?></li>
                        <li><?php _e('Delete taxonomies you no longer need', 'evo-property-suite'); ?></li>
                        <li><?php _e('Taxonomies appear as filter options on the frontend automatically', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → Custom Taxonomies tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-admin-network"></span></div>
                    <h3><?php _e('Google Maps API Key', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Enables address <strong>autocomplete</strong> in the search bar', 'evo-property-suite'); ?></li>
                        <li><?php _e('Get a key from Google Cloud Console (Places API + Maps JS API)', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → API Keys tab', 'evo-property-suite'); ?></strong></p>
                </div>

                <div class="ppg-card">
                    <div class="ppg-card-icon"><span class="dashicons dashicons-admin-tools"></span></div>
                    <h3><?php _e('Advanced', 'evo-property-suite'); ?></h3>
                    <ul>
                        <li><?php _e('Add <strong>Custom CSS</strong> to override any plugin style', 'evo-property-suite'); ?></li>
                    </ul>
                    <p class="ppg-where"><?php _e('Found in:', 'evo-property-suite'); ?> <strong><?php _e('Settings → Advanced tab', 'evo-property-suite'); ?></strong></p>
                </div>

            </div>
        </div>

        <!-- Layout Note -->
        <div class="ppg-section ppg-info-box">
            <h3><span class="dashicons dashicons-info"></span> <?php _e('About Page Layout', 'evo-property-suite'); ?></h3>
            <p><?php _e('When the <code>[wps_search]</code> shortcode is detected on a page, the plugin <strong>automatically switches to a full-width layout</strong> — your theme\'s sidebar is hidden on that page only. This ensures the property listings have maximum space. Your other pages remain unaffected.', 'evo-property-suite'); ?></p>
        </div>

        <!-- FAQ -->
        <div class="ppg-section">
            <h2><span class="dashicons dashicons-editor-help"></span> <?php _e('Frequently Asked Questions', 'evo-property-suite'); ?></h2>
            <div class="ppg-faq">
                <div class="ppg-faq-item">
                    <h4><?php _e('Can I use the shortcode on multiple pages?', 'evo-property-suite'); ?></h4>
                    <p><?php _e('Yes! Each instance renders independently with its own container. You can have property listings on your homepage, a dedicated listings page, or anywhere else.', 'evo-property-suite'); ?></p>
                </div>
                <div class="ppg-faq-item">
                    <h4><?php _e('Where do I add properties (listings)?', 'evo-property-suite'); ?></h4>
                    <p><?php _e('After activating the plugin you\'ll see a <strong>Properties</strong> menu item in the left sidebar of WP Admin. Add properties there — they\'ll automatically appear wherever the shortcode is placed.', 'evo-property-suite'); ?></p>
                </div>
                <div class="ppg-faq-item">
                    <h4><?php _e('How do I change the banner image?', 'evo-property-suite'); ?></h4>
                    <p><?php printf(__('Go to <a href="%s">WP Property Suite Settings → Banner &amp; Header</a>, click "Upload Image", select from your Media Library or upload a new file (1920×600px recommended), then click Save All Changes.', 'evo-property-suite'), admin_url('admin.php?page=wps-settings')); ?></p>
                </div>
                <div class="ppg-faq-item">
                    <h4><?php _e('Why is Google autocomplete not working in the search bar?', 'evo-property-suite'); ?></h4>
                    <p><?php _e('Make sure you\'ve added a valid Google Maps API key under Settings → API Keys, and that both <strong>Places API</strong> and <strong>Maps JavaScript API</strong> are enabled in your Google Cloud Console project.', 'evo-property-suite'); ?></p>
                </div>
                <div class="ppg-faq-item">
                    <h4><?php _e('Can I use the shortcode inside Gutenberg blocks?', 'evo-property-suite'); ?></h4>
                    <p><?php _e('Yes. Add a <strong>Shortcode block</strong> and paste <code>[wps_search]</code> inside it. The React frontend will render in place.', 'evo-property-suite'); ?></p>
                </div>
                <div class="ppg-faq-item">
                    <h4><?php _e('How does pagination work?', 'evo-property-suite'); ?></h4>
                    <p><?php _e('The plugin shows 6 posts per page by default (configurable in General settings). Pagination is dynamic — it recalculates automatically when you add or remove properties.', 'evo-property-suite'); ?></p>
                </div>
            </div>
        </div>

        <!-- Footer CTA -->
        <div class="ppg-footer">
            <a href="<?php echo admin_url('admin.php?page=wps-settings'); ?>" class="button button-primary button-hero">
                <span class="dashicons dashicons-admin-generic" style="vertical-align:middle; margin-right:5px;"></span>
                <?php _e('Open Plugin Settings', 'evo-property-suite'); ?>
            </a>
            <a href="<?php echo admin_url('edit.php?post_type=wps_property'); ?>" class="button button-secondary button-hero">
                <span class="dashicons dashicons-building" style="vertical-align:middle; margin-right:5px;"></span>
                <?php _e('Manage Properties', 'evo-property-suite'); ?>
            </a>
        </div>
    </div>

    <style>
        .wps-guide { max-width: 1100px; margin: 20px auto; }

        /* Header */
        .ppg-header { background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 100%); color: #fff; border-radius: 8px; padding: 30px 35px; margin-bottom: 24px; }
        .ppg-header-inner { display: flex; align-items: center; gap: 20px; }
        .ppg-logo { font-size: 48px; width: 48px; height: 48px; color: #fff; background: rgba(255,255,255,0.2); border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .ppg-header h1 { color: #fff; margin: 0; font-size: 24px; }
        .ppg-subtitle { color: rgba(255,255,255,0.85); margin: 4px 0 0; font-size: 14px; }

        /* Quick Start */
        .ppg-quickstart { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px 30px; margin-bottom: 24px; }
        .ppg-quickstart h2 { margin: 0 0 20px; display: flex; align-items: center; gap: 8px; color: #1d2327; }
        .ppg-quickstart h2 .dashicons { color: #d63638; }
        .ppg-steps { display: flex; gap: 20px; flex-wrap: wrap; }
        .ppg-step { flex: 1; min-width: 280px; background: #f6f7f7; border-radius: 8px; padding: 20px; position: relative; }
        .ppg-step-num { width: 36px; height: 36px; background: #2563eb; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 18px; margin-bottom: 12px; }
        .ppg-step h3 { margin: 0 0 8px; font-size: 15px; }
        .ppg-step p { margin: 0 0 10px; color: #50575e; font-size: 13px; }

        /* Shortcode Box */
        .ppg-shortcode-box { display: flex; align-items: center; gap: 10px; margin: 10px 0; flex-wrap: wrap; }
        .ppg-shortcode-box code { background: #1d2327; color: #4ade80; padding: 8px 16px; border-radius: 4px; font-size: 15px; font-family: monospace; flex: 1; min-width: 140px; }
        .ppg-note { font-size: 12px; color: #646970; font-style: italic; margin-top: 4px; }

        /* Sections */
        .ppg-section { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px 30px; margin-bottom: 24px; }
        .ppg-section h2 { margin: 0 0 18px; display: flex; align-items: center; gap: 8px; color: #1d2327; border-bottom: 2px solid #2563eb; padding-bottom: 12px; }
        .ppg-section h2 .dashicons { color: #2563eb; }
        .ppg-intro { color: #50575e; margin-bottom: 18px; }
        .ppg-table code.ppg-code { background: #f0f0f1; padding: 4px 8px; border-radius: 3px; font-size: 13px; color: #1d2327; }

        /* Cards Grid */
        .ppg-cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 16px; }
        .ppg-card { background: #f6f7f7; border-radius: 8px; padding: 20px; border-top: 4px solid #2563eb; }
        .ppg-card-icon { margin-bottom: 10px; }
        .ppg-card-icon .dashicons { font-size: 28px; width: 28px; height: 28px; color: #2563eb; }
        .ppg-card h3 { margin: 0 0 10px; font-size: 14px; color: #1d2327; }
        .ppg-card ul { margin: 0; padding-left: 18px; list-style: disc; }
        .ppg-card ul li { color: #50575e; font-size: 13px; margin-bottom: 5px; }
        .ppg-where { margin: 12px 0 0; font-size: 12px; color: #646970; padding-top: 10px; border-top: 1px dashed #c3c4c7; }

        /* Info Box */
        .ppg-info-box { background: #f0f6fc; border-left: 5px solid #2563eb; }
        .ppg-info-box h3 { margin: 0 0 10px; display: flex; align-items: center; gap: 8px; color: #1d2327; }
        .ppg-info-box h3 .dashicons { color: #2563eb; }
        .ppg-info-box p { margin: 0; color: #3c434a; }
        .ppg-info-box code { background: #fff; padding: 2px 6px; border: 1px solid #c3c4c7; border-radius: 3px; }

        /* FAQ */
        .ppg-faq-item { border-bottom: 1px solid #f0f0f1; padding: 14px 0; }
        .ppg-faq-item:last-child { border-bottom: none; padding-bottom: 0; }
        .ppg-faq-item h4 { margin: 0 0 8px; color: #1d2327; font-size: 14px; }
        .ppg-faq-item p { margin: 0; color: #50575e; font-size: 13px; }
        .ppg-faq-item code { background: #f0f0f1; padding: 2px 6px; border-radius: 3px; font-size: 12px; }

        /* Footer */
        .ppg-footer { background: #fff; border: 1px solid #c3c4c7; border-radius: 8px; padding: 25px 30px; display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; }

        .ppg-footer a .dashicons {
            line-height: 1 !important;
            vertical-align: middle !important;
            margin-top: -2px;
        }


        /* Copy button feedback */
        .ppg-copy-btn.copied { background: #00a32a !important; color: #fff !important; border-color: #00a32a !important; }

        @media (max-width: 600px) {
            .ppg-steps { flex-direction: column; }
            .ppg-cards { grid-template-columns: 1fr; }
            .ppg-header-inner { flex-direction: column; text-align: center; }
        }
    </style>

    <script>
    jQuery(document).ready(function($) {
        // Copy shortcode to clipboard
        $(document).on('click', '.ppg-copy-btn', function() {
            var text = $(this).data('copy');
            var $btn = $(this);

            if (navigator.clipboard) {
                navigator.clipboard.writeText(text).then(function() {
                    showCopied($btn);
                });
            } else {
                // Fallback
                var $tmp = $('<textarea>');
                $('body').append($tmp);
                $tmp.val(text).select();
                document.execCommand('copy');
                $tmp.remove();
                showCopied($btn);
            }

            function showCopied($el) {
                var orig = $el.html();
                $el.addClass('copied').html('<span class="dashicons dashicons-yes"></span> Copied!');
                setTimeout(function() {
                    $el.removeClass('copied').html(orig);
                }, 2000);
            }
        });
    });
    </script>
    <?php
}
