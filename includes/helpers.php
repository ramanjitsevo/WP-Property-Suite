<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * General helper functions for WP Property Suite
 */

/**
 * Enqueue Font Awesome for plugin icons.
 */
function wps_enqueue_fontawesome() {
    wp_enqueue_style(
        'wps-fontawesome',
        'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css',
        array(),
        '6.5.2'
    );
}

/**
 * Write debug messages only when WordPress debugging is explicitly enabled.
 */
function wps_debug_log($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message);
    }
}

/**
 * Format price with selected currency
 */
function wps_format_price($price) {
    if (!$price) {
        return 'N/A';
    }
    
    if (preg_match('/[\$€£₹Rs]/', $price)) {
        return $price;
    }
    
    $clean_price = preg_replace('/[^0-9.]/', '', $price);
    $numeric_price = floatval($clean_price);
    if ($numeric_price <= 0) {
        return 'N/A';
    }
    $currency = get_option('wps_default_currency', 'USD');
    $currency_symbols = array(
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        'INR' => '₹',        
    );
    $symbol = isset($currency_symbols[$currency]) ? $currency_symbols[$currency] : '$';
    return $symbol . number_format($numeric_price);
}

/**
 * Return the contact email, falling back to the WordPress admin email.
 */
function wps_get_contact_email() {
    $email = get_option('wps_contact_email', '');
    return is_email($email) ? $email : get_option('admin_email');
}

/**
 * Build a normalized property payload for the frontend REST API and initial hydration.
 *
 * @param WP_Post|int $post Post object or property post ID.
 * @return array|null
 */
function wps_build_property_data($post) {
    $post = get_post($post);

    if (!$post || $post->post_type !== 'wps_property') {
        return null;
    }

    try {
        $property_types = wp_get_post_terms($post->ID, 'property-type', array('fields' => 'names'));
        $locations = wp_get_post_terms($post->ID, 'property-location', array('fields' => 'names'));
        $bedrooms = wp_get_post_terms($post->ID, 'bedrooms', array('fields' => 'names'));
        $bathrooms = wp_get_post_terms($post->ID, 'bathrooms', array('fields' => 'names'));
        $floors = wp_get_post_terms($post->ID, 'property-floor', array('fields' => 'names'));

        $custom_taxonomies = get_option('wps_custom_taxonomies', array());
        $custom_taxonomy_data = array();
        if (is_array($custom_taxonomies)) {
            foreach ($custom_taxonomies as $tax) {
                if (isset($tax['slug']) && taxonomy_exists($tax['slug'])) {
                    $terms = wp_get_post_terms($post->ID, $tax['slug'], array('fields' => 'names'));
                    $custom_taxonomy_data[$tax['slug']] = !empty($terms) ? $terms[0] : 'N/A';
                }
            }
        }

        $price = get_post_meta($post->ID, '_property_price', true);
        $area = get_post_meta($post->ID, '_property_area', true);
        $address = get_post_meta($post->ID, '_property_address', true);
        $city = get_post_meta($post->ID, '_property_city', true);
        $state = get_post_meta($post->ID, '_property_state', true);
        $zipcode = get_post_meta($post->ID, '_property_zipcode', true);
        $country = get_post_meta($post->ID, '_property_country', true);
        $latitude = get_post_meta($post->ID, '_property_latitude', true);
        $longitude = get_post_meta($post->ID, '_property_longitude', true);
        $status = get_post_meta($post->ID, '_property_status', true);

        $gallery = array();
        $gallery_ids_raw = get_post_meta($post->ID, '_property_gallery', true);
        if (!empty($gallery_ids_raw)) {
            $gallery_ids = array_filter(explode(',', $gallery_ids_raw));
            foreach ($gallery_ids as $att_id) {
                $att_id = intval(trim($att_id));
                if ($att_id > 0) {
                    $url = wp_get_attachment_image_url($att_id, 'large');
                    if ($url) {
                        $gallery[] = $url;
                    }
                }
            }
        }

        if (empty($gallery)) {
            $gallery_urls_raw = get_post_meta($post->ID, '_property_gallery_urls', true);
            if (!empty($gallery_urls_raw)) {
                $gallery_urls = json_decode($gallery_urls_raw, true);
                if (is_array($gallery_urls)) {
                    $gallery = $gallery_urls;
                }
            }
        }

        $additional_details_raw = get_post_meta($post->ID, '_property_additional_details', true);
        $additional_details = array();
        if (!empty($additional_details_raw)) {
            if (is_string($additional_details_raw)) {
                $decoded = json_decode($additional_details_raw, true);
                $additional_details = is_array($decoded) ? $decoded : array();
            } elseif (is_array($additional_details_raw)) {
                $additional_details = $additional_details_raw;
            }
        }

        $faqs_raw = get_post_meta($post->ID, '_property_faqs', true);
        $faqs = array();
        if (!empty($faqs_raw)) {
            if (is_string($faqs_raw)) {
                $decoded = json_decode($faqs_raw, true);
                $faqs = is_array($decoded) ? $decoded : array();
            } elseif (is_array($faqs_raw)) {
                $faqs = $faqs_raw;
            }
        }

        $thumb = get_the_post_thumbnail_url($post->ID, 'large');
        if (!$thumb) {
            $thumb = get_post_meta($post->ID, '_property_thumbnail_url', true);
        }
        if (!$thumb && !empty($gallery)) {
            $thumb = $gallery[0];
        }

        $property_data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'date' => $post->post_date,
            'thumbnail' => $thumb ?: '',
            'featured' => get_post_meta($post->ID, '_property_featured', true) === '1',
            'price' => wps_format_price($price),
            'area' => $area ? $area . ' sq ft' : 'N/A',
            'address' => $address ?: 'Address not available',
            'city' => $city ?: '',
            'state' => $state ?: '',
            'zipcode' => $zipcode ?: '',
            'country' => $country ?: '',
            'latitude' => is_numeric($latitude) ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) ? (float) $longitude : null,
            'lat' => is_numeric($latitude) ? (float) $latitude : null,
            'lng' => is_numeric($longitude) ? (float) $longitude : null,
            'status' => $status ?: 'for-sale',
            'property_type' => !empty($property_types) ? $property_types[0] : 'Property',
            'location' => !empty($locations) ? $locations[0] : 'Location',
            'bedrooms' => !empty($bedrooms) ? $bedrooms[0] : 'N/A',
            'bathrooms' => !empty($bathrooms) ? $bathrooms[0] : 'N/A',
            'floor' => !empty($floors) ? $floors[0] : 'N/A',
            'gallery' => $gallery,
            'thumbnail_url' => get_post_meta($post->ID, '_property_thumbnail_url', true) ?: '',
            'gallery_urls' => get_post_meta($post->ID, '_property_gallery_urls', true) ?: '',
            'agent' => array(
                'name'  => get_post_meta($post->ID, '_property_agent_name', true) ?: '',
                'phone' => get_post_meta($post->ID, '_property_agent_phone', true) ?: '',
                'email' => get_post_meta($post->ID, '_property_agent_email', true) ?: '',
                'photo' => get_post_meta($post->ID, '_property_agent_photo', true) ?: '',
            ),
            'additional_details' => $additional_details,
            'faqs' => $faqs,
        );

        return array_merge($property_data, $custom_taxonomy_data);
    } catch (Exception $e) {
        wps_debug_log('[WP Property Suite] Error building property payload ' . $post->ID . ': ' . $e->getMessage());
    }

    return null;
}
