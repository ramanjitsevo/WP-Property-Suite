# REST API Performance Analysis - WP-PROPERTY-SUITE

**Date**: August 13, 2026  
**Focus**: Data Efficiency, Payload Optimization, Query Reduction

---

## CURRENT REST API ENDPOINTS

### 1. GET /wp-json/wps/v1/properties

**Location**: `includes/rest-api.php` - `wps_get_properties()`

**Current Response**:
```json
[
  {
    "id": 1,
    "title": "Beautiful Home",
    "content": "Full post content (500+ chars)",
    "excerpt": "Full excerpt (200+ chars)",
    "date": "2024-01-15T10:00:00",
    "thumbnail": "https://example.com/image.jpg",
    "featured": true,
    "price": "$500,000",
    "area": "2500 sq ft",
    "address": "123 Main St",
    "city": "New York",
    "state": "NY",
    "zipcode": "10001",
    "country": "USA",
    "latitude": 40.7128,
    "longitude": -74.0060,
    "lat": 40.7128,
    "lng": -74.0060,
    "status": "for-sale",
    "property_type": "House",
    "location": "New York",
    "bedrooms": "3",
    "bathrooms": "2",
    "floor": "2",
    "gallery": ["url1", "url2", "url3", "url4"],
    "thumbnail_url": "https://example.com/thumb.jpg",
    "gallery_urls": "[\"url1\", \"url2\"]",
    "agent": {
      "name": "John Doe",
      "phone": "555-1234",
      "email": "john@example.com",
      "photo": "https://example.com/agent.jpg"
    },
    "additional_details": [
      {"label": "Type", "value": "Single Family"},
      {"label": "Built Year", "value": "2010"},
      ...
    ],
    "faqs": [
      {"question": "Can I negotiate?", "answer": "Yes..."},
      ...
    ]
  }
]
```

**Payload Size Per Property**:
- Basic info (id, title, price): ~200 bytes
- All metadata: ~1.2 KB
- Gallery (4 URLs): ~400 bytes
- Additional details: ~600 bytes
- FAQs: ~800 bytes
- **Total per property: ~3.2 KB**

**For 12 properties**: 38.4 KB per request

---

## WHAT REACT FRONTEND ACTUALLY USES

### On Property List Page:
```javascript
{
  "id": 1,
  "title": "Beautiful Home",
  "price": "$500,000",
  "thumbnail": "https://example.com/image.jpg",
  "featured": true,
  "city": "New York",
  "address": "123 Main St",
  "bedrooms": "3",
  "bathrooms": "2"
}
```

**Actual Used**: ~250 bytes per property  
**Current Total**: ~3,200 bytes per property  
**Unused Data**: **92% of response wasted**

---

### On Single Property Page:
```javascript
{
  "id": 1,
  "title": "Beautiful Home",
  "content": "Full post content",
  "excerpt": "Short excerpt",
  "price": "$500,000",
  "thumbnail": "https://example.com/image.jpg",
  "featured": true,
  "address": "123 Main St",
  "city": "New York",
  "state": "NY",
  "zipcode": "10001",
  "country": "USA",
  "latitude": 40.7128,
  "longitude": -74.0060,
  "gallery": ["url1", "url2", "url3"],
  "area": "2500 sq ft",
  "status": "for-sale",
  "bedrooms": "3",
  "bathrooms": "2",
  "floor": "2",
  "agent": {
    "name": "John Doe",
    "phone": "555-1234",
    "email": "john@example.com",
    "photo": "https://example.com/agent.jpg"
  },
  "additional_details": [...],
  "faqs": [...]
}
```

**Actual Used**: ~2,800 bytes per property  
**Current Total**: ~3,200 bytes per property  
**Unused Data**: **12% of response wasted**

---

## PERFORMANCE ISSUES

### Issue #1: No Field Filtering

**Problem**: REST endpoint returns same data for all use cases.

**Current Flow**:
```
Frontend: GET /wp-json/wps/v1/properties
Response: 38.4 KB for 12 properties
Frontend extracts needed fields
90% of bandwidth wasted on unused data
```

**Impact**:
- Mobile: 38.4 KB = ~1.5 seconds on 3G
- Desktop: 38.4 KB = ~300ms on broadband
- Repeated list loads unnecessary data

---

### Issue #2: No Pagination Optimization

**Current Implementation** (rest-api.php):
```php
$args = array(
    'post_type'      => 'wps_property',
    'posts_per_page' => $per_page,  // Default 12
    'paged'          => $page,
    'offset'         => $offset,
    'post_status'    => 'publish',
    'orderby'        => 'date',
    'order'          => 'DESC',
);
```

**Issues**:
- No cache headers set
- No ETag support
- No conditional requests (If-Modified-Since)
- Frontend makes full request for every page

---

### Issue #3: Gallery URLs Stored in Two Places

**Current**: In helpers.php
```php
$gallery = array();
$gallery_ids_raw = get_post_meta($post->ID, '_property_gallery', true);
// Process attachment IDs...

if (empty($gallery)) {
    $gallery_urls_raw = get_post_meta($post->ID, '_property_gallery_urls', true);
    // Use stored URLs as fallback
}

// Return both in response
'gallery' => $gallery,
'gallery_urls' => get_post_meta($post->ID, '_property_gallery_urls', true) ?: '',
'thumbnail_url' => get_post_meta($post->ID, '_property_thumbnail_url', true) ?: '',
```

**Problems**:
- 2x gallery meta fetch calls
- Returns redundant data in response
- `gallery_urls` duplicates `gallery` array
- `thumbnail_url` duplicates `thumbnail`

---

### Issue #4: Duplicate Coordinates

**Current Response**:
```php
'latitude' => (float) $latitude,
'longitude' => (float) $longitude,
'lat' => (float) $latitude,    // Duplicate
'lng' => (float) $longitude,   // Duplicate
```

**Problem**: Same data under different keys adds ~40 bytes per property.

---

### Issue #5: Status Code Not Leveraging HTTP Caching

**Current**: No cache headers
```php
return rest_ensure_response( $properties );
```

**Better**: Add cache headers for public data
```php
$response = rest_ensure_response( $properties );
$response->header( 'Cache-Control', 'public, max-age=3600' );
return $response;
```

---

## DATABASE QUERY PROBLEMS IN REST

**For GET /wp-json/wps/v1/properties?page=1&per_page=12**:

1. `get_posts()` - 1 query to fetch 12 property IDs
2. For each property (×12): `wps_build_property_data()`
   - 19 `get_post_meta()` calls: ~228 queries
   - 5 `wp_get_post_terms()` calls: ~60 queries
   - Custom taxonomy loop: ~5N queries

**Total**: ~293 queries for 12 properties

---

## OPTIMIZATION RECOMMENDATIONS

### Priority 1: Add Field Filtering Parameter

**Implement**:
```php
GET /wp-json/wps/v1/properties?_fields=id,title,price,thumbnail,bedrooms,bathrooms
GET /wp-json/wps/v1/properties?_fields=id,title,price,thumbnail&detail=true
```

**Implementation**:
```php
function wps_get_properties( $request ) {
    // Get fields parameter
    $fields = $request->get_param( '_fields' );
    $detail = $request->get_param( 'detail' );
    
    // Determine what to fetch based on fields
    $fetch_full = $detail === 'true' || !$fields;
    
    // ... get properties ...
    
    foreach ( $posts as $post ) {
        if ( $fetch_full ) {
            $property_data = wps_build_property_data( $post );
        } else {
            // Fetch only requested fields
            $property_data = wps_build_property_data_minimal( $post, $fields );
        }
        // ...
    }
}
```

**Helper Function**:
```php
function wps_build_property_data_minimal( $post, $fields = '' ) {
    $default_fields = array( 'id', 'title', 'price', 'thumbnail' );
    $requested = $fields ? array_map( 'trim', explode( ',', $fields ) ) : $default_fields;
    
    $data = array();
    
    foreach ( $requested as $field ) {
        switch ( $field ) {
            case 'id':
                $data['id'] = $post->ID;
                break;
            case 'title':
                $data['title'] = $post->post_title;
                break;
            case 'price':
                $data['price'] = wps_format_price( get_post_meta( $post->ID, '_property_price', true ) );
                break;
            case 'thumbnail':
                $data['thumbnail'] = get_the_post_thumbnail_url( $post->ID, 'large' ) ?: '';
                break;
            case 'bedrooms':
                $terms = wp_get_post_terms( $post->ID, 'bedrooms', array( 'fields' => 'names' ) );
                $data['bedrooms'] = !empty( $terms ) ? $terms[0] : 'N/A';
                break;
            // ... more fields ...
        }
    }
    
    return $data;
}
```

**Impact**:
- List view: 38.4 KB → 3 KB (92% reduction)
- Bandwidth saved: 90%+
- Load time improvement: 40-60%

---

### Priority 2: Fix Duplicate Coordinates and Gallery URLs

**Current Response**:
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "lat": 40.7128,
  "lng": -74.0060,
  "gallery": ["url1", "url2"],
  "gallery_urls": "[\"url1\", \"url2\"]",
  "thumbnail": "...",
  "thumbnail_url": "..."
}
```

**Optimized Response**:
```json
{
  "latitude": 40.7128,
  "longitude": -74.0060,
  "gallery": ["url1", "url2"],
  "thumbnail": "..."
}
```

**Implementation**:
```php
$property_data = array(
    'id' => $post->ID,
    'title' => $post->post_title,
    'price' => wps_format_price($price),
    // ... other fields ...
    'latitude' => is_numeric($latitude) ? (float) $latitude : null,
    'longitude' => is_numeric($longitude) ? (float) $longitude : null,
    // Remove 'lat', 'lng', duplicate 'gallery_urls', 'thumbnail_url'
    'gallery' => $gallery,
    'thumbnail' => $thumb ?: '',
    // ...
);
```

**Impact**: ~80-100 bytes per property saved (3-5% payload reduction)

---

### Priority 3: Implement Batch Meta Fetching

**See DATABASE_QUERY_ANALYSIS.md for details**

**Impact**:
- 19 queries → 1 query per property
- 87% fewer database queries
- API response time: 30-40% faster

---

### Priority 4: Add Cache Headers

**Implementation**:
```php
function wps_get_properties( $request ) {
    // ... existing code ...
    
    $response = rest_ensure_response( $properties );
    
    // Set cache headers for public properties list
    $response->header( 'Cache-Control', 'public, max-age=3600' );
    
    // Set ETag for conditional requests
    $response->header( 'ETag', '"' . md5( wp_json_encode( $properties ) ) . '"' );
    
    return $response;
}
```

**Impact**:
- Browser caching: Reduces repeat requests
- CDN caching: Static data can be cached
- Conditional requests: 304 Not Modified saves bandwidth

---

### Priority 5: Add Rate Limiting

**Current**: No rate limiting on REST endpoints

**Implementation**:
```php
function wps_rest_rate_limit( $request ) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = 'wps_api_' . $ip;
    $count = wp_cache_get( $key );
    
    if ( $count >= 60 ) {  // 60 requests per minute per IP
        return new WP_Error(
            'rate_limit',
            __( 'Too many requests. Please try again later.' ),
            array( 'status' => 429 )
        );
    }
    
    wp_cache_set( $key, $count + 1, '', 60 );
}

add_action( 'rest_api_init', function() {
    add_filter( 'wps/v1/rest_pre_dispatch', 'wps_rest_rate_limit' );
});
```

**Impact**: Prevents API abuse, protects server resources

---

## RESPONSE TIME TARGETS

### Before Optimization:
- List 12 properties: ~300-500ms
- Payload size: 38.4 KB
- Queries: 293

### After Optimization (All Priority 1-3):
- List 12 properties: ~100-150ms
- Payload size: 3 KB (with field filtering)
- Queries: 40

### Improvement:
- Response time: **60-70% faster**
- Payload: **92% smaller**
- Queries: **87% fewer**

---

## IMPLEMENTATION PRIORITY

1. **Priority 1** (Quick win): Field filtering - 1-2 hours, 92% payload reduction
2. **Priority 2** (Low effort): Remove duplicates - 30 min, 3-5% improvement
3. **Priority 3** (High impact): Batch queries - 2-3 hours, 87% fewer queries
4. **Priority 4** (Best practice): Cache headers - 30 min
5. **Priority 5** (Security): Rate limiting - 30 min

---

## TESTING APPROACH

```php
// Measure payload size
$response = wps_get_properties( new WP_REST_Request( 'GET', '/wps/v1/properties' ) );
$payload = wp_json_encode( $response->get_data() );
echo 'Payload size: ' . strlen( $payload ) . ' bytes';

// Measure query count
$queries_before = get_num_queries();
// ... API call ...
$queries_after = get_num_queries();
echo 'Queries: ' . ( $queries_after - $queries_before );
```

Expected improvements:
- Payload: 38KB → 3KB (with field filtering)
- Queries: 293 → 40 (with batch fetching)
- Time: 300-500ms → 100-150ms

