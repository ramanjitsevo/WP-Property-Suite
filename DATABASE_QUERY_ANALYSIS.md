# Database Query Analysis - WP-PROPERTY-SUITE

**Date**: August 13, 2026  
**Focus**: N+1 Query Problems, Optimization Opportunities

---

## QUERY PATTERN ANALYSIS

### Current Query Flow: Property List Display

When displaying a list of 12 properties (via REST API or shortcode):

```
Initial Query:
  get_posts( array( 'posts_per_page' => 12 ) )
  Result: 1 query ✓

For Each Property (12 times):
  wps_build_property_data( $post )
    ├─ get_post_meta( '_property_price' )         // Query 1 per property
    ├─ get_post_meta( '_property_area' )          // Query 2 per property
    ├─ get_post_meta( '_property_address' )       // Query 3 per property
    ├─ get_post_meta( '_property_city' )          // Query 4 per property
    ├─ get_post_meta( '_property_state' )         // Query 5 per property
    ├─ get_post_meta( '_property_zipcode' )       // Query 6 per property
    ├─ get_post_meta( '_property_country' )       // Query 7 per property
    ├─ get_post_meta( '_property_latitude' )      // Query 8 per property
    ├─ get_post_meta( '_property_longitude' )     // Query 9 per property
    ├─ get_post_meta( '_property_status' )        // Query 10 per property
    ├─ get_post_meta( '_property_gallery' )       // Query 11 per property
    ├─ get_post_meta( '_property_thumbnail_url' ) // Query 12 per property
    ├─ get_post_meta( '_property_gallery_urls' )  // Query 13 per property
    ├─ get_post_meta( '_property_additional_details' )  // Query 14 per property
    ├─ get_post_meta( '_property_faqs' )          // Query 15 per property
    ├─ get_post_meta( '_property_agent_name' )    // Query 16 per property
    ├─ get_post_meta( '_property_agent_phone' )   // Query 17 per property
    ├─ get_post_meta( '_property_agent_email' )   // Query 18 per property
    ├─ get_post_meta( '_property_agent_photo' )   // Query 19 per property
    ├─ wp_get_post_terms( 'property-type' )       // Query 20 per property
    ├─ wp_get_post_terms( 'property-location' )   // Query 21 per property
    ├─ wp_get_post_terms( 'bedrooms' )            // Query 22 per property
    ├─ wp_get_post_terms( 'bathrooms' )           // Query 23 per property
    ├─ wp_get_post_terms( 'property-floor' )      // Query 24 per property
    └─ For each custom taxonomy (N times)
       └─ wp_get_post_terms( custom_tax )         // Query 25+ per property
```

**Total Queries for 12 properties**:
- Base: 1 initial query
- Per property: 25+ queries
- **Total: 1 + (12 × 25) = 301+ queries** for a simple list view

---

## DUPLICATE QUERIES

### Issue: Meta queries in admin.php AND helpers.php

**admin/admin.php** (lines 45-63):
```php
$price = get_post_meta($post->ID, '_property_price', true);
$area = get_post_meta($post->ID, '_property_area', true);
$address = get_post_meta($post->ID, '_property_address', true);
$city = get_post_meta($post->ID, '_property_city', true);
$state = get_post_meta($post->ID, '_property_state', true);
$zipcode = get_post_meta($post->ID, '_property_zipcode', true);
$country = get_post_meta($post->ID, '_property_country', true);
// ... more meta fetches
```

**includes/helpers.php** (lines 98-104):
```php
$price = get_post_meta($post->ID, '_property_price', true);
$area = get_post_meta($post->ID, '_property_area', true);
$address = get_post_meta($post->ID, '_property_address', true);
$city = get_post_meta($post->ID, '_property_city', true);
$state = get_post_meta($post->ID, '_property_state', true);
$zipcode = get_post_meta($post->ID, '_property_zipcode', true);
$country = get_post_meta($post->ID, '_property_country', true);
```

**Problem**: Same meta keys fetched in two places, creating maintenance burden and potential for inconsistency.

---

### Issue: Taxonomy queries in multiple locations

**frontend.php** - `wps_get_recent_property_features()` (lines 489-500):
```php
$bedrooms = wp_get_post_terms($property_id, 'bedrooms', ...);
$bathrooms = wp_get_post_terms($property_id, 'bathrooms', ...);
$floors = wp_get_post_terms($property_id, 'property-floor', ...);
```

Also fetched in **helpers.php** - `wps_build_property_data()` (lines 86-91):
```php
$property_types = wp_get_post_terms($post->ID, 'property-type', ...);
$locations = wp_get_post_terms($post->ID, 'property-location', ...);
$bedrooms = wp_get_post_terms($post->ID, 'bedrooms', ...);
$bathrooms = wp_get_post_terms($post->ID, 'bathrooms', ...);
$floors = wp_get_post_terms($post->ID, 'property-floor', ...);
```

**Result**: When rendering recent properties cards, terms are fetched twice per property:
1. First in `wps_build_property_data()` (not used in card)
2. Again in `wps_get_recent_property_features()` (actually used in card)

---

## INEFFICIENT PATTERNS

### Pattern #1: Gallery URL Processing (DUPLICATE WORK)

**Current Flow** (helpers.php lines 113-127):
```php
// First attempt: fetch from attachment IDs
$gallery = array();
$gallery_ids_raw = get_post_meta($post->ID, '_property_gallery', true);
if (!empty($gallery_ids_raw)) {
    $gallery_ids = array_filter(explode(',', $gallery_ids_raw));
    foreach ($gallery_ids as $att_id) {
        $att_id = intval(trim($att_id));
        if ($att_id > 0) {
            $url = wp_get_attachment_image_url($att_id, 'large');  // Query
            if ($url) {
                $gallery[] = $url;
            }
        }
    }
}

// If first failed, fetch from stored URLs
if (empty($gallery)) {
    $gallery_urls_raw = get_post_meta($post->ID, '_property_gallery_urls', true);
    if (!empty($gallery_urls_raw)) {
        $gallery_urls = json_decode($gallery_urls_raw, true);
        if (is_array($gallery_urls)) {
            $gallery = $gallery_urls;
        }
    }
}
```

**Problems**:
- Two meta fetches for gallery
- If ID-based gallery empty, falls back to URL-based (redundant)
- Attachment queries inside loop
- Same gallery processed in both `wps_build_property_data()` and `wps_render_recent_property_card()`

---

### Pattern #2: Custom Taxonomies Loop

**Location**: helpers.php lines 93-101

```php
foreach ($custom_taxonomies as $tax) {
    if (isset($tax['slug']) && taxonomy_exists($tax['slug'])) {
        $terms = wp_get_post_terms($post->ID, $tax['slug'], ...);
        $custom_taxonomy_data[$tax['slug']] = !empty($terms) ? $terms[0] : 'N/A';
    }
}
```

**Problem**: 
- `get_option('wps_custom_taxonomies')` called for every property
- Each custom taxonomy adds 1 query per property
- If 5 custom taxonomies exist: 5 extra queries per property
- For 12 properties: 60 additional queries just for custom taxonomies

---

### Pattern #3: Asset Loading Inefficiency

**Current Flow** (frontend.php, helpers.php):

```
wps_enqueue_assets()
  → wps_enqueue_fontawesome()  // Loads 40KB+ CSS everywhere

wps_enqueue_recent_properties_assets()
  → wps_enqueue_fontawesome()  // Loads 40KB+ CSS again!

wps_admin_property_enqueue_scripts()
  → wps_enqueue_fontawesome()  // Loads 40KB+ CSS in admin!
```

**Problem**: Font Awesome loaded 3+ times on same page via multiple enqueue calls.

---

## WORDPRESS CACHING IMPLICATIONS

WordPress has built-in meta caching via `wp_cache_*` functions, but:

1. **Transient Cache**: Not enabled by default unless object cache plugin installed
2. **Query Caching**: `get_post_meta()` calls are cached per-request, but not across requests
3. **Taxonomy Cache**: `wp_get_post_terms()` caches within request

**Result**: The 25+ queries per property happen once per page load but could be reduced to 2-3 queries.

---

## OPTIMIZATION RECOMMENDATIONS

### Priority 1: Batch Meta Fetching

**Current**: 19 separate `get_post_meta()` calls per property

**Optimized**: Single function that fetches all meta at once
```php
function wps_get_batch_property_meta( $post_id ) {
    global $wpdb;
    
    $meta_keys = array(
        '_property_price', '_property_area', '_property_address',
        '_property_city', '_property_state', '_property_zipcode',
        '_property_country', '_property_latitude', '_property_longitude',
        '_property_status', '_property_featured', '_property_gallery',
        '_property_thumbnail_url', '_property_gallery_urls',
        '_property_additional_details', '_property_faqs',
        '_property_agent_name', '_property_agent_phone',
        '_property_agent_email', '_property_agent_photo'
    );
    
    $meta_results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, meta_value FROM $wpdb->postmeta 
             WHERE post_id = %d AND meta_key IN (" . implode(',', array_fill(0, count($meta_keys), '%s')) . ")",
            array_merge([$post_id], $meta_keys)
        )
    );
    
    $meta_data = array();
    foreach ($meta_results as $row) {
        $meta_data[$row->meta_key] = $row->meta_value;
    }
    
    return $meta_data;
}
```

**Impact**: 19 queries → 1 query per property (95% reduction)

---

### Priority 2: Batch Taxonomy Fetching

**Current**: 5 taxonomy calls + N custom taxonomy calls per property

**Optimized**:
```php
function wps_get_batch_post_terms( $post_id, $taxonomies ) {
    global $wpdb;
    
    $terms_result = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT tr.name, t.taxonomy FROM {$wpdb->term_relationships} tr
             JOIN {$wpdb->terms} t ON tr.term_id = t.term_id
             JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             WHERE tr.object_id = %d AND tt.taxonomy IN (" . implode(',', array_fill(0, count($taxonomies), '%s')) . ")",
            array_merge([$post_id], $taxonomies)
        )
    );
    
    $terms_by_taxonomy = array();
    foreach ($terms_result as $row) {
        if (!isset($terms_by_taxonomy[$row->taxonomy])) {
            $terms_by_taxonomy[$row->taxonomy] = array();
        }
        $terms_by_taxonomy[$row->taxonomy][] = $row->name;
    }
    
    return $terms_by_taxonomy;
}
```

**Impact**: 5+ queries → 1 query per property (80% reduction)

---

### Priority 3: Eliminate Gallery Duplication

**Current**: Gallery fetched in `wps_build_property_data()` and `wps_render_recent_property_card()`

**Optimized**: Fetch once, return in property data

**Impact**: Eliminates redundant processing

---

### Priority 4: Conditional Asset Loading

**Current**: Font Awesome loaded unconditionally on every page

**Optimized**:
```php
function wps_enqueue_fontawesome_conditional() {
    // Only load on pages with property shortcodes
    global $post;
    
    $needs_icons = false;
    
    // Check if admin
    if (is_admin()) {
        $screen = get_current_screen();
        if ($screen && $screen->post_type === 'wps_property') {
            $needs_icons = true;
        }
    }
    
    // Check if page has property shortcodes
    if ($post && has_shortcode($post->post_content, 'wps_')) {
        $needs_icons = true;
    }
    
    // Only enqueue if needed
    if ($needs_icons) {
        wp_enqueue_style('wps-fontawesome', ...);
    }
}
```

**Impact**: Saves 40KB+ CSS on non-property pages (100% reduction for those pages)

---

### Priority 5: Cache Custom Taxonomies List

**Current**: `get_option('wps_custom_taxonomies')` called for every property

**Optimized**:
```php
$custom_taxonomies = wp_cache_get('wps_custom_taxonomies');
if (false === $custom_taxonomies) {
    $custom_taxonomies = get_option('wps_custom_taxonomies', array());
    wp_cache_set('wps_custom_taxonomies', $custom_taxonomies, '', 3600);
}
```

**Impact**: Option call cached per-request

---

## QUERY REDUCTION SUMMARY

| Scenario | Current Queries | Optimized | Savings |
|----------|-----------------|-----------|---------|
| Single property REST call | ~25 | ~3 | 88% |
| 12 properties on REST endpoint | ~301 | ~40 | 87% |
| 6 properties in shortcode | ~150 | ~20 | 87% |
| Property admin page load | ~35 | ~10 | 71% |

---

## IMPLEMENTATION ORDER

1. **Immediate**: Batch meta fetching (biggest impact, lowest risk)
2. **Next**: Batch taxonomy fetching
3. **Then**: Eliminate gallery duplication
4. **Follow-up**: Conditional asset loading
5. **Long-term**: Custom taxonomy caching

---

## TESTING APPROACH

After optimization, verify using Query Monitor or similar:

```php
// Add to template for query counting
do_action('qm/debug', 'Query count before:', get_num_queries());
// ... property display code ...
do_action('qm/debug', 'Query count after:', get_num_queries());
```

Expected results:
- Property list: 25+ → 3 queries per property
- REST API call: 301+ → 40 queries
- Admin page: 35+ → 10 queries

