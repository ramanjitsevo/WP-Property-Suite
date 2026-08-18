<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Meta Boxes for Property Details
 */
function wps_add_meta_boxes() {
    add_meta_box(
        'property_details',
        __('Property Details', 'evo-property-suite'),
        'wps_meta_box_callback',
        'wps_property',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'wps_add_meta_boxes');

/**
 * Enqueue admin assets used by property edit screens.
 */
function wps_admin_property_enqueue_scripts($hook) {
    if (!in_array($hook, array('post.php', 'post-new.php'), true)) {
        return;
    }

    $screen = get_current_screen();
    if (!$screen || $screen->post_type !== 'wps_property') {
        return;
    }

    wps_enqueue_fontawesome();
}
add_action('admin_enqueue_scripts', 'wps_admin_property_enqueue_scripts');

/**
 * Meta Box Callback
 */
function wps_meta_box_callback($post) {
    wp_nonce_field('wps_meta_box', 'wps_meta_box_nonce');
    wp_enqueue_media();

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
    $featured = get_post_meta($post->ID, '_property_featured', true);
    $gallery_ids = get_post_meta($post->ID, '_property_gallery', true);
    $gallery_ids_array = !empty($gallery_ids) ? array_filter(explode(',', $gallery_ids)) : array();
    $agent_name = get_post_meta($post->ID, '_property_agent_name', true);
    $agent_phone = get_post_meta($post->ID, '_property_agent_phone', true);
    $agent_email = get_post_meta($post->ID, '_property_agent_email', true);
    $agent_photo = get_post_meta($post->ID, '_property_agent_photo', true);
    $additional_details_raw = get_post_meta($post->ID, '_property_additional_details', true);
    $additional_details = !empty($additional_details_raw) ? json_decode($additional_details_raw, true) : array();
    $property_faqs_raw = get_post_meta($post->ID, '_property_faqs', true);
    $property_faqs = !empty($property_faqs_raw) ? json_decode($property_faqs_raw, true) : array();

    $google_api_key = get_option('wps_google_api_key', '');

    // Render the meta box HTML (kept identical to original implementation)
    ?>
    <div class="wps-tabs" style="display:flex; gap:16px; align-items:flex-start;">
        <div class="wps-tab-list" style="width:220px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:8px;">
            <button type="button" class="wps-tab-button active" data-tab="basic" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-circle-info" aria-hidden="true"></i><?php _e('Basic', 'evo-property-suite'); ?></button>
            <button type="button" class="wps-tab-button" data-tab="location" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-location-dot" aria-hidden="true"></i><?php _e('Location', 'evo-property-suite'); ?></button>
            <button type="button" class="wps-tab-button" data-tab="features" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-list-check" aria-hidden="true"></i><?php _e('Features', 'evo-property-suite'); ?></button>
            <button type="button" class="wps-tab-button" data-tab="gallery" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-images" aria-hidden="true"></i><?php _e('Gallery', 'evo-property-suite'); ?></button>
            <button type="button" class="wps-tab-button" data-tab="faq" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-circle-question" aria-hidden="true"></i><?php _e('FAQs', 'evo-property-suite'); ?></button>
            <button type="button" class="wps-tab-button" data-tab="additional" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-table-list" aria-hidden="true"></i><?php _e('Additional Details', 'evo-property-suite'); ?></button>
            <button type="button" class="wps-tab-button" data-tab="agent" style="width:100%; padding:10px; text-align:left; border:none; background:transparent; cursor:pointer;"><i class="fas fa-user-tie" aria-hidden="true"></i><?php _e('Agent', 'evo-property-suite'); ?></button>
        </div>

        <div class="wps-tab-content" style="flex:1; background:#fff; border:1px solid #ccd0d4; border-radius:4px; padding:16px;">
            <div class="wps-tab-panel" id="wps-panel-basic">
    <p>
        <label for="property_price"><?php _e('Price:', 'evo-property-suite'); ?></label><br>
        <input type="text" id="property_price" name="property_price" value="<?php echo esc_attr($price); ?>" style="width: 100%;" placeholder="e.g., 500000">
    </p>
    <p>
        <label for="property_area"><?php _e('Area (sq ft):', 'evo-property-suite'); ?></label><br>
        <input type="number" id="property_area" name="property_area" value="<?php echo esc_attr($area); ?>" style="width: 100%;" placeholder="e.g., 1500">
    </p>
    <p>
        <label>
            <input type="checkbox" name="property_featured" value="1" <?php checked($featured, '1'); ?>>
            <?php _e('Mark as Featured Property', 'evo-property-suite'); ?>
        </label>
        <br>
        <span class="description"><?php _e('Featured properties can be displayed with the [wps_featured_properties] shortcode.', 'evo-property-suite'); ?></span>
    </p>
            </div>
    <div class="wps-tab-panel" id="wps-panel-location" style="display:none;">
    <h3><?php _e('Location Details', 'evo-property-suite'); ?></h3>
    
    <p>
        <label for="property_address"><?php _e('Full Address:', 'evo-property-suite'); ?></label><br>
        <input type="text" id="property_address" name="property_address" value="<?php echo esc_attr($address); ?>" style="width: 100%;" placeholder="Start typing to autocomplete..." class="google-autocomplete-address">
        <?php if ($google_api_key): ?>
        <script>
        jQuery(document).ready(function($) {
            window.wpsInitAdminAutocomplete = function() {
                var input = document.getElementById('property_address');
                if (!input || input.dataset.wpsAutocompleteReady === '1' || !window.google || !google.maps || !google.maps.places) {
                    return;
                }
                
                var autocomplete = new google.maps.places.Autocomplete(input, {
                    fields: ['address_components', 'formatted_address', 'geometry', 'name'],
                    types: ['geocode'],
                });

                autocomplete.addListener('place_changed', function() {
                    var place = autocomplete.getPlace();
                    if (!place || !place.address_components) {
                        return;
                    }

                    var components = {};
                    place.address_components.forEach(function(component) {
                        components[component.types[0]] = component.long_name;
                    });

                    if (place.formatted_address) {
                        $('#property_address').val(place.formatted_address);
                    }
                    $('#property_city').val(components.locality || components.sublocality || '');
                    $('#property_state').val(components.administrative_area_level_1 || '');
                    $('#property_zipcode').val(components.postal_code || '');
                    $('#property_country').val(components.country || '');

                    if (place.geometry && place.geometry.location) {
                        $('#property_latitude').val(place.geometry.location.lat());
                        $('#property_longitude').val(place.geometry.location.lng());
                    }
                });

                input.dataset.wpsAutocompleteReady = '1';
            };

            if (window.google && google.maps && google.maps.places) {
                window.wpsInitAdminAutocomplete();
            } else if (!document.getElementById('wps-google-maps-places-admin')) {
                var script = document.createElement('script');
                script.id = 'wps-google-maps-places-admin';
                script.src = 'https://maps.googleapis.com/maps/api/js?key=<?php echo esc_js(rawurlencode($google_api_key)); ?>&libraries=places&callback=wpsInitAdminAutocomplete';
                script.async = true;
                script.defer = true;
                document.head.appendChild(script);
            }
        });
        </script>
        <?php endif; ?>
        <p class="description"><?php _e('Start typing an address and select from suggestions', 'evo-property-suite'); ?></p>
    </p>
    
    <table class="form-table" style="margin-top: 15px;">
        <tr>
            <th><label for="property_city"><?php _e('City:', 'evo-property-suite'); ?></label></th>
            <td><input type="text" id="property_city" name="property_city" value="<?php echo esc_attr($city); ?>" style="width: 100%;" placeholder="City"></td>
        </tr>
        <tr>
            <th><label for="property_state"><?php _e('State:', 'evo-property-suite'); ?></label></th>
            <td><input type="text" id="property_state" name="property_state" value="<?php echo esc_attr($state); ?>" style="width: 100%;" placeholder="State"></td>
        </tr>
        <tr>
            <th><label for="property_zipcode"><?php _e('Zip Code:', 'evo-property-suite'); ?></label></th>
            <td><input type="text" id="property_zipcode" name="property_zipcode" value="<?php echo esc_attr($zipcode); ?>" style="width: 100%;" placeholder="Zip Code"></td>
        </tr>
        <tr>
            <th><label for="property_country"><?php _e('Country:', 'evo-property-suite'); ?></label></th>
            <td><input type="text" id="property_country" name="property_country" value="<?php echo esc_attr($country); ?>" style="width: 100%;" placeholder="Country"></td>
        </tr>
        <tr>
            <th><label for="property_latitude"><?php _e('Latitude:', 'evo-property-suite'); ?></label></th>
            <td>
                <input type="text" id="property_latitude" name="property_latitude" value="<?php echo esc_attr($latitude); ?>" style="width: 100%;" placeholder="e.g., 34.052235">
                <p class="description"><?php _e('Filled automatically when selecting a Google address. Required for the frontend map.', 'evo-property-suite'); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="property_longitude"><?php _e('Longitude:', 'evo-property-suite'); ?></label></th>
            <td>
                <input type="text" id="property_longitude" name="property_longitude" value="<?php echo esc_attr($longitude); ?>" style="width: 100%;" placeholder="e.g., -118.243683">
                <p class="description"><?php _e('Filled automatically when selecting a Google address. Required for the frontend map.', 'evo-property-suite'); ?></p>
            </td>
        </tr>
    </table>
    </div>
    
    <div class="wps-tab-panel" id="wps-panel-features" style="display:none;">
    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">
    
    <p>
        <label for="property_status"><?php _e('Status:', 'evo-property-suite'); ?></label><br>
        <select id="property_status" name="property_status" style="width: 100%;">
            <option value="for-sale" <?php selected($status, 'for-sale'); ?>><?php _e('For Sale', 'evo-property-suite'); ?></option>
            <option value="for-rent" <?php selected($status, 'for-rent'); ?>><?php _e('For Rent', 'evo-property-suite'); ?></option>
            <option value="sold" <?php selected($status, 'sold'); ?>><?php _e('Sold', 'evo-property-suite'); ?></option>
            <option value="rented" <?php selected($status, 'rented'); ?>><?php _e('Rented', 'evo-property-suite'); ?></option>
        </select>
    
    </div>

    <div class="wps-tab-panel" id="wps-panel-gallery" style="display:none;">
    <h3><?php _e('Property Gallery', 'evo-property-suite'); ?></h3>
    <p class="description" style="margin-bottom: 12px;">
        <?php _e('Select multiple images from the media library to display below the featured image on the single property page.', 'evo-property-suite'); ?>
    </p>

    <div id="property-gallery-preview" style="display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
        <?php foreach ($gallery_ids_array as $att_id):
            $thumb_url = wp_get_attachment_image_url(intval($att_id), 'thumbnail');
            if ($thumb_url): ?>
            <div class="wps-gallery-thumb" data-id="<?php echo esc_attr($att_id); ?>"
                 style="position: relative; width: 82px; height: 82px; border: 1px solid #ccd0d4; border-radius: 4px; overflow: hidden; cursor: move;">
                <img src="<?php echo esc_url($thumb_url); ?>" style="width:100%; height:100%; object-fit:cover; display:block;">
                <button type="button" class="wps-remove-gallery-thumb"
                        data-id="<?php echo esc_attr($att_id); ?>"
                        style="position:absolute; top:2px; right:2px; background:rgba(0,0,0,0.72); color:#fff; border:none; border-radius:50%; width:20px; height:20px; cursor:pointer; line-height:18px; font-size:15px; padding:0;">×</button>
            </div>
        <?php endif; endforeach; ?>
    </div>

    <input type="hidden" id="property_gallery_ids" name="property_gallery_ids"
           value="<?php echo esc_attr(implode(',', $gallery_ids_array)); ?>">

    <button type="button" id="wps-gallery-btn" class="button button-secondary">
        <span class="dashicons dashicons-format-gallery" style="margin-right: 5px;"></span>
        <?php _e('Select Gallery Images', 'evo-property-suite'); ?>
    </button>
    </div>

    <div class="wps-tab-panel" id="wps-panel-agent" style="display:none;">
    <hr style="margin: 20px 0; border: 0; border-top: 1px solid #ccc;">

    <h3><?php _e('Per-Property Agent (optional)', 'evo-property-suite'); ?></h3>
    <p class="description"><?php _e('Set an agent specifically for this property. These values override the global settings on the frontend when present.', 'evo-property-suite'); ?></p>

    <table class="form-table">
        <tr>
            <th><label for="property_agent_name"><?php _e('Agent Name:', 'evo-property-suite'); ?></label></th>
            <td><input type="text" id="property_agent_name" name="property_agent_name" value="<?php echo esc_attr($agent_name); ?>" style="width:100%;" placeholder="Agent Name"></td>
        </tr>
        <tr>
            <th><label for="property_agent_phone"><?php _e('Agent Phone:', 'evo-property-suite'); ?></label></th>
            <td><input type="text" id="property_agent_phone" name="property_agent_phone" value="<?php echo esc_attr($agent_phone); ?>" style="width:100%;" placeholder="+1 (555) 123-4567"></td>
        </tr>
        <tr>
            <th><label for="property_agent_email"><?php _e('Agent Email:', 'evo-property-suite'); ?></label></th>
            <td><input type="email" id="property_agent_email" name="property_agent_email" value="<?php echo esc_attr($agent_email); ?>" style="width:100%;" placeholder="agent@example.com"></td>
        </tr>
        <tr>
            <th><label for="property_agent_photo"><?php _e('Agent Photo:', 'evo-property-suite'); ?></label></th>
            <td>
                <div class="image-upload-container" style="text-align:left;">
                    <img id="property_agent_photo_preview"
                         src="<?php echo esc_url( $agent_photo ); ?>"
                         style="<?php echo $agent_photo ? 'display:block;' : 'display:none;'; ?>width:80px; height:80px; border-radius:50%; object-fit:cover; margin-bottom:10px;" />
                    <br/>
                    <input type="hidden" id="property_agent_photo" name="property_agent_photo" value="<?php echo esc_attr($agent_photo); ?>" />
                    <button type="button" class="button wps-upload-img" data-target="property_agent_photo"><?php _e('Upload Photo', 'evo-property-suite'); ?></button>
                    <button type="button" class="button wps-remove-img" data-target="property_agent_photo" <?php echo $agent_photo ? 'style="display:block;"' : 'style="display:none;"'; ?>><?php _e('Remove', 'evo-property-suite'); ?></button>
                </div>
            </td>
        </tr>
    </table>
    </div>

    <div class="wps-tab-panel" id="wps-panel-faq" style="display:none;">
    <h3><?php _e('Property FAQs', 'evo-property-suite'); ?></h3>
    <p class="description"><?php _e('Add frequently asked questions for this property. These will appear in the FAQ tab on the frontend.', 'evo-property-suite'); ?></p>

    <div id="property-faqs-container" style="margin-bottom:12px;">
        <?php if (!empty($property_faqs) && is_array($property_faqs)): foreach ($property_faqs as $idx => $item): ?>
            <div class="property-faq-row" data-index="<?php echo esc_attr($idx); ?>" style="display:grid; grid-template-columns:1fr; gap:8px; margin-bottom:12px; padding:12px; border:1px solid #dcdcde; border-radius:4px; background:#f6f7f7;">
                <input type="text" name="faq_question[]" value="<?php echo esc_attr($item['question'] ?? ''); ?>" placeholder="Question" style="width:100%;" />
                <textarea name="faq_answer[]" placeholder="Answer" rows="3" style="width:100%;"><?php echo esc_textarea($item['answer'] ?? ''); ?></textarea>
                <button type="button" class="button remove-property-faq" style="justify-self:start;">Remove FAQ</button>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <button type="button" id="add-property-faq" class="button">Add FAQ</button>
    </div>

    <div class="wps-tab-panel" id="wps-panel-additional" style="display:none;">
    <h3><?php _e('Additional Details (repeatable)', 'evo-property-suite'); ?></h3>
    <p class="description"><?php _e('Add any extra labeled details (e.g., Year Built, Parking) that will show in the Additional Detail tab on the frontend.', 'evo-property-suite'); ?></p>

    <div id="additional-details-container" style="margin-bottom:12px;">
        <?php if (!empty($additional_details) && is_array($additional_details)): foreach ($additional_details as $idx => $item): ?>
            <div class="additional-detail-row" data-index="<?php echo esc_attr($idx); ?>" style="display:flex; gap:8px; margin-bottom:8px;">
                <input type="text" name="additional_label[]" value="<?php echo esc_attr($item['label'] ?? ''); ?>" placeholder="Label (e.g., Year Built)" style="flex:1;" />
                <input type="text" name="additional_value[]" value="<?php echo esc_attr($item['value'] ?? ''); ?>" placeholder="Value" style="width:220px;" />
                <button type="button" class="button remove-additional">Remove</button>
            </div>
        <?php endforeach; endif; ?>
    </div>
    <button type="button" id="add-additional-detail" class="button">Add Detail</button>
    </div>
        </div>
    </div>

    <style>
    .wps-tab-list .wps-tab-button.active { background:#f0f6fc; color:#2271b1; font-weight:600; border-left:3px solid #2271b1; }
    .wps-tab-list .wps-tab-button { border-left:3px solid transparent; display:flex; align-items:center; gap:9px; }
    .wps-tab-list .wps-tab-button i { width:16px; color:#646970; text-align:center; }
    .wps-tab-list .wps-tab-button.active i { color:#2271b1; }
    
    /* Responsive Admin Meta Box */
    @media (max-width: 782px) {
      .wps-tabs {flex-direction: column !important;gap: 10px !important;}      
      .wps-tab-list { width: 100% !important;display: flex !important;flex-wrap: wrap !important; gap: 5px !important; padding: 10px !important; }      
      .wps-tab-button {flex: 1 1 auto !important; min-width: 100px !important; padding: 8px 12px !important; font-size: 13px !important; text-align: center !important; border: 1px solid #ccd0d4 !important; border-radius: 4px !important; border-left: none !important; }      
      .wps-tab-list .wps-tab-button.active { border-left: none !important; border-bottom: 3px solid #2271b1 !important; }      
      .wps-tab-content {padding: 12px !important;}      
      .additional-detail-row {flex-direction: column !important; gap: 5px !important; }      
      .additional-detail-row input { width: 100% !important; }      
      .property-faq-row {padding: 10px !important; }      
      .property-faq-row input,
      .property-faq-row textarea {font-size: 14px !important; }
    }
    
    @media (max-width: 480px) {
      .wps-tab-button { min-width: 80px !important; font-size: 12px !important; padding: 6px 8px !important; }      
      .wps-tab-content input,
      .wps-tab-content textarea,
      .wps-tab-content select { font-size: 14px !important;  }
    }
    </style>

    <script>
    // Define renderGalleryPreview BEFORE jQuery ready so it's available globally
    window.renderGalleryPreview = function() {
        var ids = jQuery('#property_gallery_ids').val().split(',').filter(Boolean);
        var $preview = jQuery('#property-gallery-preview').empty();

        if (ids.length === 0) return;

        // Use AJAX to fetch thumbnail URLs
        jQuery.post(ajaxurl, {
            action: 'pp_get_gallery_thumbs',
            ids: ids.join(','),
            nonce: '<?php echo wp_create_nonce('pp_gallery_nonce'); ?>'
        }, function(response) {
            if (!response.success) return;
            response.data.forEach(function(item) {
                $preview.append(
                    '<div class="wps-gallery-thumb" data-id="' + item.id + '"' +
                    ' style="position:relative; width:82px; height:82px; border:1px solid #ccd0d4; border-radius:4px; overflow:hidden;">' +
                    '<img src="' + item.thumb + '" style="width:100%; height:100%; object-fit:cover; display:block;">' +
                    '<button type="button" class="wps-remove-gallery-thumb" data-id="' + item.id + '"' +
                    ' style="position:absolute; top:2px; right:2px; background:rgba(0,0,0,0.72); color:#fff; border:none;"' +
                    ' border-radius:50%; width:20px; height:20px; cursor:pointer; line-height:18px; font-size:15px; padding:0;">×</button>' +
                    '</div>'
                );
            });
        });
    };

    jQuery(document).ready(function($) {
            // Tab switching
            $('.wps-tab-button').on('click', function() {
                var tab = $(this).data('tab');
                $('.wps-tab-button').removeClass('active');
                $(this).addClass('active');
                $('.wps-tab-panel').hide();
                $('#wps-panel-' + tab).show();
            });

            // Ensure initial gallery preview is rendered if needed
            if (typeof renderGalleryPreview === 'function') {
                renderGalleryPreview();
            }
        var ppMediaFrame;
        var wpsPropertyMediaFrame;

        $('#wps-gallery-btn').on('click', function(e) {
            e.preventDefault();

            if (ppMediaFrame) { ppMediaFrame.open(); return; }

            ppMediaFrame = wp.media({
                title: '<?php _e('Select Gallery Images', 'evo-property-suite'); ?>',
                button: { text: '<?php _e('Add to Gallery', 'evo-property-suite'); ?>' },
                multiple: true,
                library: { type: 'image' }
            });

            ppMediaFrame.on('select', function() {
                var attachments = ppMediaFrame.state().get('selection').toJSON();
                var currentIds = $('#property_gallery_ids').val().split(',').filter(Boolean);

                attachments.forEach(function(att) {
                    if (currentIds.indexOf(String(att.id)) === -1) {
                        currentIds.push(String(att.id));
                    }
                });

                $('#property_gallery_ids').val(currentIds.join(','));
                renderGalleryPreview();
            });

            ppMediaFrame.open();
        });

        $('#property-gallery-preview').on('click', '.wps-remove-gallery-thumb', function(e) {
            e.preventDefault();
            var removeId = String($(this).data('id'));
            var ids = $('#property_gallery_ids').val().split(',').filter(Boolean);
            ids = ids.filter(function(id) { return id !== removeId; });
            $('#property_gallery_ids').val(ids.join(','));
            $(this).closest('.wps-gallery-thumb').remove();
        });

        // Generic image uploader for property agent photo.
        $(document).on('click', '.wps-upload-img', function(e) {
            e.preventDefault();

            var $button = $(this);
            var target = $button.data('target');

            if (!target) {
                return;
            }

            wpsPropertyMediaFrame = wp.media({
                title: '<?php esc_attr_e('Select Image', 'evo-property-suite'); ?>',
                button: { text: '<?php esc_attr_e('Use this image', 'evo-property-suite'); ?>' },
                multiple: false,
                library: { type: 'image' }
            });

            wpsPropertyMediaFrame.on('select', function() {
                var attachment = wpsPropertyMediaFrame.state().get('selection').first().toJSON();
                $('#' + target).val(attachment.url);
                $('#' + target + '_preview').attr('src', attachment.url).show();
                $button.siblings('.wps-remove-img').show();
            });

            wpsPropertyMediaFrame.open();
        });

        $(document).on('click', '.wps-remove-img', function(e) {
            e.preventDefault();

            var target = $(this).data('target');
            if (!target) {
                return;
            }

            $('#' + target).val('');
            $('#' + target + '_preview').attr('src', '').hide();
            $(this).hide();
        });

            // Additional details repeatable
            $('#add-additional-detail').on('click', function(e) {
                e.preventDefault();
                var idx = $('#additional-details-container .additional-detail-row').length;
                var $row = $('<div class="additional-detail-row" data-index="' + idx + '" style="display:flex; gap:8px; margin-bottom:8px;">'
                    + '<input type="text" name="additional_label[]" placeholder="Label (e.g., Year Built)" style="flex:1;" />'
                    + '<input type="text" name="additional_value[]" placeholder="Value" style="width:220px;" />'
                    + '<button type="button" class="button remove-additional">Remove</button>'
                    + '</div>');
                $('#additional-details-container').append($row);
            });

            $(document).on('click', '.remove-additional', function(e) {
                e.preventDefault();
                $(this).closest('.additional-detail-row').remove();
            });

            // Property FAQs repeatable
            $('#add-property-faq').on('click', function(e) {
                e.preventDefault();
                var idx = $('#property-faqs-container .property-faq-row').length;
                var $row = $('<div class="property-faq-row" data-index="' + idx + '" style="display:grid; grid-template-columns:1fr; gap:8px; margin-bottom:12px; padding:12px; border:1px solid #dcdcde; border-radius:4px; background:#f6f7f7;">'
                    + '<input type="text" name="faq_question[]" placeholder="Question" style="width:100%;" />'
                    + '<textarea name="faq_answer[]" placeholder="Answer" rows="3" style="width:100%;"></textarea>'
                    + '<button type="button" class="button remove-property-faq" style="justify-self:start;">Remove FAQ</button>'
                    + '</div>');
                $('#property-faqs-container').append($row);
            });

            $(document).on('click', '.remove-property-faq', function(e) {
                e.preventDefault();
                $(this).closest('.property-faq-row').remove();
            });
    });
    </script>
    <?php
}

/**
 * Save Meta Box Data
 */
function wps_save_meta_boxes($post_id) {
    if (!isset($_POST['wps_meta_box_nonce'])) {
        return;
    }
    
    $nonce = sanitize_text_field(wp_unslash($_POST['wps_meta_box_nonce']));
    if (!wp_verify_nonce($nonce, 'wps_meta_box')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (isset($_POST['property_price'])) {
        update_post_meta($post_id, '_property_price', sanitize_text_field(wp_unslash($_POST['property_price'])));
    }
    
    if (isset($_POST['property_area'])) {
        update_post_meta($post_id, '_property_area', sanitize_text_field(wp_unslash($_POST['property_area'])));
    }
    
    if (isset($_POST['property_address'])) {
        update_post_meta($post_id, '_property_address', sanitize_text_field(wp_unslash($_POST['property_address'])));
    }
    
    if (isset($_POST['property_city'])) {
        update_post_meta($post_id, '_property_city', sanitize_text_field(wp_unslash($_POST['property_city'])));
    }
    
    if (isset($_POST['property_state'])) {
        update_post_meta($post_id, '_property_state', sanitize_text_field(wp_unslash($_POST['property_state'])));
    }
    
    if (isset($_POST['property_zipcode'])) {
        update_post_meta($post_id, '_property_zipcode', sanitize_text_field(wp_unslash($_POST['property_zipcode'])));
    }
    
    if (isset($_POST['property_country'])) {
        update_post_meta($post_id, '_property_country', sanitize_text_field(wp_unslash($_POST['property_country'])));
    }

    if (isset($_POST['property_latitude'])) {
        $latitude = sanitize_text_field(wp_unslash($_POST['property_latitude']));
        if ($latitude !== '' && is_numeric($latitude)) {
            update_post_meta($post_id, '_property_latitude', $latitude);
        } else {
            delete_post_meta($post_id, '_property_latitude');
        }
    }

    if (isset($_POST['property_longitude'])) {
        $longitude = sanitize_text_field(wp_unslash($_POST['property_longitude']));
        if ($longitude !== '' && is_numeric($longitude)) {
            update_post_meta($post_id, '_property_longitude', $longitude);
        } else {
            delete_post_meta($post_id, '_property_longitude');
        }
    }
    
    if (isset($_POST['property_status'])) {
        update_post_meta($post_id, '_property_status', sanitize_text_field(wp_unslash($_POST['property_status'])));
    }

    if (isset($_POST['property_featured']) && sanitize_text_field(wp_unslash($_POST['property_featured'])) === '1') {
        update_post_meta($post_id, '_property_featured', '1');
    } else {
        delete_post_meta($post_id, '_property_featured');
    }

    if (isset($_POST['property_gallery_ids'])) {
        $raw_ids = sanitize_text_field(wp_unslash($_POST['property_gallery_ids']));
        $clean_ids = array_filter(array_map('absint', explode(',', $raw_ids)));
        update_post_meta($post_id, '_property_gallery', implode(',', $clean_ids));
    }

    // Per-property agent fields
    if (isset($_POST['property_agent_name'])) {
        update_post_meta($post_id, '_property_agent_name', sanitize_text_field(wp_unslash($_POST['property_agent_name'])));
    }
    if (isset($_POST['property_agent_phone'])) {
        update_post_meta($post_id, '_property_agent_phone', sanitize_text_field(wp_unslash($_POST['property_agent_phone'])));
    }
    if (isset($_POST['property_agent_email'])) {
        update_post_meta($post_id, '_property_agent_email', sanitize_email(wp_unslash($_POST['property_agent_email'])));
    }
    if (isset($_POST['property_agent_photo'])) {
        update_post_meta($post_id, '_property_agent_photo', esc_url_raw(wp_unslash($_POST['property_agent_photo'])));
    }

    // Property FAQs (repeatable)
    if (isset($_POST['faq_question']) && is_array($_POST['faq_question'])) {
        $questions = array_map('sanitize_text_field', wp_unslash($_POST['faq_question']));
        $answers = isset($_POST['faq_answer']) && is_array($_POST['faq_answer']) ? array_map('sanitize_textarea_field', wp_unslash($_POST['faq_answer'])) : array();
        $combined = array();
        for ($i = 0; $i < count($questions); $i++) {
            $question = trim($questions[$i]);
            $answer = isset($answers[$i]) ? trim($answers[$i]) : '';
            if ($question !== '' || $answer !== '') {
                $combined[] = array('question' => $question, 'answer' => $answer);
            }
        }
        if (!empty($combined)) {
            update_post_meta($post_id, '_property_faqs', wp_json_encode($combined));
        } else {
            delete_post_meta($post_id, '_property_faqs');
        }
    } else {
        delete_post_meta($post_id, '_property_faqs');
    }

    // Additional details (repeatable)
    if (isset($_POST['additional_label']) && is_array($_POST['additional_label'])) {
        $labels = array_map('sanitize_text_field', wp_unslash($_POST['additional_label']));
        $values = isset($_POST['additional_value']) && is_array($_POST['additional_value']) ? array_map('sanitize_text_field', wp_unslash($_POST['additional_value'])) : array();
        $combined = array();
        for ($i = 0; $i < count($labels); $i++) {
            $label = trim($labels[$i]);
            $value = isset($values[$i]) ? trim($values[$i]) : '';
            if ($label !== '' || $value !== '') {
                $combined[] = array('label' => $label, 'value' => $value);
            }
        }
        if (!empty($combined)) {
            update_post_meta($post_id, '_property_additional_details', wp_json_encode($combined));
        } else {
            delete_post_meta($post_id, '_property_additional_details');
        }
    }
}
add_action('save_post', 'wps_save_meta_boxes');

/**
 * Display admin notice after plugin activation
 */
function wps_admin_notice() {
    if (!get_transient('wps_show_activation_notice')) {
        return;
    }
    
    if (get_user_meta(get_current_user_id(), 'wps_notice_dismissed', true)) {
        delete_transient('wps_show_activation_notice');
        return;
    }
    
    $guide_url = admin_url('admin.php?page=wps-guide');
    $settings_url = admin_url('admin.php?page=wps-settings');
    ?>
    <style>
        .wps-activation-notice .button .dashicons {
            line-height: 1;
            vertical-align: middle;
            margin-top: -1px;
        }
    </style>
    <div class="notice notice-success is-dismissible wps-activation-notice" id="wps-notice">
        <div style="display:flex; align-items:flex-start; gap:15px; padding:8px 0;">
            <div style="flex-shrink:0;">
                <span class="dashicons dashicons-building" style="font-size:40px; color:#2271b1; width:40px; height:40px;"></span>
            </div>
            <div style="flex:1;">
                <h2 style="margin:0 0 8px; color:#1d2327;">WP Property Suite is Ready! 🏠</h2>
                <p style="margin:0 0 12px; font-size:14px; color:#3c434a;">
                    Your property listing plugin has been activated with <strong>10 sample properties</strong> and <strong>pre-configured settings</strong>. Here's how to get started:
                </p>
                <div style="background:#f0f6fc; border-left:4px solid #2271b1; padding:12px 15px; margin-bottom:12px;">
                    <p style="margin:0 0 8px; font-weight:600; font-size:14px;">✓ Sample Data Installed</p>
                    <p style="margin:0; font-size:13px;">
                        10 demo properties with images, details, FAQs, and agent information have been added. All settings (banner, colors, text, CTA) are pre-filled and ready to use.
                    </p>
                </div>
                <div style="background:#f0f6fc; border-left:4px solid #2271b1; padding:12px 15px; margin-bottom:12px;">
                    <p style="margin:0 0 8px; font-weight:600; font-size:14px;">Step 1: Add the shortcode to any page</p>
                    <p style="margin:0; font-size:13px;">
                        Copy <code style="background:#fff; padding:3px 8px; border:1px solid #c3c4c7; font-size:13px;">[wps_search]</code> and paste it into any WordPress page to display your property listings with banner, search, filters, and cards.
                    </p>
                </div>
                <div style="background:#f0f6fc; border-left:4px solid #2271b1; padding:12px 15px; margin-bottom:12px;">
                    <p style="margin:0 0 8px; font-weight:600; font-size:14px;">Step 2: Customize your settings (Optional)</p>
                    <p style="margin:0; font-size:13px;">
                        Go to <strong>WP Property Suite Settings</strong> to change the banner image, colors, header text, card layout, and more. Everything is already set up with professional defaults!
                    </p>
                </div>
                <p style="margin:0;">
                    <a href="<?php echo esc_url($guide_url); ?>" class="button button-primary" style="margin-right:8px;">
                        <span class="dashicons dashicons-book" style="vertical-align:middle; margin-right:4px;"></span>View Shortcode Guide
                    </a>
                    <a href="<?php echo esc_url($settings_url); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-admin-generic" style="vertical-align:middle; margin-right:4px;"></span>Open Settings
                    </a>
                    <button type="button" class="button-link wps-dismiss-notice" style="margin-left:15px; color:#787c82; text-decoration:underline;">
                        Don't show this again
                    </button>
                </p>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Dismiss button in the notice header (X button)
        $(document).on('click', '#wps-notice .notice-dismiss', function() {
            $.post(ajaxurl, {
                action: 'wps_dismiss_notice',
                nonce: '<?php echo wp_create_nonce('wps_dismiss_notice'); ?>'
            });
        });
        // "Don't show this again" link
        $(document).on('click', '.wps-dismiss-notice', function(e) {
            e.preventDefault();
            $('#wps-notice').fadeOut();
            $.post(ajaxurl, {
                action: 'wps_dismiss_notice',
                nonce: '<?php echo wp_create_nonce('wps_dismiss_notice'); ?>'
            });
        });
    });
    </script>
    <?php
}

add_action('admin_notices', 'wps_admin_notice');

/**
 * AJAX handler to dismiss the activation notice permanently
 */
function wps_dismiss_notice_ajax() {
    check_ajax_referer('wps_dismiss_notice', 'nonce');
    update_user_meta(get_current_user_id(), 'wps_notice_dismissed', true);
    delete_transient('wps_show_activation_notice');
    wp_send_json_success();
}
add_action('wp_ajax_wps_dismiss_notice', 'wps_dismiss_notice_ajax');

/**
 * AJAX: Return thumbnail URLs for gallery preview in admin meta box
 */
function wps_get_gallery_thumbs_ajax() {
    check_ajax_referer('pp_gallery_nonce', 'nonce');

    $ids = isset($_POST['ids']) ? array_filter(array_map('absint', explode(',', $_POST['ids']))) : array();
    $result = array();

    foreach ($ids as $id) {
        $thumb = wp_get_attachment_image_url($id, 'thumbnail');
        if ($thumb) {
            $result[] = array('id' => $id, 'thumb' => $thumb);
        }
    }

    wp_send_json_success($result);
}
add_action('wp_ajax_pp_get_gallery_thumbs', 'wps_get_gallery_thumbs_ajax');
