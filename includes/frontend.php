<?php
if (!defined('ABSPATH')) {
    exit;
}

function wps_handle_layout() {
    global $post;
    
    if (wps_post_has_search_shortcode($post)) {
        // Remove default WordPress sidebars
        remove_action('sidebar', 'dynamic_sidebar');
        
        // Add custom CSS to hide sidebars and make content full width
        add_action('wp_head', 'wps_custom_styles', 100);
        
        // Add body class for custom styling
        add_filter('body_class', 'wps_add_body_class');
        
        // Force full width template if theme supports it
        add_filter('template_include', 'wps_force_full_width');
    }
}

/**
 * Check whether the current post contains a supported property search shortcode.
 */
function wps_post_has_search_shortcode($post) {
    if (!is_a($post, 'WP_Post')) {
        return false;
    }

    return has_shortcode($post->post_content, 'wps_search') || has_shortcode($post->post_content, 'evo-property-suite');
}

/**
 * Force full width template
 */
function wps_force_full_width($template) {
    // Try to find a full-width template in the theme
    $full_width_template = locate_template(array('template-fullwidth.php', 'full-width.php', 'page-fullwidth.php'));
    
    if ($full_width_template) {
        return $full_width_template;
    }
    
    return $template;
}

/**
 * Add custom styles to hide sidebars and make full width
 */
function wps_custom_styles() {
    ?>
    <style>
        /* Hide WordPress theme sidebars only (NOT the property plugin sidebar) */
        .site-sidebar,
        .theme-sidebar,
        #secondary,
        .widget-area,
        aside.widget-area,
        .sidebar-primary,
        .sidebar-secondary,
        #sidebar-primary,
        #sidebar-secondary,
        .wp-sidebar,
        .theme-widget-area,
        div#secondary,
        section.widget-area:not(.properties-sidebar),
        aside:not(.properties-sidebar):not([class*="wps"]) {display: none !important; width: 0 !important; visibility: hidden !important; }
        
        /* Make WordPress content area full width */
        .content-area,
        #primary,
        .site-content,
        .site-main,
        .main-content,
        .content-wrapper,
        div#primary,
        .site-content > .container,
        .site-content > .container-fluid,
        main.site-main {width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; float: none !important; }
        
        /* Remove WordPress container constraints */
        .site-content .container,
        #main .container,
        .container.content-area,
        .wrapper.site-content { max-width: 100% !important; width: 100% !important; padding: 0 !important; }
        
        /* Hide WordPress page title and comments */
        .entry-title,
        .page-title,
        h1.entry-title,
        .post-title,
        .page-header,
        .entry-header,
        #comments,
        .comments-area { display: none !important; }
        
        /* Ensure property plugin container takes full width */
        .wps-container { width: 100% !important; max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        
        /* IMPORTANT: Keep property plugin sidebar visible */
        .wps-app .properties-sidebar,
        .wps-app aside.properties-sidebar,
        .wps-container .properties-sidebar,
        aside.properties-sidebar {display: block !important; width: 300px !important; visibility: visible !important; opacity: 1 !important; }

        /* Expand boxed Elementor sections to the plugin's default wide layout */
        .elementor-section.elementor-section-boxed > .elementor-container {
            max-width: 1500px !important;
        }

        @media(max-width: 768px){
            .wps-app .properties-sidebar,
            .wps-app aside.properties-sidebar,
            .wps-container .properties-sidebar,
            aside.properties-sidebar { display: block !important; width: 100% !important; visibility: visible !important; opacity: 1 !important;  }

        }
    </style>
    <?php
}

/**
 * Add custom body class
 */
function wps_add_body_class($classes) {
    $classes[] = 'wps-full-width';
    $classes[] = 'wps-no-sidebar';
    $classes[] = 'wps-page';
    return $classes;
}

function wps_shortcode($atts) {
    // Unique ID for the container
    $container_id = 'wps-root-' . uniqid();

    wps_enqueue_assets(true);
    
    // Add viewport meta tag for responsive design (only if not already present)
    if (!has_action('wp_head', 'wps_viewport_meta')) {
        add_action('wp_head', 'wps_viewport_meta');
    }
    
    ob_start();
    ?>
    <div id="<?php echo esc_attr($container_id); ?>" class="wps-container" data-container-id="<?php echo esc_attr($container_id); ?>"></div>
    <?php
    return ob_get_clean();
}

/**
 * Build an initial property payload for the frontend so the first render is not empty.
 *
 * @param int $limit Number of properties to preload.
 * @return array
 */
function wps_get_initial_properties_payload($limit = 10) {
    $limit = max(1, absint($limit));

    $posts = get_posts(array(
        'post_type' => 'wps_property',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    $properties = array();
    foreach ($posts as $post) {
        $property_data = wps_build_property_data($post);
        if (!empty($property_data)) {
            $properties[] = $property_data;
        }
    }

    return $properties;
}

/**
 * Display recently added properties.
 *
 * Usage: [wps_recent_properties posts="6" columns="4" slider="no"]
 */
function wps_recent_properties_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts' => 3,
        'number' => '',
        'limit' => '',
        'columns' => 4,
        'cols' => '',
        'slider' => 'no',
        'enable_slider' => '',
    ), $atts, 'wps_recent_properties');

    $posts_count = !empty($atts['number']) ? $atts['number'] : $atts['posts'];
    $posts_count = !empty($atts['limit']) ? $atts['limit'] : $posts_count;
    $posts_count = max(1, min(50, absint($posts_count)));

    $columns = !empty($atts['cols']) ? $atts['cols'] : $atts['columns'];
    $columns = max(1, min(6, absint($columns)));

    $slider_value = !empty($atts['enable_slider']) ? $atts['enable_slider'] : $atts['slider'];
    $slider_enabled = in_array(strtolower((string) $slider_value), array('1', 'true', 'yes', 'on'), true);

    wps_enqueue_recent_properties_assets();

    $properties = get_posts(array(
        'post_type' => 'wps_property',
        'post_status' => 'publish',
        'posts_per_page' => $posts_count,
        'orderby' => 'date',
        'order' => 'DESC',
    ));

    if (empty($properties)) {
        return '<div class="wps-recent-properties-empty">' . esc_html__('No properties found.', 'evo-property-suite') . '</div>';
    }

    $container_id = 'wps-recent-properties-' . uniqid();
    $wrapper_classes = 'wps-recent-properties';
    $wrapper_classes .= $slider_enabled ? ' is-slider' : ' is-grid';

    ob_start();
    ?>
    <section id="<?php echo esc_attr($container_id); ?>" class="<?php echo esc_attr($wrapper_classes); ?>" style="<?php echo $slider_enabled ? '' : '--wps-recent-columns:' . esc_attr($columns) . ';--wps-recent-tablet-columns:' . esc_attr(min($columns, 2)) . ';'; ?>">
        <?php if ($slider_enabled) : ?>
            <button type="button" class="wps-recent-slider-btn wps-recent-slider-prev" aria-label="<?php esc_attr_e('Previous properties', 'evo-property-suite'); ?>">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
        <?php endif; ?>

        <div class="wps-recent-properties-track">
            <?php foreach ($properties as $property) : ?>
                <?php echo wps_render_recent_property_card($property); ?>
            <?php endforeach; ?>
        </div>

        <?php if ($slider_enabled) : ?>
            <button type="button" class="wps-recent-slider-btn wps-recent-slider-next" aria-label="<?php esc_attr_e('Next properties', 'evo-property-suite'); ?>">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
            <script>
                (function() {
                    var root = document.getElementById('<?php echo esc_js($container_id); ?>');
                    if (!root) {
                        return;
                    }

                    var track = root.querySelector('.wps-recent-properties-track');
                    var previous = root.querySelector('.wps-recent-slider-prev');
                    var next = root.querySelector('.wps-recent-slider-next');

                    if (!track || !previous || !next) {
                        return;
                    }

                    function slide(direction) {
                        var card = track.querySelector('.wps-recent-property-card');
                        var distance = card ? card.getBoundingClientRect().width + 20 : track.clientWidth;
                        track.scrollBy({ left: direction * distance, behavior: 'smooth' });
                    }

                    previous.addEventListener('click', function() { slide(-1); });
                    next.addEventListener('click', function() { slide(1); });
                }());
            </script>
        <?php endif; ?>
    </section>
    <?php

    return ob_get_clean();
}

/**
 * Display properties marked as featured in the admin.
 *
 * Usage: [wps_featured_properties posts="6" columns="4" slider="no"]
 */
function wps_featured_properties_shortcode($atts) {
    $atts = shortcode_atts(array(
        'posts' => 3,
        'number' => '',
        'limit' => '',
        'columns' => 4,
        'cols' => '',
        'slider' => 'no',
        'enable_slider' => '',
    ), $atts, 'wps_featured_properties');

    $posts_count = !empty($atts['number']) ? $atts['number'] : $atts['posts'];
    $posts_count = !empty($atts['limit']) ? $atts['limit'] : $posts_count;
    $posts_count = max(1, min(50, absint($posts_count)));

    $columns = !empty($atts['cols']) ? $atts['cols'] : $atts['columns'];
    $columns = max(1, min(6, absint($columns)));

    $slider_value = !empty($atts['enable_slider']) ? $atts['enable_slider'] : $atts['slider'];
    $slider_enabled = in_array(strtolower((string) $slider_value), array('1', 'true', 'yes', 'on'), true);

    wps_enqueue_recent_properties_assets();

    $properties = get_posts(array(
        'post_type' => 'wps_property',
        'post_status' => 'publish',
        'posts_per_page' => $posts_count,
        'orderby' => 'date',
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => '_property_featured',
                'value' => '1',
                'compare' => '=',
            ),
        ),
    ));

    if (empty($properties)) {
        return '<div class="wps-recent-properties-empty">' . esc_html__('No featured properties found.', 'evo-property-suite') . '</div>';
    }

    $container_id = 'wps-featured-properties-' . uniqid();
    $wrapper_classes = 'wps-recent-properties wps-featured-properties';
    $wrapper_classes .= $slider_enabled ? ' is-slider' : ' is-grid';

    ob_start();
    ?>
    <section id="<?php echo esc_attr($container_id); ?>" class="<?php echo esc_attr($wrapper_classes); ?>" style="<?php echo $slider_enabled ? '' : '--wps-recent-columns:' . esc_attr($columns) . ';--wps-recent-tablet-columns:' . esc_attr(min($columns, 2)) . ';'; ?>">
        <?php if ($slider_enabled) : ?>
            <button type="button" class="wps-recent-slider-btn wps-recent-slider-prev" aria-label="<?php esc_attr_e('Previous properties', 'evo-property-suite'); ?>">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
        <?php endif; ?>

        <div class="wps-recent-properties-track">
            <?php foreach ($properties as $property) : ?>
                <?php echo wps_render_recent_property_card($property); ?>
            <?php endforeach; ?>
        </div>

        <?php if ($slider_enabled) : ?>
            <button type="button" class="wps-recent-slider-btn wps-recent-slider-next" aria-label="<?php esc_attr_e('Next properties', 'evo-property-suite'); ?>">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>
            <script>
                (function() {
                    var root = document.getElementById('<?php echo esc_js($container_id); ?>');
                    if (!root) {
                        return;
                    }

                    var track = root.querySelector('.wps-recent-properties-track');
                    var previous = root.querySelector('.wps-recent-slider-prev');
                    var next = root.querySelector('.wps-recent-slider-next');

                    if (!track || !previous || !next) {
                        return;
                    }

                    function slide(direction) {
                        var card = track.querySelector('.wps-recent-property-card');
                        var distance = card ? card.getBoundingClientRect().width + 20 : track.clientWidth;
                        track.scrollBy({ left: direction * distance, behavior: 'smooth' });
                    }

                    previous.addEventListener('click', function() { slide(-1); });
                    next.addEventListener('click', function() { slide(1); });
                }());
            </script>
        <?php endif; ?>
    </section>
    <?php

    return ob_get_clean();
}

/**
 * Render a compact property card for the recent properties shortcode.
 */
function wps_render_recent_property_card($property) {
    $property = get_post($property);

    if (!$property || $property->post_type !== 'wps_property') {
        return '';
    }

    $thumbnail = get_the_post_thumbnail_url($property->ID, 'large');
    if (!$thumbnail) {
        $thumbnail = get_post_meta($property->ID, '_property_thumbnail_url', true);
    }
    if (!$thumbnail) {
        $gallery_urls = get_post_meta($property->ID, '_property_gallery_urls', true);
        $gallery_urls = $gallery_urls ? json_decode($gallery_urls, true) : array();
        $thumbnail = is_array($gallery_urls) && !empty($gallery_urls[0]) ? $gallery_urls[0] : '';
    }

    $price = wps_format_price(get_post_meta($property->ID, '_property_price', true));
    $area = get_post_meta($property->ID, '_property_area', true);
    $city = get_post_meta($property->ID, '_property_city', true);
    $address = get_post_meta($property->ID, '_property_address', true);
    $status = get_post_meta($property->ID, '_property_status', true);
    $status_label = $status ? ucwords(str_replace('-', ' ', $status)) : __('For Sale', 'evo-property-suite');
    $location = $city ?: $address;
    $features = wps_get_recent_property_features($property->ID, $area);
    $property_url = function_exists('wps_get_property_frontend_url') ? wps_get_property_frontend_url($property) : get_permalink($property);

    ob_start();
    ?>
    <article class="wps-recent-property-card">
        <a class="wps-recent-property-link" href="<?php echo esc_url($property_url); ?>">
            <div class="wps-recent-property-image-wrap">
                <?php if ($thumbnail) : ?>
                    <img class="wps-recent-property-image" src="<?php echo esc_url($thumbnail); ?>" alt="<?php echo esc_attr($property->post_title); ?>">
                <?php else : ?>
                    <div class="wps-recent-property-placeholder"><?php esc_html_e('No Image', 'evo-property-suite'); ?></div>
                <?php endif; ?>
                <span class="wps-recent-property-badge"><?php echo esc_html($status_label); ?></span>
            </div>
            <div class="wps-recent-property-body">
                <h3 class="wps-recent-property-title"><?php echo esc_html($property->post_title); ?></h3>
                <?php if ($location) : ?>
                    <p class="wps-recent-property-location">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <?php echo esc_html($location); ?>
                    </p>
                <?php endif; ?>
                <p class="wps-recent-property-price"><?php echo esc_html($price); ?></p>
                <?php if (!empty($features)) : ?>
                    <div class="wps-recent-property-features">
                        <?php foreach ($features as $feature) : ?>
                            <span>
                                <i class="fas <?php echo esc_attr($feature['icon']); ?>" aria-hidden="true"></i>
                                <?php echo esc_html($feature['label']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </a>
    </article>
    <?php

    return ob_get_clean();
}

/**
 * Return the first three available card features.
 */
function wps_get_recent_property_features($property_id, $area) {
    $bedrooms = wp_get_post_terms($property_id, 'bedrooms', array('fields' => 'names'));
    $bathrooms = wp_get_post_terms($property_id, 'bathrooms', array('fields' => 'names'));
    $floors = wp_get_post_terms($property_id, 'property-floor', array('fields' => 'names'));

    $features = array();

    if (!empty($bedrooms[0])) {
        $features[] = array('icon' => 'fa-bed', 'label' => sprintf(__('%s Beds', 'evo-property-suite'), $bedrooms[0]));
    }
    if (!empty($bathrooms[0])) {
        $features[] = array('icon' => 'fa-bath', 'label' => sprintf(__('%s Baths', 'evo-property-suite'), $bathrooms[0]));
    }
    if (!empty($floors[0])) {
        $features[] = array('icon' => 'fa-building', 'label' => sprintf(__('Floor: %s', 'evo-property-suite'), $floors[0]));
    }
    if (get_option('wps_show_area', '1') !== '0' && !empty($area)) {
        $features[] = array('icon' => 'fa-ruler-combined', 'label' => $area . ' sq ft');
    }

    return array_slice($features, 0, 3);
}

/**
 * Enqueue scoped styles for recent properties shortcode.
 */
function wps_enqueue_recent_properties_assets() {
    wps_enqueue_fontawesome();

    wp_register_style('wps-recent-properties', false, array(), WPS_PLUGIN_VERSION);
    wp_enqueue_style('wps-recent-properties');
    wp_add_inline_style('wps-recent-properties', '
        .wps-recent-properties {
            --wps-recent-gap: 20px;
            position: relative;
            width: 100%;
            margin: 24px 0;
        }
        .wps-recent-properties-track {
            display: grid;
            gap: var(--wps-recent-gap);
        }
        .wps-recent-properties.is-grid .wps-recent-properties-track {
            grid-template-columns: repeat(var(--wps-recent-columns, 4), minmax(0, 1fr));
        }
        .wps-recent-properties.is-slider {
            padding: 0 48px;
        }
        .wps-recent-properties.is-slider .wps-recent-properties-track {
            display: flex;
            overflow-x: auto;
            scroll-snap-type: x mandatory;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
        }
        .wps-recent-properties.is-slider .wps-recent-properties-track::-webkit-scrollbar {
            display: none;
        }
        .wps-recent-properties.is-slider .wps-recent-property-card {
            flex: 0 0 min(320px, 85vw);
            scroll-snap-align: start;
        }
        .wps-recent-property-card {
            min-width: 0;
            overflow: hidden;
            background: ' . esc_html(get_option('wps_card_background', '#ffffff')) . ';
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
        }
        .wps-recent-property-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
        }
        .wps-recent-property-link {
            display: flex;
            flex-direction: column;
            height: 100%;
            color: inherit;
            text-decoration: none;
        }
        .wps-recent-property-image-wrap {
            position: relative;
            height: 220px;
            overflow: hidden;
            background: #eef2f7;
        }
        .wps-recent-property-image {
            position: absolute;
            inset: 0;
            width: 100% !important;
            height: 100% !important;
            display: block;
            object-fit: cover !important;
            object-position: center center !important;
            transition: transform 0.25s ease;
        }
        .wps-recent-property-card:hover .wps-recent-property-image {
            transform: scale(1.04);
        }
        .wps-recent-property-placeholder {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            color: #64748b;
            font-weight: 600;
        }
        .wps-recent-property-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            padding: 5px 12px;
            border-radius: 4px;
            background: ' . esc_html(get_option('wps_secondary_color', '#10b981')) . ';
            color: #fff;
            font-size: 12px;
            font-weight: 700;
        }
        .wps-recent-property-body {
            display: flex;
            flex: 1;
            flex-direction: column;
            gap: 8px;
            padding: 18px 20px 20px;
        }
        .wps-recent-property-title {
            margin: 0;
            overflow: hidden;
            color: #1a1a2e;
            font-size: 18px;
            font-weight: 600;
            line-height: 1.35;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2;
            line-clamp: 2;
        }
        .wps-recent-property-location {
            margin: 0;
            color: #666;
            font-size: 14px;
        }
        .wps-recent-property-price {
            margin: 0;
            color: ' . esc_html(get_option('wps_primary_color', '#2563eb')) . ';
            font-size: 22px;
            font-weight: 800;
        }
        .wps-recent-property-features {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 12px;
            margin-top: 4px;
            padding-top: 10px;
            border-top: 1px solid #f0f0f0;
            color: #666;
            font-size: 14px;
        }
        .wps-recent-property-features span {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            white-space: nowrap;
        }
        .wps-recent-property-features i {
            color: #667eea;
        }
        .wps-recent-slider-btn {
            position: absolute;
            top: 50%;
            z-index: 2;
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 50%;
            background: #fff;
            color: #1f2937;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.18);
            cursor: pointer;
            transform: translateY(-50%);
        }
        .wps-recent-slider-prev {
            left: 0;
        }
        .wps-recent-slider-next {
            right: 0;
        }
        .wps-recent-properties-empty {
            padding: 18px;
            border-radius: 8px;
            background: #f8fafc;
            color: #64748b;
            text-align: center;
        }
        @media (max-width: 900px) {
            .wps-recent-properties.is-grid .wps-recent-properties-track {
                grid-template-columns: repeat(var(--wps-recent-tablet-columns, 2), minmax(0, 1fr));
            }
        }
        @media (max-width: 560px) {
            .wps-recent-properties.is-grid .wps-recent-properties-track {
                grid-template-columns: 1fr;
            }
            .wps-recent-properties.is-slider {
                padding: 0;
            }
            .wps-recent-slider-btn {
                display: none;
            }
        }
    ');
}

/**
 * Conditionally enqueue React assets - only on pages with WPS shortcodes.
 * This prevents loading 160KB of React JS on pages that don't need it.
 */
function wps_enqueue_assets_conditional() {
    global $post;
    
    // Check if we're on a page with the shortcode
    if ( ! $post || ( ! has_shortcode( $post->post_content, 'wps_search' ) && ! has_shortcode( $post->post_content, 'evo-property-suite' ) ) ) {
        // Not on a WPS page, skip loading the heavy React bundle
        return;
    }
    
    // This is a WPS page, load the assets
    wps_enqueue_assets( false );
}

/**
 * Enqueue React assets
 */
function wps_enqueue_assets($force = false) {
    // Check if we're on a page with the shortcode
    global $post;
    
    // Debug: Log when this function runs
    wps_debug_log('WP Property Suite: Enqueue assets function called');
    
    if ($force || wps_post_has_search_shortcode($post)) {
        wps_debug_log('WP Property Suite: Shortcode found on page');
        wps_enqueue_fontawesome();
        
        $build_path = WPS_PLUGIN_PATH . 'build';
        wps_debug_log('WP Property Suite: Build path = ' . $build_path);
        
        // Check if build exists
        if (file_exists($build_path)) {
            wps_debug_log('WP Property Suite: Build folder exists');
            
            // Get the built files (with hash)
            $js_files = glob(WPS_PLUGIN_PATH . 'build/static/js/main.*.js');
            $css_files = glob(WPS_PLUGIN_PATH . 'build/static/css/main.*.css');
            
            wps_debug_log('WP Property Suite: JS files found = ' . count($js_files));
            wps_debug_log('WP Property Suite: CSS files found = ' . count($css_files));
            
            if (!empty($js_files)) {
                usort($js_files, function($left, $right) {
                    return filemtime($right) <=> filemtime($left);
                });
                $js_file = basename($js_files[0]);

                $css_file = null;
                if (!empty($css_files)) {
                    usort($css_files, function($left, $right) {
                        return filemtime($right) <=> filemtime($left);
                    });
                    $css_file = basename($css_files[0]);
                }
                
                wps_debug_log('WP Property Suite: Enqueuing JS = ' . $js_file);
                
                wp_enqueue_script(
                    'wps-react',
                    WPS_PLUGIN_URL . 'build/static/js/' . $js_file,
                    array(),
                    WPS_PLUGIN_VERSION,
                    true
                );
                
                // Pass REST API URL and settings to React via inline script.
                // wp_add_inline_script is used instead of wp_localize_script to
                // guarantee the data is printed even when wps_enqueue_assets() is
                // called after wp_enqueue_scripts has already fired (e.g. from a
                // shortcode handler). The data is always output before the script
                // runs, regardless of footer enqueue.
                $wps_plugin_data = array(
                    'apiUrl' => esc_url_raw(rest_url('wps/v1')),
                    'nonce' => wp_create_nonce('wp_rest'),
                    'settings' => array(
                        'headerText' => get_option('wps_header_text', 'Find Your Dream Property'),
                        'bannerSubtitle' => get_option('wps_banner_subtitle', 'Discover the perfect home for your family'),
                        'bannerImage' => get_option('wps_banner_image', ''),
                        'bannerHeight' => get_option('wps_banner_height', '320'),
                        'bannerHeightMobile' => get_option('wps_banner_height_mobile', '250'),
                        'bannerOverlay' => get_option('wps_banner_overlay', '50'),
                        'bannerOverlayColor' => get_option('wps_banner_overlay_color', '#000000'),
                        'primaryColor' => get_option('wps_primary_color', '#2563eb'),
                        'secondaryColor' => get_option('wps_secondary_color', '#10b981'),
                        'textColor' => get_option('wps_text_color', '#1f2937'),
                        'backgroundColor' => get_option('wps_background_color', '#f3f4f6'),
                        'cardBackground' => get_option('wps_card_background', '#ffffff'),
                        'fontFamily' => get_option('wps_font_family', 'Arial, sans-serif'),
                        'fontSize' => get_option('wps_font_size', '16'),
                        'propertiesPerPage' => get_option('wps_properties_per_page', '12'),
                        'showBadge' => get_option('wps_show_badge', '1'),
                        'showArea' => get_option('wps_show_area', '1'),
                        'showAddress' => get_option('wps_show_address', '1'),
                        'sidebarPosition' => get_option('wps_sidebar_position', 'left'),
                        'sidebarWidth' => get_option('wps_sidebar_width', '280'),
                        'enableFilters' => get_option('wps_enable_filters', '1'),
                        'enableCompare' => get_option('wps_enable_compare', '1'),
                        'enableLeadForm' => get_option('wps_enable_lead_form', '1'),
                        'leadFormTitle' => get_option('wps_lead_form_title', 'Interested in this property?'),
                        'contactEmail' => wps_get_contact_email(),
                        'contactPhone' => get_option('wps_contact_phone', ''),
                        'customCSS' => get_option('wps_custom_css', ''),
                        'googleApiKey' => trim((string) get_option('wps_google_api_key', '')),
                        // CTA Section
                        'ctaImage' => get_option('wps_cta_image', ''),
                        'ctaTitle' => get_option('wps_cta_title', 'Want to Sell or Rent Your Property?'),
                        'ctaDescription' => get_option('wps_cta_description', 'List your property with us and reach thousands of potential buyers and renters.'),
                        'ctaButtonText' => get_option('wps_cta_button_text', 'Add Property Now'),
                        'ctaButtonUrl' => get_option('wps_cta_button_url', '/wp-admin/post-new.php?post_type=wps_property'),
                        'ctaBgColor' => get_option('wps_cta_bg_color', '#f0f9ff'),
                        'ctaTextColor' => get_option('wps_cta_text_color', '#1e3a5f'),
                        // Features Section
                        'featuresBgColor' => get_option('wps_features_bg_color', '#ffffff'),
                        'featuresTextColor' => get_option('wps_features_text_color', '#1f2937'),
                        'features' => array(
                            array(
                                'icon' => get_option('wps_feature_1_icon', 'fas fa-trophy'),
                                'title' => get_option('wps_feature_1_title', 'Trusted by Thousands'),
                                'description' => get_option('wps_feature_1_description', 'Join thousands of happy clients who found their perfect property.'),
                            ),
                            array(
                                'icon' => get_option('wps_feature_2_icon', 'fas fa-chart-bar'),
                                'title' => get_option('wps_feature_2_title', 'Wide Range of Properties'),
                                'description' => get_option('wps_feature_2_description', 'Explore a wide range of properties for sale and rent.'),
                            ),
                            array(
                                'icon' => get_option('wps_feature_3_icon', 'fas fa-users'),
                                'title' => get_option('wps_feature_3_title', 'Expert Agents'),
                                'description' => get_option('wps_feature_3_description', 'Work with experienced agents to find the best property.'),
                            ),
                            array(
                                'icon' => get_option('wps_feature_4_icon', 'fas fa-shield-alt'),
                                'title' => get_option('wps_feature_4_title', 'Secure & Easy Process'),
                                'description' => get_option('wps_feature_4_description', 'Enjoy a secure and hassle-free property buying or renting process.'),
                            ),
                        ),
                        // Single Property Page
                        'agentName' => get_option('wps_agent_name', 'John Smith'),
                        'agentPhoto' => get_option('wps_agent_photo', ''),
                        'agentRole' => get_option('wps_agent_role', 'Property Agent'),
                        'agentPhone' => get_option('wps_agent_phone', '+1 (555) 123-4567'),
                        'agentEmail' => get_option('wps_agent_email', ''),
                        'contactFormHeading' => get_option('wps_contact_form_heading', 'Get More Details'),
                        'contactFormSubtitle' => get_option('wps_contact_form_subtitle', 'Schedule a tour or request more information about this property.'),
                        'featuredLabel' => get_option('wps_featured_label', 'FEATURED PROPERTY'),
                        'scheduleTourUrl' => get_option('wps_schedule_tour_url', ''),
                        'socialFacebook' => get_option('wps_social_facebook', ''),
                        'socialTwitter' => get_option('wps_social_twitter', ''),
                        'socialLinkedin' => get_option('wps_social_linkedin', ''),
                        'socialInstagram' => get_option('wps_social_instagram', ''),
                        'initialProperties' => wps_get_initial_properties_payload(10),
                        'currentUserId' => get_current_user_id(),
                    )
                );
                wp_add_inline_script(
                    'wps-react',
                    'var propertyPluginData = ' . wp_json_encode($wps_plugin_data) . ';',
                    'before'
                );
                
                // Enqueue CSS if exists
                if ($css_file) {
                    wps_debug_log('WP Property Suite: Enqueuing CSS = ' . $css_file);
                    wp_enqueue_style(
                        'wps-styles',
                        WPS_PLUGIN_URL . 'build/static/css/' . $css_file,
                        array(),
                        WPS_PLUGIN_VERSION
                    );
                }
            } else {
                wps_debug_log('WP Property Suite: No JS files found!');
            }
        } else {
            wps_debug_log('WP Property Suite: Build folder does NOT exist at ' . $build_path);
        }
    } else {
        wps_debug_log('WP Property Suite: Shortcode NOT found on this page');
    }
}

function wps_viewport_meta() {
    echo '<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0">' . "\n";
}

/**
 * Apply the default wide boxed container width on the public frontend.
 */
function wps_default_boxed_container_width() {
    echo '<style>.elementor-section.elementor-section-boxed > .elementor-container { max-width: 1500px !important; }</style>' . "\n";
}
