# Email Template Customization Feature

## Overview
Admins can now customize email templates sent to users/contacts when leads are captured from property pages. The feature includes a full-featured editor with template variables and preview capabilities.

## Features Implemented

### 1. **Email Template Management Module** (`includes/email-templates.php`)
- Registers email template settings in WordPress
- Provides template variable system with placeholders
- Includes functions to render and process templates
- Reset to default template functionality

#### Key Functions:
- `wps_register_email_template_settings()` - Registers settings
- `wps_get_default_lead_email_template()` - Returns default HTML template
- `wps_get_email_template_variables()` - Lists all available template variables
- `wps_render_email_template($template, $variables)` - Process template with variable substitution
- `wps_get_lead_email_template()` - Get current/saved template
- `wps_get_lead_email_subject()` - Get custom email subject
- `wps_process_email_subject($subject, $variables)` - Process subject line variables
- `wps_reset_email_template()` - Reset to default template

### 2. **Available Template Variables**
Admins can use these placeholders in email templates:
- `{name}` - Visitor full name
- `{email}` - Visitor email address
- `{phone}` - Visitor phone number
- `{message}` - Visitor message/inquiry
- `{property_title}` - Property listing title
- `{property_id}` - Property ID number
- `{property_url}` - Link to property page
- `{lead_id}` - Unique lead ID
- `{date}` - Submission date and time
- `{site_name}` - Website name
- `{site_url}` - Website URL
- `{year}` - Current year

### 3. **Admin Settings Interface**
Added new "Email Templates" tab in WP Property Suite Settings page:
- **Subject Line Editor** - Customize email subject with variables
- **HTML Template Editor** - Full TinyMCE editor for email template HTML
- **Variable Reference** - Displays all available variables with descriptions
- **Reset Button** - One-click reset to default professional template

### 4. **Integration with Lead Submission**
Updated `includes/rest-api.php` to:
- Use custom template from settings instead of hardcoded HTML
- Support variable substitution in subject and body
- Maintain backward compatibility
- Preserve email styling and functionality

### 5. **Default Template**
Professional, responsive HTML email template with:
- Gradient header
- Lead information section
- Property details section
- Lead/submission metadata
- Call-to-action buttons
- Footer with site information
- Mobile-responsive design

## How to Use

### For Admins

1. **Access Email Templates Settings:**
   - Go to WordPress Dashboard
   - Navigate to WP Property Suite Settings → Email Templates tab

2. **Customize Email Subject:**
   - Modify the subject line using variables like `{name}`, `{property_title}`
   - Example: `[New Lead] {property_title} — from {name}`

3. **Customize Email Template:**
   - Use the TinyMCE editor to modify the HTML template
   - Insert template variables using placeholders
   - Add your branding, colors, and layout preferences

4. **Preview Variables:**
   - Reference list shows all available variables and their descriptions
   - Use exact variable names in curly braces: `{variable_name}`

5. **Reset to Default:**
   - Click "Reset to Default Template" button to restore original design

## Technical Details

### Settings Storage
- `wps_lead_email_subject` - Email subject line with variables
- `wps_lead_email_template` - HTML email template with variables

### Sanitization
- Subject line: `sanitize_text_field()` before processing
- HTML template: `wp_kses_post()` for safe HTML handling
- Variables: HTML-escaped for security
- URLs and messages: Special handling for proper display

### Variable Processing
Variables are safely processed with:
- HTML escaping for text fields (name, email, etc.)
- URL escaping for URLs (property_url, site_url)
- HTML-allowed for message content (preserves line breaks)
- No direct user input in template rendering

## Security Considerations

✅ **Implemented Security Measures:**
- All variables are escaped before insertion
- Admin capability check for template editing
- Nonce verification for reset action
- Sanitization of all user inputs
- Safe handling of HTML content with `wp_kses_post()`

## Default Template Features

The professional default template includes:
- **Beautiful gradient header** with property house emoji
- **Lead information section** with name, email, phone, message
- **Property details section** with title, ID, and link to view
- **Meta information** with lead ID and submission date/time
- **Call-to-action buttons** for replying and calling
- **Footer** with site info and copyright
- **Responsive design** for mobile devices

## Future Enhancement Ideas

- Multiple email templates (lead notification, confirmation, etc.)
- Email template preview/test functionality
- Template import/export
- Pre-built template library
- Email scheduling/automation
- Analytics on email opens/clicks
- A/B testing different templates

## API Reference

### Register Hook
```php
add_action('init', 'wps_register_email_template_settings');
```

### Get Current Template
```php
$template = wps_get_lead_email_template();
$subject = wps_get_lead_email_subject();
```

### Render Template with Variables
```php
$variables = array(
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'property_title' => 'Beautiful House',
    // ... other variables
);
$html = wps_render_email_template($template, $variables);
```

### Reset to Default
```php
wps_reset_email_template();
```

## Files Modified/Created

### New Files:
- `/includes/email-templates.php` - Template management system

### Modified Files:
- `/WP_Property_Suite.php` - Added include for email-templates.php
- `/includes/rest-api.php` - Updated to use custom templates
- `/admin/settings.php` - Added Email Templates tab and UI

## Installation Note

No additional installation steps required. The feature is automatically available after plugin activation.

---

**Version:** 1.0.0  
**Compatibility:** WordPress 6.0+  
**License:** GPL v2 or later
