<?php
if (!defined('ABSPATH')) {
    exit;
}

function wps_create_demo_property() {
    if (get_option('wps_demo_created')) {
        return;
    }

    wps_install_default_data();

    update_option('wps_demo_created', 'sample-data-json');
    wps_debug_log('[WP Property Suite Demo] Demo sample data imported from default-properties.json');
}


/**
 * Install default data (properties + settings) from JSON files.
 * Runs once and sideloads images, creates properties and updates plugin settings.
 */
function wps_install_default_data($force = false) {
    // Only run once unless forced
    if (!$force && get_option('wps_default_data_installed')) {
        return;
    }

    $data_dir = WPS_PLUGIN_PATH . 'data/';
    $props_file = $data_dir . 'default-properties.json';
    $settings_file = $data_dir . 'default-settings.json';

    if (!file_exists($props_file)) {
        wps_debug_log('[WP Property Suite] default properties JSON not found: ' . $props_file);
        return;
    }

    // Load JSON files
    $props_json = file_get_contents($props_file);
    $props = json_decode($props_json, true);
    if (!is_array($props)) {
        wps_debug_log('[WP Property Suite] Invalid default properties JSON');
        return;
    }

    // Ensure media functions available
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    wps_debug_log('[WP Property Suite] Starting default data import with ' . count($props) . ' properties');

    foreach ($props as $idx => $p) {
        // Skip if a property with the same title exists
        $existing = null;

    $property_query = new WP_Query(
    array(
        'post_type'      => 'wps_property',
        'post_status'    => 'any',
        'title'          => $p['title'] ?? '',
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    )
);

    if ( ! empty( $property_query->posts ) ) {
    $existing = $property_query->posts[0];
    }
        if ($existing) {
            wps_debug_log('[WP Property Suite] Skipping property ' . ($idx + 1) . ' - already exists: ' . ($p['title'] ?? ''));
            continue;
        }

        wps_debug_log('[WP Property Suite] Importing property ' . ($idx + 1) . '/' . count($props) . ': ' . ($p['title'] ?? ''));

        $post_arr = array(
            'post_type'    => 'wps_property',
            'post_title'   => sanitize_text_field($p['title'] ?? 'Demo Property'),
            'post_content' => wp_kses_post($p['content'] ?? ''),
            'post_excerpt' => sanitize_text_field($p['excerpt'] ?? ''),
            'post_status'  => 'publish',
        );

        $post_id = wp_insert_post($post_arr, true);
        if (is_wp_error($post_id)) {
            wps_debug_log('[WP Property Suite] Failed to create demo property: ' . $post_id->get_error_message());
            continue;
        }

        wps_debug_log('[WP Property Suite] Created post ID: ' . $post_id . ' for ' . ($p['title'] ?? ''));

        // Taxonomies
        if (!empty($p['property_type'])) wp_set_object_terms($post_id, array(sanitize_text_field($p['property_type'])), 'property-type');
        if (!empty($p['location'])) wp_set_object_terms($post_id, array(sanitize_text_field($p['location'])), 'property-location');
        if (!empty($p['bedrooms'])) wp_set_object_terms($post_id, array(sanitize_text_field($p['bedrooms'])), 'bedrooms');
        if (!empty($p['bathrooms'])) wp_set_object_terms($post_id, array(sanitize_text_field($p['bathrooms'])), 'bathrooms');
        if (!empty($p['floor'])) wp_set_object_terms($post_id, array(sanitize_text_field($p['floor'])), 'property-floor');

        // Meta fields
        if (isset($p['price'])) update_post_meta($post_id, '_property_price', sanitize_text_field($p['price']));
        if (isset($p['area'])) update_post_meta($post_id, '_property_area', sanitize_text_field($p['area']));
        if (isset($p['address'])) update_post_meta($post_id, '_property_address', sanitize_text_field($p['address']));
        if (isset($p['city'])) update_post_meta($post_id, '_property_city', sanitize_text_field($p['city']));
        if (isset($p['state'])) update_post_meta($post_id, '_property_state', sanitize_text_field($p['state']));
        if (isset($p['zipcode'])) update_post_meta($post_id, '_property_zipcode', sanitize_text_field($p['zipcode']));
        if (isset($p['country'])) update_post_meta($post_id, '_property_country', sanitize_text_field($p['country']));
        if (isset($p['lat']) && is_numeric($p['lat'])) update_post_meta($post_id, '_property_latitude', sanitize_text_field($p['lat']));
        if (isset($p['lng']) && is_numeric($p['lng'])) update_post_meta($post_id, '_property_longitude', sanitize_text_field($p['lng']));
        if (isset($p['status'])) update_post_meta($post_id, '_property_status', sanitize_text_field($p['status']));
        // Featured image sideload - save original URL as fallback
        if (!empty($p['thumbnail_url'])) {
            // Always save the original URL as fallback
            update_post_meta($post_id, '_property_thumbnail_url', esc_url_raw($p['thumbnail_url']));
            
            wps_debug_log('[WP Property Suite] Sideload thumbnail for post ' . $post_id . ': ' . $p['thumbnail_url']);
            $att_id = media_sideload_image($p['thumbnail_url'], $post_id, $p['title'] ?? 'Featured image', 'id');
            if (!is_wp_error($att_id)) {
                set_post_thumbnail($post_id, $att_id);
                wps_debug_log('[WP Property Suite] Thumbnail sideloaded successfully, attachment ID: ' . $att_id);
            } else {
                wps_debug_log('[WP Property Suite] Failed to sideload thumbnail: ' . $att_id->get_error_message());
                wps_debug_log('[WP Property Suite] Using fallback URL: ' . $p['thumbnail_url']);
            }
        }

        // Gallery - save original URLs as fallback
        $gallery_ids = array();
        if (!empty($p['gallery_urls']) && is_array($p['gallery_urls'])) {
            // Always save original URLs as fallback
            update_post_meta($post_id, '_property_gallery_urls', json_encode($p['gallery_urls']));
            
            wps_debug_log('[WP Property Suite] Sideload ' . count($p['gallery_urls']) . ' gallery images for post ' . $post_id);
            foreach ($p['gallery_urls'] as $gidx => $gurl) {
                wps_debug_log('[WP Property Suite] Gallery image ' . ($gidx + 1) . ': ' . $gurl);
                $gatt = media_sideload_image($gurl, $post_id, 'Gallery image', 'id');
                if (!is_wp_error($gatt)) {
                    $gallery_ids[] = $gatt;
                    wps_debug_log('[WP Property Suite] Gallery image ' . ($gidx + 1) . ' sideloaded, ID: ' . $gatt);
                } else {
                    wps_debug_log('[WP Property Suite] Failed to sideload gallery image: ' . $gatt->get_error_message());
                }
            }
            if (!empty($gallery_ids)) {
                update_post_meta($post_id, '_property_gallery', implode(',', $gallery_ids));
                wps_debug_log('[WP Property Suite] Gallery saved with ' . count($gallery_ids) . ' images');
            }
        }

        // Agent meta
        if (!empty($p['agent']) && is_array($p['agent'])) {
            $agent = $p['agent'];
            if (isset($agent['name'])) update_post_meta($post_id, '_property_agent_name', sanitize_text_field($agent['name']));
            if (isset($agent['phone'])) update_post_meta($post_id, '_property_agent_phone', sanitize_text_field($agent['phone']));
            if (isset($agent['email'])) update_post_meta($post_id, '_property_agent_email', sanitize_email($agent['email']));
            if (!empty($agent['photo'])) {
                update_post_meta($post_id, '_property_agent_photo', esc_url_raw($agent['photo']));
                $aatt = media_sideload_image($agent['photo'], $post_id, sanitize_text_field($agent['name'] ?? 'agent'), 'id');
                if (!is_wp_error($aatt)) {
                    update_post_meta($post_id, '_property_agent_photo', wp_get_attachment_image_url($aatt, 'thumbnail'));
                }
            }
        }

        // Additional details
        if (!empty($p['additional_details'])) {
            update_post_meta($post_id, '_property_additional_details', wp_json_encode($p['additional_details']));
        }

        // FAQs
        if (!empty($p['faqs']) && is_array($p['faqs'])) {
            update_post_meta($post_id, '_property_faqs', wp_json_encode($p['faqs']));
        }

        wps_debug_log('[WP Property Suite] Imported demo property: ' . $post_id . ' - ' . ($p['title'] ?? ''));
    }

    // Install settings
    if (file_exists($settings_file)) {
        $sjson = file_get_contents($settings_file);
        $sdata = json_decode($sjson, true);
        if (is_array($sdata)) {
            // Optionally sideload bannerImage and ctaImage and agentPhoto to store attachment URLs
            if (!empty($sdata['bannerImage'])) {
                $batt = media_sideload_image($sdata['bannerImage'], 0, 'Banner image', 'id');
                if (!is_wp_error($batt)) {
                    $banner_url = wp_get_attachment_image_url($batt, 'large');
                    update_option('wps_banner_image', $banner_url);
                } else {
                    update_option('wps_banner_image', esc_url_raw($sdata['bannerImage']));
                }
            }

            if (!empty($sdata['ctaImage'])) {
                $catt = media_sideload_image($sdata['ctaImage'], 0, 'CTA image', 'id');
                if (!is_wp_error($catt)) {
                    $cta_url = wp_get_attachment_image_url($catt, 'large');
                    update_option('wps_cta_image', $cta_url);
                } else {
                    update_option('wps_cta_image', esc_url_raw($sdata['ctaImage']));
                }
            }

            if (!empty($sdata['agentPhoto'])) {
                $aatt = media_sideload_image($sdata['agentPhoto'], 0, 'Agent photo', 'id');
                if (!is_wp_error($aatt)) {
                    $agent_photo_url = wp_get_attachment_image_url($aatt, 'thumbnail');
                    update_option('wps_agent_photo', $agent_photo_url);
                } else {
                    update_option('wps_agent_photo', esc_url_raw($sdata['agentPhoto']));
                }
            }

            // Save remaining simple settings
            $simple_keys = array(
                'headerText' => 'wps_header_text',
                'bannerSubtitle' => 'wps_banner_subtitle',
                'bannerHeight' => 'wps_banner_height',
                'bannerHeightMobile' => 'wps_banner_height_mobile',
                'bannerOverlay' => 'wps_banner_overlay',
                'bannerOverlayColor' => 'wps_banner_overlay_color',
                'primaryColor' => 'wps_primary_color',
                'secondaryColor' => 'wps_secondary_color',
                'textColor' => 'wps_text_color',
                'backgroundColor' => 'wps_background_color',
                'cardBackground' => 'wps_card_background',
                'fontFamily' => 'wps_font_family',
                'fontSize' => 'wps_font_size',
                'propertiesPerPage' => 'wps_properties_per_page',
                'showBadge' => 'wps_show_badge',
                'showArea' => 'wps_show_area',
                'showAddress' => 'wps_show_address',
                'sidebarPosition' => 'wps_sidebar_position',
                'sidebarWidth' => 'wps_sidebar_width',
                'enableFilters' => 'wps_enable_filters',
                'enableCompare' => 'wps_enable_compare',
                'enableLeadForm' => 'wps_enable_lead_form',
                'leadFormTitle' => 'wps_lead_form_title',
                'contactEmail' => 'wps_contact_email',
                'contactPhone' => 'wps_contact_phone',
                'customCSS' => 'wps_custom_css',
                'ctaTitle' => 'wps_cta_title',
                'ctaDescription' => 'wps_cta_description',
                'ctaButtonText' => 'wps_cta_button_text',
                'ctaButtonUrl' => 'wps_cta_button_url',
                'ctaBgColor' => 'wps_cta_bg_color',
                'ctaTextColor' => 'wps_cta_text_color',
                'featuresBgColor' => 'wps_features_bg_color',
                'featuresTextColor' => 'wps_features_text_color',
                'agentName' => 'wps_agent_name',
                'agentRole' => 'wps_agent_role',
                'agentPhone' => 'wps_agent_phone',
                'agentEmail' => 'wps_agent_email',
                'contactFormHeading' => 'wps_contact_form_heading',
                'contactFormSubtitle' => 'wps_contact_form_subtitle',
                'featuredLabel' => 'wps_featured_label',
                'scheduleTourUrl' => 'wps_schedule_tour_url',
                'socialFacebook' => 'wps_social_facebook',
                'socialTwitter' => 'wps_social_twitter',
                'socialLinkedin' => 'wps_social_linkedin',
                'socialInstagram' => 'wps_social_instagram',
            );

            foreach ($simple_keys as $src => $opt_name) {
                if (isset($sdata[$src])) {
                    $value = $sdata[$src];
                    if ($src === 'contactEmail' && !is_email($value)) {
                        $value = get_option('admin_email');
                    }
                    update_option($opt_name, $value);
                }
            }
        }
    }

    wps_install_default_leads();

    // Mark installed
    update_option('wps_default_data_installed', 1);
    wps_debug_log('[WP Property Suite] Default data import complete');
}

/**
 * Seed demo leads for the default property data.
 */
function wps_install_default_leads() {
    global $wpdb;

    wps_create_leads_table();

    $table_name = $wpdb->prefix . 'property_leads';
    $sample_leads = array(
        array(
            'property_title' => 'Modern Luxury Villa — Beverly Hills',
            'name' => 'Aarav Mehta',
            'email' => 'aarav.mehta@example.com',
            'phone' => '+1 (310) 555-1001',
            'message' => 'Please share the latest availability and a weekend viewing slot.',
            'created_at' => current_time('mysql'),
        ),
        array(
            'property_title' => 'Cozy Downtown Apartment — New York',
            'name' => 'Maya Kapoor',
            'email' => 'maya.kapoor@example.com',
            'phone' => '+1 (212) 555-1002',
            'message' => 'I am interested in this apartment and would like details about monthly fees.',
            'created_at' => current_time('mysql'),
        ),
        array(
            'property_title' => 'Beachfront Bungalow — Malibu',
            'name' => 'Daniel Brooks',
            'email' => 'daniel.brooks@example.com',
            'phone' => '+1 (424) 555-1003',
            'message' => 'Can you confirm beach access rules and rental potential for this property?',
            'created_at' => current_time('mysql'),
        ),
        array(
            'property_title' => 'Spacious Family Home — Austin, Texas',
            'name' => 'Priya Nair',
            'email' => 'priya.nair@example.com',
            'phone' => '+1 (512) 555-1004',
            'message' => 'We are relocating to Austin and want to schedule a family tour next week.',
            'created_at' => current_time('mysql'),
        ),
        array(
            'property_title' => 'Luxury Penthouse — Miami Beach',
            'name' => 'Ethan Williams',
            'email' => 'ethan.williams@example.com',
            'phone' => '+1 (305) 555-1005',
            'message' => 'Please send the full brochure, HOA details, and private showing options.',
            'created_at' => current_time('mysql'),
        ),
    );

    foreach ($sample_leads as $lead) {
        $property = null;

        $property_query = new WP_Query(
        array(
        'post_type'      => 'wps_property',
        'post_status'    => 'any',
        'title'          => $lead['property_title'],
        'posts_per_page' => 1,
        'no_found_rows'  => true,
    )
);

if ( ! empty( $property_query->posts ) ) {
    $property = $property_query->posts[0];
}
        $property_id = $property ? intval($property->ID) : 0;

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM $table_name WHERE email = %s AND property_title = %s LIMIT 1",
                $lead['email'],
                $lead['property_title']
            )
        );

        if ($exists) {
            continue;
        }

        $wpdb->insert(
            $table_name,
            array(
                'property_id' => $property_id,
                'property_title' => $lead['property_title'],
                'name' => $lead['name'],
                'email' => $lead['email'],
                'phone' => $lead['phone'],
                'message' => $lead['message'],
                'created_at' => $lead['created_at'],
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
    }
}
