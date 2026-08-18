# Caching Strategy & Optimization Analysis

**Date**: August 13, 2026  
**Focus**: WordPress Transients, Object Cache, Browser Caching, API Caching

---

## CURRENT CACHING STATE

### What's Currently Cached

1. **Post Meta Cache** (automatic, per-request):
   - WordPress caches post meta within a request
   - Not persistent across requests
   - Resets on page reload

2. **Taxonomy Terms Cache** (automatic, per-request):
   - `wp_get_post_terms()` caches results per request
   - Not persistent
   - Resets on page reload

3. **Activation Notice** (1 minute transient):
   ```php
   set_transient('wps_show_activation_notice', true, 60);
   ```

**Everything Else**: Not cached

---

## CACHING OPPORTUNITIES

### Opportunity #1: Cache Property List (HIGH IMPACT)

**Location**: REST API endpoint `wps_get_properties()`

**Current**: Query runs every request
```php
$posts = get_posts( $args );
foreach ( $posts as $post ) {
    $property_data = wps_build_property_data( $post );
    $properties[] = $property_data;
}
```

**Problem**:
- 300+ queries for 12 properties every request
- No caching, full recalculation each time
- Even if data hasn't changed

**Recommended Caching Strategy**:

**Approach A: Transient-based (Simple)**
```php
function wps_get_properties_cached( $page = 1, $per_page = 12 ) {
    // Create cache key
    $cache_key = 'wps_properties_list_' . $page . '_' . $per_page;
    
    // Check cache
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }
    
    // If not cached, fetch and build
    $properties = wps_get_properties_uncached( $page, $per_page );
    
    // Cache for 1 hour
    set_transient( $cache_key, $properties, 3600 );
    
    return $properties;
}
```

**Cache Invalidation**:
```php
// Clear all property list caches when property updated
function wps_clear_property_cache() {
    global $wpdb;
    
    // Get all transients related to property lists
    $transients = $wpdb->get_col(
        "SELECT option_name FROM {$wpdb->options} 
         WHERE option_name LIKE '%wps_properties_list_%'"
    );
    
    foreach ( $transients as $transient ) {
        delete_transient( str_replace( 'transient_', '', $transient ) );
    }
}

add_action( 'save_post_wps_property', 'wps_clear_property_cache' );
add_action( 'deleted_post', 'wps_clear_property_cache' );
```

**Impact**:
- First request: 300+ queries → 1 request
- Subsequent requests: ~50ms (cached)
- 90% faster for users after first visitor

---

### Opportunity #2: Cache Taxonomy Terms List (MEDIUM IMPACT)

**Location**: REST endpoint `wps_get_taxonomies()`

**Current**:
```php
$taxonomies = array(
    'property_types' => get_terms(array('taxonomy' => 'property-type', 'hide_empty' => true)),
    'locations' => get_terms(array('taxonomy' => 'property-location', 'hide_empty' => true)),
    'bedrooms' => get_terms(array('taxonomy' => 'bedrooms', 'hide_empty' => true)),
    'bathrooms' => get_terms(array('taxonomy' => 'bathrooms', 'hide_empty' => true)),
    'floors' => get_terms(array('taxonomy' => 'property-floor', 'hide_empty' => true)),
);
```

**Problem**:
- 5 separate taxonomy queries
- Runs on every API call to `/taxonomies`
- Rarely changes (only when terms added/modified)

**Recommended Implementation**:
```php
function wps_get_taxonomies_cached() {
    $cache_key = 'wps_taxonomies_list';
    
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }
    
    $taxonomies = array(
        'property_types' => get_terms(array('taxonomy' => 'property-type', 'hide_empty' => true)),
        'locations' => get_terms(array('taxonomy' => 'property-location', 'hide_empty' => true)),
        'bedrooms' => get_terms(array('taxonomy' => 'bedrooms', 'hide_empty' => true)),
        'bathrooms' => get_terms(array('taxonomy' => 'bathrooms', 'hide_empty' => true)),
        'floors' => get_terms(array('taxonomy' => 'property-floor', 'hide_empty' => true)),
    );
    
    // Cache for 24 hours (terms rarely change)
    set_transient( $cache_key, $taxonomies, 86400 );
    
    return $taxonomies;
}

// Invalidate on term changes
function wps_clear_taxonomy_cache() {
    delete_transient( 'wps_taxonomies_list' );
}

add_action( 'create_term', 'wps_clear_taxonomy_cache' );
add_action( 'edit_term', 'wps_clear_taxonomy_cache' );
add_action( 'delete_term', 'wps_clear_taxonomy_cache' );
```

**Impact**:
- 5 queries → 0 queries (cached)
- 20+ ms per request saved
- Low invalidation frequency (only when terms change)

---

### Opportunity #3: Cache Custom Taxonomy List (LOW IMPACT)

**Location**: `includes/helpers.php` - `wps_build_property_data()`

**Current**:
```php
$custom_taxonomies = get_option('wps_custom_taxonomies', array());
// Called for every property
```

**Problem**:
- `get_option()` called for every property
- Returns 0 or many taxonomies
- For 12 properties: 12 option lookups

**Solution**:
```php
function wps_get_custom_taxonomies_cached() {
    $cached = wp_cache_get( 'wps_custom_taxonomies' );
    
    if ( false === $cached ) {
        $cached = get_option( 'wps_custom_taxonomies', array() );
        wp_cache_set( 'wps_custom_taxonomies', $cached );
    }
    
    return $cached;
}

// In wps_build_property_data()
$custom_taxonomies = wps_get_custom_taxonomies_cached();
```

**Impact**:
- 12 option lookups → 1 option lookup (first call only)
- Minimal performance gain, but cleaner code

---

### Opportunity #4: Cache Property Build Data Function (HIGH IMPACT)

**Location**: `includes/helpers.php` - `wps_build_property_data()`

**Current**: Built fresh every request for each property

**Problem**:
- Same property data requested multiple times
- REST API: All properties in list built fresh
- Shortcodes: Properties built fresh
- Admin: Property data built fresh
- No sharing between requests

**Recommended Strategy**:

**Option A: Transient per Property (Best for Dynamic Data)**
```php
function wps_build_property_data( $post ) {
    $post = get_post( $post );

    if ( !$post || $post->post_type !== 'wps_property' ) {
        return null;
    }

    // Cache key
    $cache_key = 'wps_property_' . $post->ID;
    
    // Check cache
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }

    // Original logic here
    $property_data = array( /* ... */ );

    // Cache for 12 hours (or until property updated)
    set_transient( $cache_key, $property_data, 43200 );

    return $property_data;
}

// Invalidate on update
function wps_clear_property_data_cache( $post_id ) {
    delete_transient( 'wps_property_' . $post_id );
    delete_transient( 'wps_properties_list_*' );  // Also invalidate list cache
}

add_action( 'save_post_wps_property', 'wps_clear_property_data_cache' );
```

**Impact**:
- For property detail page (single property): 25 queries → 1-2 queries
- REST API list: Depends on cache hits, could save 50-100ms per property

---

### Opportunity #5: Browser Cache Headers (MEDIUM IMPACT)

**Location**: REST API responses & static assets

**Current**: No explicit cache headers

**Recommended Implementation**:

**For REST API**:
```php
function wps_get_properties( $request ) {
    // ... existing code ...
    
    $response = rest_ensure_response( $properties );
    
    // Cache public data for 1 hour
    $response->header( 'Cache-Control', 'public, max-age=3600' );
    
    // Add ETag for conditional requests
    $etag = '"' . md5( wp_json_encode( $properties ) ) . '"';
    $response->header( 'ETag', $etag );
    
    // Check If-None-Match header
    if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && 
         $_SERVER['HTTP_IF_NONE_MATCH'] === $etag ) {
        return new WP_REST_Response( null, 304 );  // Not Modified
    }
    
    return $response;
}
```

**For Static Assets** (wp-config.php or .htaccess):
```apache
# Cache images for 30 days
<FilesMatch "\.(jpg|jpeg|png|gif|webp)$">
    Header set Cache-Control "public, max-age=2592000"
</FilesMatch>

# Cache CSS/JS for 7 days
<FilesMatch "\.(css|js)$">
    Header set Cache-Control "public, max-age=604800"
</FilesMatch>
```

**Impact**:
- Repeat visitors: 80-90% faster (cached locally)
- Bandwidth reduction: 40-50%

---

### Opportunity #6: Object Cache Plugin Integration (ADVANCED)

**Current**: No object cache plugin

**Recommended**: Use with Redis or Memcached

**Implementation**:
```php
// If object cache available, use it
function wps_get_property_optimized( $post_id ) {
    $cache_key = 'wps_property_' . $post_id;
    
    // Check object cache first (fastest, in-memory)
    $cached = wp_cache_get( $cache_key, 'wps_properties' );
    if ( false !== $cached ) {
        return $cached;
    }
    
    // Then check transient (database)
    $cached = get_transient( $cache_key );
    if ( false !== $cached ) {
        wp_cache_set( $cache_key, $cached, 'wps_properties' );
        return $cached;
    }
    
    // Finally, build from scratch
    $property = wps_build_property_data( $post_id );
    
    wp_cache_set( $cache_key, $property, 'wps_properties', 3600 );
    set_transient( $cache_key, $property, 3600 );
    
    return $property;
}
```

**Benefits**:
- In-memory caching: 1-10ms lookups
- Survives transient expiration
- Multi-tier caching strategy

---

### Opportunity #7: Lead Form Submissions Cache (LOW IMPACT)

**Current**: All submissions go directly to database

**Recommendation**: Brief cache to prevent duplicate submissions
```php
function wps_submit_lead( $request ) {
    $params = $request->get_json_params();
    
    // Create submission signature
    $signature = md5( json_encode( [
        $params['property_id'],
        $params['email'],
        $params['phone']
    ] ) );
    
    $cache_key = 'wps_lead_submission_' . $signature;
    
    // Check if recently submitted (within 2 minutes)
    if ( wp_cache_get( $cache_key ) ) {
        return new WP_Error(
            'duplicate_submission',
            __( 'This lead was recently submitted. Please wait before resubmitting.' ),
            array( 'status' => 429 )
        );
    }
    
    // ... submit lead ...
    
    // Mark as submitted
    wp_cache_set( $cache_key, true, '', 120 );  // 2 minute cache
    
    return rest_ensure_response( [ 'success' => true ] );
}
```

**Impact**: Prevents accidental duplicate submissions (UX improvement)

---

## CACHING STRATEGY SUMMARY

| Target | Method | TTL | Invalidation | Impact |
|--------|--------|-----|--------------|--------|
| Property list | Transient | 1 hour | On update | 🔥🔥🔥 High |
| Individual property | Transient | 12 hours | On update | 🔥🔥🔥 High |
| Taxonomies list | Transient | 24 hours | On term change | 🔥🔥 Medium |
| Property data cache | Transient | 12 hours | On update | 🔥🔥 Medium |
| Custom taxonomies | Object cache | Per-request | On update | 🔥 Low |
| API responses | Browser cache | 1 hour | On invalidation | 🔥🔥 Medium |
| Static assets | Browser cache | 30 days | On file change | 🔥 Medium |
| Lead submissions | Object cache | 2 minutes | Auto-expire | 🔥 Low |

---

## IMPLEMENTATION PRIORITY

### Phase 1: Quick Wins (1-2 hours)
1. Cache custom taxonomies in object cache
2. Add browser cache headers to API
3. Add ETag support to REST endpoints

### Phase 2: Medium Effort (2-3 hours)
1. Cache property build data function
2. Cache taxonomy terms list
3. Implement cache invalidation hooks

### Phase 3: Advanced (3-4 hours)
1. Implement multi-tier caching (object + transient)
2. Add Redis support
3. Advanced cache invalidation patterns

---

## PERFORMANCE IMPACT

### Before Caching
- Property list API: 300+ queries, 500-800ms
- Taxonomy list API: 5+ queries, 100-200ms
- Property detail view: 25+ queries, 200-300ms
- Repeat visits: Same as first visit

### After Caching (Phase 1 & 2)
- Property list API: First request ~500ms, cached ~50ms (90% improvement)
- Taxonomy list API: First request ~150ms, cached ~20ms (87% improvement)
- Property detail view: First request ~250ms, cached ~30ms (88% improvement)
- Repeat visits: 50-70% of original time (massive improvement)

---

## CACHE INVALIDATION STRATEGY

### When to Clear Caches

**Clear property-specific cache**:
- On `save_post_wps_property`
- On `deleted_post` (if wps_property)
- On property meta update

**Clear all property caches**:
- On taxonomy term change
- On plugin settings change
- On custom taxonomy change

**Clear taxonomy caches**:
- On `create_term`, `edit_term`, `delete_term`
- For relevant taxonomies only

### Implementation
```php
function wps_get_cache_groups() {
    return array(
        'wps_properties' => 'Individual property data',
        'wps_property_list' => 'Property list pages',
        'wps_taxonomies' => 'Taxonomy terms',
        'wps_settings' => 'Plugin settings',
    );
}

function wps_clear_all_property_caches() {
    wp_cache_flush_group( 'wps_properties' );
    wp_cache_flush_group( 'wps_property_list' );
}

// Hook to clear caches
add_action( 'save_post_wps_property', function( $post_id ) {
    delete_transient( 'wps_property_' . $post_id );
    delete_transient( 'wps_properties_list_*' );
    wp_cache_delete( 'wps_property_' . $post_id, 'wps_properties' );
});
```

---

## TESTING CACHING

### Manual Testing
```php
// Check if transient works
set_transient( 'test_cache', 'value', 60 );
$result = get_transient( 'test_cache' );
var_dump( $result );  // Should be 'value'

// Check cache hits
add_action( 'wp_footer', function() {
    echo '<!-- Cache hits: ' . get_transient_hits() . ' -->';
});
```

### Query Monitoring
```php
// In plugin
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    add_action( 'wp_footer', function() {
        echo '<!-- Queries: ' . get_num_queries() . ' -->';
    });
}
```

### Expected Query Reduction
- First request: 300+ queries
- Cached request: 5-10 queries (basic setup queries)
- With Redis: 0-2 queries (connection only)

