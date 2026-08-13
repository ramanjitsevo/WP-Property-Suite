# Default Properties on Plugin Installation

## Overview
When the WP Property Suite plugin is installed and activated, **10 default sample properties** are now automatically imported into the WordPress database. This provides users with an immediate working demo without needing to manually add properties.

## What Changed

### Plugin Activation Hook
Updated `WP_Property_Suite.php` activation hook to automatically call `wps_install_default_data()`:

```php
function wps_activate() {
    wps_register_post_type();
    wps_register_taxonomies();
    wps_create_leads_table();
    
    flush_rewrite_rules();
    
    // Install default demo properties on activation
    wps_install_default_data();
    
    set_transient('wps_show_activation_notice', true, 60);
}
```

## How It Works

1. **Plugin Activated** - User activates the WP Property Suite plugin
2. **Activation Hook Fires** - `wps_activate()` function is called
3. **Post Types & Taxonomies Registered** - Custom post types and taxonomies are created
4. **Default Properties Imported** - `wps_install_default_data()` reads from `data/default-properties.json`
5. **Properties Created** - 10 sample properties are added as published posts
6. **Settings Imported** - Default plugin settings are applied
7. **Activation Notice Shown** - User sees helpful setup instructions

## Default Properties Included

The plugin includes 10 professionally curated sample properties:

1. **Modern Luxury Villa — Beverly Hills** - $850,000 | 2,500 sq ft | 4 bed, 3 bath
2. **Cozy Downtown Apartment — New York** - $650,000 | 1,200 sq ft | 2 bed, 1 bath
3. **Beachfront Bungalow — Malibu** - $1,200,000 | 2,000 sq ft | 3 bed, 2 bath
4. **Spacious Family Home — Austin, Texas** - $450,000 | 3,000 sq ft | 5 bed, 3 bath
5. **Luxury Penthouse — Miami Beach** - $2,500,000 | 3,500 sq ft | 4 bed, 4 bath
6. **Charming Victorian House — Boston** - $550,000 | 2,200 sq ft | 3 bed, 2 bath
7. **Modern Condo — Seattle Downtown** - $480,000 | 1,100 sq ft | 2 bed, 1.5 bath
8. **Rustic Farm House — Vermont** - $320,000 | 2,800 sq ft | 4 bed, 2 bath
9. **Desert Villa — Scottsdale, Arizona** - $780,000 | 2,600 sq ft | 3 bed, 3 bath
10. **Lakeside Cottage — Lake Tahoe** - $900,000 | 2,100 sq ft | 3 bed, 2 bath

## Each Property Includes

✅ Property details (price, area, bedrooms, bathrooms)  
✅ Full address with coordinates (lat/lng)  
✅ Property type and location taxonomy  
✅ High-quality images from Unsplash  
✅ Property description and excerpt  
✅ Agent information (name, phone, email, photo)  
✅ FAQ section with common questions  
✅ Additional details (year built, heating, etc.)  
✅ Property status (for-sale or for-rent)  

## Preventing Duplicate Imports

The system includes protection against duplicate imports:

- **First Install**: Properties are imported on activation
- **Subsequent Activations**: Duplicates are skipped (checks by title)
- **Re-activation**: Existing properties are not re-imported
- **Option Flag**: `wps_default_data_installed` tracks installation state

## Manual Re-import

If admins delete default properties and want to restore them:

1. Go to WP Property Suite Settings
2. Scroll to bottom
3. Click "Import Sample Data" button
4. Confirm the action
5. All 10 default properties are re-imported

## Benefits

✅ **Instant Demo** - No waiting for manual property creation  
✅ **Test Environment** - Users can test all features immediately  
✅ **Learning Tool** - Sample data shows proper property formatting  
✅ **Professional Setup** - Users see polished demo from day one  
✅ **No Manual Work** - Eliminates setup friction  
✅ **Easy to Remove** - Users can delete unwanted properties  

## User Experience Flow

### Before (Old)
1. Install plugin ❌ No properties shown
2. Visit site ❌ Empty listings page
3. Admin manually adds properties ⏱️ Time-consuming
4. Test features ✓ Finally ready

### After (New)
1. Install plugin ✓ Properties auto-imported
2. Visit site ✓ 10 demo properties displayed
3. Test features immediately ✓ Ready to use
4. Delete/customize as needed ✓ Full control

## Technical Details

### Default Properties File
- Location: `/data/default-properties.json`
- Format: JSON array with 10 property objects
- Size: ~150KB (includes URLs to external images)

### Installation Function
- File: `includes/demo-data.php`
- Function: `wps_install_default_data()`
- Logic: 
  - Reads JSON file
  - Creates posts with post meta
  - Assigns taxonomies
  - Sideloads images
  - Updates settings
  - Logs progress

### Safety Checks
- ✓ Duplicate title detection
- ✓ File existence validation
- ✓ JSON parsing error handling
- ✓ WordPress function availability checks
- ✓ Post creation error handling

## Deactivation & Re-activation

- **Deactivate Plugin**: Properties remain in database (not deleted)
- **Reactivate Plugin**: Detects existing properties, skips re-import
- **Delete Plugin**: Properties can be manually deleted or kept

## For Developers

### Disable Auto-import
If you want to prevent automatic import during activation:

```php
// Add to wp-config.php or constants file
define('WPS_SKIP_AUTO_INSTALL', true);
```

Then modify the activation hook to check:
```php
if (!defined('WPS_SKIP_AUTO_INSTALL')) {
    wps_install_default_data();
}
```

### Customize Default Properties
Edit `/data/default-properties.json` before plugin installation to customize:
- Property titles and descriptions
- Prices and specifications
- Images and agent information
- FAQs and additional details
- Locations and property types

## Troubleshooting

**Q: Properties not showing after installation?**
A: Clear cache and flush permalinks. Go to Settings > Permalinks and click Save Changes.

**Q: Duplicate properties appearing?**
A: This is prevented by the system. Refresh the page or clear the database and reinstall.

**Q: Can I use my own properties instead?**
A: Yes, delete the default properties and add your own through the WordPress admin.

**Q: How do I reset to default properties?**
A: Use the "Import Sample Data" button in WP Property Suite Settings.

---

**Version:** 1.0.0  
**Release Date:** December 2024  
**Status:** Automatic import enabled by default  
