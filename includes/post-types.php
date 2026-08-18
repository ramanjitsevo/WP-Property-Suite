<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register Property custom post type.
 */
function wps_register_post_type() {
    $labels = array(
        'name'                  => _x('Properties', 'Post Type General Name', 'evo-property-suite'),
        'singular_name'         => _x('Property', 'Post Type Singular Name', 'evo-property-suite'),
        'menu_name'             => __('Properties', 'evo-property-suite'),
        'name_admin_bar'        => __('Property', 'evo-property-suite'),
        'archives'              => __('Property Archives', 'evo-property-suite'),
        'attributes'            => __('Property Attributes', 'evo-property-suite'),
        'parent_item_colon'     => __('Parent Property:', 'evo-property-suite'),
        'all_items'             => __('All Properties', 'evo-property-suite'),
        'add_new_item'          => __('Add New Property', 'evo-property-suite'),
        'add_new'               => __('Add New', 'evo-property-suite'),
        'new_item'              => __('New Property', 'evo-property-suite'),
        'edit_item'             => __('Edit Property', 'evo-property-suite'),
        'update_item'           => __('Update Property', 'evo-property-suite'),
        'view_item'             => __('View Property', 'evo-property-suite'),
        'view_items'            => __('View Properties', 'evo-property-suite'),
        'search_items'          => __('Search Properties', 'evo-property-suite'),
        'not_found'             => __('Not found', 'evo-property-suite'),
        'not_found_in_trash'    => __('Not found in Trash', 'evo-property-suite'),
        'featured_image'        => __('Featured Image', 'evo-property-suite'),
        'set_featured_image'    => __('Set featured image', 'evo-property-suite'),
        'remove_featured_image' => __('Remove featured image', 'evo-property-suite'),
        'use_featured_image'    => __('Use as featured image', 'evo-property-suite'),
        'insert_into_item'      => __('Insert into property', 'evo-property-suite'),
        'uploaded_to_this_item' => __('Uploaded to this property', 'evo-property-suite'),
        'items_list'            => __('Properties list', 'evo-property-suite'),
        'items_list_navigation' => __('Properties list navigation', 'evo-property-suite'),
        'filter_items_list'     => __('Filter properties list', 'evo-property-suite'),
    );

    register_post_type('wps_property', array(
        'label'               => __('Property', 'evo-property-suite'),
        'description'         => __('Property listings for real estate', 'evo-property-suite'),
        'labels'              => $labels,
        'supports'            => array('title', 'editor', 'excerpt', 'thumbnail', 'custom-fields'),
        'taxonomies'          => array('property-type', 'property-location', 'bedrooms', 'bathrooms', 'property-floor'),
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_icon'           => 'dashicons-building',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'query_var'           => false,
        'show_in_rest'        => true,
        'rest_base'           => 'properties',
        'capability_type'     => 'post',
    ));
}

/**
 * Register built-in property taxonomies.
 */
function wps_register_taxonomies() {
    wps_register_property_taxonomy('property-type', __('Property Types', 'evo-property-suite'), __('Property Type', 'evo-property-suite'), 'property-type');
    wps_register_property_taxonomy('property-location', __('Locations', 'evo-property-suite'), __('Location', 'evo-property-suite'), 'location');
    wps_register_property_taxonomy('bedrooms', __('Bedrooms', 'evo-property-suite'), __('Bedroom', 'evo-property-suite'), 'bedrooms');
    wps_register_property_taxonomy('bathrooms', __('Bathrooms', 'evo-property-suite'), __('Bathroom', 'evo-property-suite'), 'bathrooms');
    wps_register_property_taxonomy('property-floor', __('Floors', 'evo-property-suite'), __('Floor', 'evo-property-suite'), 'floor');
}

/**
 * Register a hierarchical property taxonomy with common labels.
 */
function wps_register_property_taxonomy($slug, $plural, $singular, $rewrite_slug) {
    register_taxonomy($slug, 'wps_property', array(
        'labels' => array(
            'name'              => $plural,
            'singular_name'     => $singular,
            'search_items'      => sprintf(__('Search %s', 'evo-property-suite'), $plural),
            'all_items'         => sprintf(__('All %s', 'evo-property-suite'), $plural),
            'edit_item'         => sprintf(__('Edit %s', 'evo-property-suite'), $singular),
            'update_item'       => sprintf(__('Update %s', 'evo-property-suite'), $singular),
            'add_new_item'      => sprintf(__('Add New %s', 'evo-property-suite'), $singular),
            'new_item_name'     => sprintf(__('New %s Name', 'evo-property-suite'), $singular),
            'menu_name'         => $plural,
        ),
        'hierarchical'      => true,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => $rewrite_slug),
        'show_in_rest'      => true,
    ));
}

/**
 * Build the React frontend URL for a property detail view.
 */
function wps_get_property_frontend_url($post) {
    $post = get_post($post);

    if (!$post || $post->post_type !== 'wps_property') {
        return home_url('/');
    }

    return add_query_arg(
        'wps_property',
        wps_get_property_frontend_slug($post),
        wps_get_property_listings_url()
    );
}

/**
 * Return the slug format used by the React frontend: title-slug-ID.
 */
function wps_get_property_frontend_slug($post) {
    $post = get_post($post);

    if (!$post) {
        return '';
    }

    $slug = sanitize_title(str_replace('&', ' and ', $post->post_title));
    $slug = $slug ? $slug : 'property';

    return $slug . '-' . intval($post->ID);
}

/**
 * Find the page that hosts the property React shortcode.
 */
function wps_get_property_listings_url() {
    $configured_page_id = absint(get_option('wps_listings_page_id', get_option('wps_property_listings_page_id', 0)));

    if ($configured_page_id) {
        $configured_page = get_post($configured_page_id);
        if ($configured_page && $configured_page->post_type === 'page' && $configured_page->post_status === 'publish') {
            return get_permalink($configured_page);
        }
    }

    $pages = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'ID',
        'order' => 'ASC',
        'fields' => 'ids',
    ));

    foreach ($pages as $page_id) {
        $content = get_post_field('post_content', $page_id);

        if (has_shortcode($content, 'wps_search') || has_shortcode($content, 'evo-property-suite')) {
            return get_permalink($page_id);
        }
    }

    return home_url('/');
}

/**
 * Point native property permalinks to the React frontend detail URL.
 */
function wps_filter_property_permalink($post_link, $post) {
    if ($post instanceof WP_Post && $post->post_type === 'wps_property') {
        return wps_get_property_frontend_url($post);
    }

    return $post_link;
}
add_filter('post_type_link', 'wps_filter_property_permalink', 10, 2);

/**
 * Point property preview links to the React frontend detail URL.
 */
function wps_filter_property_preview_link($preview_link, $post) {
    if ($post instanceof WP_Post && $post->post_type === 'wps_property') {
        return wps_get_property_frontend_url($post);
    }

    return $preview_link;
}
add_filter('preview_post_link', 'wps_filter_property_preview_link', 10, 2);

/**
 * Keep the Properties list-table "View" row action aligned with frontend URLs.
 */
function wps_filter_property_row_actions($actions, $post) {
    if ($post instanceof WP_Post && $post->post_type === 'wps_property' && isset($actions['view'])) {
        $actions['view'] = sprintf(
            '<a href="%s" rel="bookmark" aria-label="%s">%s</a>',
            esc_url(wps_get_property_frontend_url($post)),
            esc_attr(sprintf(__('View "%s"', 'evo-property-suite'), $post->post_title)),
            esc_html__('View', 'evo-property-suite')
        );
    }

    return $actions;
}
add_filter('post_row_actions', 'wps_filter_property_row_actions', 10, 2);
