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
        plugins_url( '../assets/fontawesome/css/all.min.css', __FILE__ ),
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
 * Batch fetch all post meta for a property. Optimized to reduce queries from 19+ to 1.
 *
 * @param int $post_id Post ID
 * @return array Meta data
 */
function wps_get_batch_property_meta( $post_id ) {
    global $wpdb;
    
    $meta_keys = array(
        '_property_price',
        '_property_area',
        '_property_address',
        '_property_city',
        '_property_state',
        '_property_zipcode',
        '_property_country',
        '_property_latitude',
        '_property_longitude',
        '_property_status',
        '_property_featured',
        '_property_gallery',
        '_property_thumbnail_url',
        '_property_gallery_urls',
        '_property_additional_details',
        '_property_faqs',
        '_property_agent_name',
        '_property_agent_phone',
        '_property_agent_email',
        '_property_agent_photo',
    );
    
    $placeholders = implode( ',', array_fill( 0, count( $meta_keys ), '%s' ) );
    $meta_results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->postmeta 
             WHERE post_id = %d AND meta_key IN ($placeholders)",
            array_merge( array( $post_id ), $meta_keys )
        )
    );
    
    $meta_data = array();
    foreach ( $meta_results as $row ) {
        $meta_data[ $row->meta_key ] = $row->meta_value;
    }
    
    return $meta_data;
}

/**
 * Batch fetch taxonomy terms for a property. Optimized to reduce queries from 5+ to 1.
 *
 * @param int $post_id Post ID
 * @param array $taxonomies Taxonomy slugs to fetch
 * @return array Terms organized by taxonomy
 */
function wps_get_batch_post_terms( $post_id, $taxonomies = array() ) {
    global $wpdb;
    
    if ( empty( $taxonomies ) ) {
        $taxonomies = array( 'property-type', 'property-location', 'bedrooms', 'bathrooms', 'property-floor' );
    }
    
    $placeholders = implode( ',', array_fill( 0, count( $taxonomies ), '%s' ) );
    $terms_result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DISTINCT tr.term_id, t.name, tt.taxonomy 
             FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->terms} t ON tr.term_id = t.term_id
             JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             WHERE tr.object_id = %d AND tt.taxonomy IN ($placeholders)",
            array_merge( array( $post_id ), $taxonomies )
        )
    );
    
    $terms_by_taxonomy = array();
    foreach ( $terms_result as $row ) {
        if ( ! isset( $terms_by_taxonomy[ $row->taxonomy ] ) ) {
            $terms_by_taxonomy[ $row->taxonomy ] = array();
        }
        $terms_by_taxonomy[ $row->taxonomy ][] = $row->name;
    }
    
    return $terms_by_taxonomy;
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
        // Batch fetch all meta in one query (was 19+ queries)
        $meta_data = wps_get_batch_property_meta( $post->ID );
        
        // Batch fetch all taxonomies in one query (was 5+ queries)
        $terms_by_taxonomy = wps_get_batch_post_terms( $post->ID );
        
        // Extract meta data
        $price = isset( $meta_data['_property_price'] ) ? $meta_data['_property_price'] : '';
        $area = isset( $meta_data['_property_area'] ) ? $meta_data['_property_area'] : '';
        $address = isset( $meta_data['_property_address'] ) ? $meta_data['_property_address'] : '';
        $city = isset( $meta_data['_property_city'] ) ? $meta_data['_property_city'] : '';
        $state = isset( $meta_data['_property_state'] ) ? $meta_data['_property_state'] : '';
        $zipcode = isset( $meta_data['_property_zipcode'] ) ? $meta_data['_property_zipcode'] : '';
        $country = isset( $meta_data['_property_country'] ) ? $meta_data['_property_country'] : '';
        $latitude = isset( $meta_data['_property_latitude'] ) ? $meta_data['_property_latitude'] : '';
        $longitude = isset( $meta_data['_property_longitude'] ) ? $meta_data['_property_longitude'] : '';
        $status = isset( $meta_data['_property_status'] ) ? $meta_data['_property_status'] : '';
        $featured = isset( $meta_data['_property_featured'] ) ? $meta_data['_property_featured'] : '';
        $gallery_ids_raw = isset( $meta_data['_property_gallery'] ) ? $meta_data['_property_gallery'] : '';
        $gallery_urls_raw = isset( $meta_data['_property_gallery_urls'] ) ? $meta_data['_property_gallery_urls'] : '';
        $agent_name = isset( $meta_data['_property_agent_name'] ) ? $meta_data['_property_agent_name'] : '';
        $agent_phone = isset( $meta_data['_property_agent_phone'] ) ? $meta_data['_property_agent_phone'] : '';
        $agent_email = isset( $meta_data['_property_agent_email'] ) ? $meta_data['_property_agent_email'] : '';
        $agent_photo = isset( $meta_data['_property_agent_photo'] ) ? $meta_data['_property_agent_photo'] : '';
        $additional_details_raw = isset( $meta_data['_property_additional_details'] ) ? $meta_data['_property_additional_details'] : '';
        $faqs_raw = isset( $meta_data['_property_faqs'] ) ? $meta_data['_property_faqs'] : '';
        
        // Extract taxonomies
        $property_types = isset( $terms_by_taxonomy['property-type'] ) ? $terms_by_taxonomy['property-type'] : array();
        $locations = isset( $terms_by_taxonomy['property-location'] ) ? $terms_by_taxonomy['property-location'] : array();
        $bedrooms = isset( $terms_by_taxonomy['bedrooms'] ) ? $terms_by_taxonomy['bedrooms'] : array();
        $bathrooms = isset( $terms_by_taxonomy['bathrooms'] ) ? $terms_by_taxonomy['bathrooms'] : array();
        $floors = isset( $terms_by_taxonomy['property-floor'] ) ? $terms_by_taxonomy['property-floor'] : array();

        // Handle custom taxonomies
        $custom_taxonomies = get_option('wps_custom_taxonomies', array());
        $custom_taxonomy_data = array();
        if (is_array($custom_taxonomies)) {
            foreach ($custom_taxonomies as $tax) {
                if (isset($tax['slug']) && taxonomy_exists($tax['slug'])) {
                    $custom_tax_terms = isset( $terms_by_taxonomy[ $tax['slug'] ] ) ? $terms_by_taxonomy[ $tax['slug'] ] : array();
                    $custom_taxonomy_data[$tax['slug']] = !empty($custom_tax_terms) ? $custom_tax_terms[0] : 'N/A';
                }
            }
        }

        // Build gallery
        $gallery = array();
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

        if (empty($gallery) && !empty($gallery_urls_raw)) {
            $gallery_urls = json_decode($gallery_urls_raw, true);
            if (is_array($gallery_urls)) {
                $gallery = $gallery_urls;
            }
        }

        // Parse additional details
        $additional_details = array();
        if (!empty($additional_details_raw)) {
            if (is_string($additional_details_raw)) {
                $decoded = json_decode($additional_details_raw, true);
                $additional_details = is_array($decoded) ? $decoded : array();
            } elseif (is_array($additional_details_raw)) {
                $additional_details = $additional_details_raw;
            }
        }

        // Parse FAQs
        $faqs = array();
        if (!empty($faqs_raw)) {
            if (is_string($faqs_raw)) {
                $decoded = json_decode($faqs_raw, true);
                $faqs = is_array($decoded) ? $decoded : array();
            } elseif (is_array($faqs_raw)) {
                $faqs = $faqs_raw;
            }
        }

        // Get thumbnail
        $thumb = get_the_post_thumbnail_url($post->ID, 'large');
        if (!$thumb) {
            $thumb = isset( $meta_data['_property_thumbnail_url'] ) ? $meta_data['_property_thumbnail_url'] : '';
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
            'featured' => $featured === '1',
            'price' => wps_format_price($price),
            'area' => $area ? $area . ' sq ft' : 'N/A',
            'address' => $address ?: 'Address not available',
            'city' => $city ?: '',
            'state' => $state ?: '',
            'zipcode' => $zipcode ?: '',
            'country' => $country ?: '',
            'latitude' => is_numeric($latitude) ? (float) $latitude : null,
            'longitude' => is_numeric($longitude) ? (float) $longitude : null,
            'status' => $status ?: 'for-sale',
            'property_type' => !empty($property_types) ? $property_types[0] : 'Property',
            'location' => !empty($locations) ? $locations[0] : 'Location',
            'bedrooms' => !empty($bedrooms) ? $bedrooms[0] : 'N/A',
            'bathrooms' => !empty($bathrooms) ? $bathrooms[0] : 'N/A',
            'floor' => !empty($floors) ? $floors[0] : 'N/A',
            'gallery' => $gallery,
            'agent' => array(
                'name'  => $agent_name ?: '',
                'phone' => $agent_phone ?: '',
                'email' => $agent_email ?: '',
                'photo' => $agent_photo ?: '',
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
