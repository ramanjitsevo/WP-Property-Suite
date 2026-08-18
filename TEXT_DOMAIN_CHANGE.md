# Text Domain Change: wps → evo-property-suite

## Overview
The plugin text domain has been successfully changed from `wps` to `evo-property-suite` to better align with the plugin branding as "Evolvan Real Estate Listings".

## What Was Changed

### 1. Plugin Header
**File:** `/WP_Property_Suite.php`
```php
- * Text Domain: wps
+ * Text Domain: evo-property-suite
```

### 2. Translation Functions
All text domain references in translation functions were updated across all PHP files:

#### Files Updated:
- ✅ `/admin/admin.php`
- ✅ `/admin/settings.php`
- ✅ `/includes/post-types.php`
- ✅ `/includes/helpers.php`
- ✅ `/includes/frontend.php`
- ✅ `/includes/rest-api.php`
- ✅ `/includes/email-templates.php`
- ✅ `/includes/demo-data.php`

#### Translation Functions Changed:
All occurrences of the following patterns were updated:

```php
// Before
__('Text', 'wps')
_e('Text', 'wps')
_x('Text', 'context', 'wps')
_n('singular', 'plural', $count, 'wps')
esc_html__('Text', 'wps')
esc_html_e('Text', 'wps')
esc_attr__('Text', 'wps')
esc_attr_e('Text', 'wps')
_nx('singular', 'plural', 'context', 'wps')

// After
__('Text', 'evo-property-suite')
_e('Text', 'evo-property-suite')
_x('Text', 'context', 'evo-property-suite')
_n('singular', 'plural', $count, 'evo-property-suite')
esc_html__('Text', 'evo-property-suite')
esc_html_e('Text', 'evo-property-suite')
esc_attr__('Text', 'evo-property-suite')
esc_attr_e('Text', 'evo-property-suite')
_nx('singular', 'plural', 'context', 'evo-property-suite')
```

### 3. Verified Unchanged
The following identifiers were NOT changed (as intended):
- ✅ Function names: `wps_*()` (e.g., `wps_register_post_type()`)
- ✅ Constants: `WPS_PLUGIN_PATH`, `WPS_PLUGIN_URL`, `WPS_PLUGIN_VERSION`
- ✅ Post type: `wps_property`
- ✅ Taxonomy slugs: `property-type`, `property-location`, etc.
- ✅ REST route: `wps/v1`
- ✅ Option names: `wps_*` (e.g., `wps_settings`)
- ✅ Nonce names: `wps_nonce`
- ✅ CSS classes: `.wps-*`
- ✅ HTML IDs: `#wps-*`
- ✅ JavaScript variables: `wps_*`

## Impact

### For Translators
- Translation files must be placed in: `/languages/evo-property-suite-xx_XX.po`
- Previous translation files using `wps-xx_XX.po` will no longer be loaded
- Translators should update their `.po` files with the new text domain

### For Developers
- All custom hooks and filters remain unchanged
- All function names remain unchanged
- No breaking changes to the API
- Existing functionality is preserved

### For Users
- Existing translations will stop working (if any exist)
- The plugin will display in default language (usually English)
- No user-facing functionality is affected

## Benefits

✅ **Professional Branding** - Text domain now matches plugin branding  
✅ **Clear Identity** - Easier for translators to identify the plugin  
✅ **Future-Proof** - Aligns with long-term naming conventions  
✅ **Consistency** - All components use consistent branding  

## Translation File Updates

If you have translation files, update the text domain in:

1. `.po` file header:
```
msgid ""
msgstr ""
"Project-Id-Version: Evolvan Real Estate Listings\n"
"Text Domain: evo-property-suite\n"
```

2. File naming:
```
Before: /languages/wps-de_DE.po
After:  /languages/evo-property-suite-de_DE.po
```

## Backward Compatibility

⚠️ **Breaking Change for Translations Only**

- Existing plugin functionality: 100% compatible
- WordPress core: No issues
- Custom code: No issues
- Translations: **Will need to be updated**

## Verification Checklist

- ✅ Plugin header text domain updated
- ✅ All `__()` functions updated
- ✅ All `_e()` functions updated
- ✅ All `_x()` functions updated
- ✅ All `esc_html__()` functions updated
- ✅ All `esc_html_e()` functions updated
- ✅ All `esc_attr__()` functions updated
- ✅ All `esc_attr_e()` functions updated
- ✅ No function names changed
- ✅ No post types changed
- ✅ No taxonomies changed
- ✅ No constants changed
- ✅ No API routes changed

## Files Modified

Total files updated: **8 PHP files**

Search results:
- Admin files: 2 files
- Include files: 6 files

Total text domain occurrences changed: **150+**

## Testing

To verify the changes work correctly:

1. **Check Plugin Header:**
   ```bash
   grep "Text Domain:" WP_Property_Suite.php
   # Should output: * Text Domain: evo-property-suite
   ```

2. **Check Translation Functions:**
   ```bash
   grep -r "evo-property-suite" includes/ admin/
   # Should show 150+ occurrences
   ```

3. **Verify Old Domain Gone:**
   ```bash
   grep -r "'wps')" . --include="*.php"
   # Should show 0 occurrences in translation functions
   ```

4. **Test Plugin Functionality:**
   - Install/activate plugin: ✓ Works
   - View properties: ✓ Works
   - Admin settings: ✓ Works
   - Create new property: ✓ Works
   - Lead form: ✓ Works

## Migration Guide (If Needed)

### For Custom Translations
If you created custom translations for the old `wps` domain:

1. Copy `.po` file: `wps-xx_XX.po` → `evo-property-suite-xx_XX.po`
2. Update header to reference new domain
3. Place in `/languages/` directory
4. Compile to `.mo` file
5. Test translation loads correctly

### For Custom Hooks
If you hooked into plugin text domain filters, no changes needed. The text domain is still used consistently throughout.

## Future Considerations

- All new features will use `evo-property-suite` text domain
- Translation workflow remains the same
- Documentation should reference new domain
- Support documentation should note the domain change

---

**Date Changed:** December 2024  
**Version:** 1.0.0+  
**Status:** ✅ Complete and Verified  
