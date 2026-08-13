# Admin Guide: Customizing Email Templates

## Quick Start

### 1. Access Email Template Settings

1. Log in to your WordPress Admin Dashboard
2. Navigate to **WP Property Suite Settings** (found in the left sidebar)
3. Click the **"Email Templates"** tab

### 2. Edit Email Subject

The subject line is what recipients will see in their inbox.

**Example Subject:**
```
[New Lead] {property_title} — from {name}
```

**Available Variables:**
- `{name}` - Visitor's name
- `{property_title}` - Property being inquired about
- `{date}` - Submission date

### 3. Edit Email Template (HTML)

The HTML editor allows you to customize the entire email design and layout.

**Common Customizations:**

#### Change Header Color
Find this line in the template:
```html
<td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); ...">
```

Replace the colors:
- `#667eea` - Primary color (left)
- `#764ba2` - Secondary color (right)

#### Change Header Text
Find and modify:
```html
<h1 style="...">🏠 New Lead Received!</h1>
```

#### Customize Sections
The template includes these sections you can modify:
- **Header Section** - Title and branding
- **Lead Information** - Name, email, phone, message
- **Property Details** - Property title and link
- **Footer** - Copyright and links

#### Add Your Logo
Find the header section and add your logo URL:
```html
<img src="YOUR_LOGO_URL" style="max-width: 150px; height: auto;" />
```

### 4. Using Template Variables

Template variables are placeholders that get replaced with actual data when emails are sent.

**Insert variables anywhere in your template:**
```html
<p>Hello,</p>
<p>New inquiry from <strong>{name}</strong> ({email})</p>
<p>Property: <strong>{property_title}</strong></p>
<p>Message: {message}</p>
<p>Lead ID: {lead_id}</p>
```

**All Available Variables:**
| Variable | Description | Example |
|----------|-------------|---------|
| `{name}` | Visitor name | John Smith |
| `{email}` | Visitor email | john@example.com |
| `{phone}` | Visitor phone | +1-555-1234 |
| `{message}` | Inquiry message | "I'm interested in viewing the property" |
| `{property_title}` | Property name | "Modern Luxury Villa — Beverly Hills" |
| `{property_id}` | Property ID | 123 |
| `{property_url}` | Link to property | https://yoursite.com/property/123 |
| `{lead_id}` | Lead number | 456 |
| `{date}` | Submission date | December 15, 2024 at 2:30 PM |
| `{site_name}` | Your website name | My Real Estate |
| `{site_url}` | Your website URL | https://yoursite.com |
| `{year}` | Current year | 2024 |

### 5. Save Your Changes

Click **"Save All Changes"** at the bottom of the settings page.

### 6. Reset to Default

If you want to start over with the original professional template, click **"Reset to Default Template"** button.

---

## Practical Examples

### Example 1: Simple Text-Only Template

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>New Lead from {name}</h2>
    
    <p><strong>Contact Information:</strong></p>
    <ul>
        <li>Name: {name}</li>
        <li>Email: {email}</li>
        <li>Phone: {phone}</li>
    </ul>
    
    <p><strong>Property Inquiry:</strong></p>
    <p>Property: {property_title}</p>
    <p>Message: {message}</p>
    
    <p><strong>Lead Details:</strong></p>
    <p>Lead ID: {lead_id}</p>
    <p>Date: {date}</p>
    
    <hr>
    <p><a href="{property_url}">View Property</a></p>
    <footer>
        <p>© {year} {site_name}</p>
    </footer>
</body>
</html>
```

### Example 2: Branded Professional Template

```html
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; background: #f5f5f5; padding: 20px; }
        .header { background: #2c3e50; color: white; padding: 30px; text-align: center; }
        .content { background: white; padding: 30px; }
        .footer { background: #34495e; color: white; padding: 20px; text-align: center; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏠 New Property Inquiry</h1>
            <p>From {site_name}</p>
        </div>
        
        <div class="content">
            <h2>Hello,</h2>
            <p>You have received a new inquiry for:</p>
            <p style="font-size: 18px; font-weight: bold; color: #2c3e50;">{property_title}</p>
            
            <hr>
            
            <h3>Contact Information:</h3>
            <table style="width: 100%;">
                <tr><td><strong>Name:</strong></td><td>{name}</td></tr>
                <tr><td><strong>Email:</strong></td><td>{email}</td></tr>
                <tr><td><strong>Phone:</strong></td><td>{phone}</td></tr>
            </table>
            
            <h3>Message:</h3>
            <p style="background: #ecf0f1; padding: 15px; border-radius: 5px;">{message}</p>
            
            <p style="text-align: center; margin-top: 20px;">
                <a href="{property_url}" style="display: inline-block; background: #3498db; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px;">View Property</a>
            </p>
        </div>
        
        <div class="footer">
            <p>Lead ID: #{lead_id} | {date}</p>
            <p>© {year} {site_name}</p>
        </div>
    </div>
</body>
</html>
```

### Example 3: Minimal Modern Template

```html
<!DOCTYPE html>
<html>
<body style="margin: 0; padding: 20px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb;">
    <div style="max-width: 500px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        
        <div style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 40px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">New Inquiry</h1>
            <p style="margin: 5px 0 0 0; opacity: 0.9;">From {name}</p>
        </div>
        
        <div style="padding: 30px;">
            <p><strong>{property_title}</strong></p>
            <p>{message}</p>
            
            <p><small>
                <strong>Name:</strong> {name}<br>
                <strong>Email:</strong> {email}<br>
                <strong>Phone:</strong> {phone}
            </small></p>
        </div>
        
        <div style="background: #f9fafb; padding: 20px; text-align: center; font-size: 12px; color: #666;">
            <p style="margin: 0;">Lead #{lead_id} | {date}</p>
            <p style="margin: 10px 0 0 0;">© {year} {site_name}</p>
        </div>
    </div>
</body>
</html>
```

---

## Tips & Best Practices

✅ **DO:**
- Keep email width around 600px for better rendering
- Use inline CSS styles instead of `<style>` tags
- Test your template by submitting a test lead
- Use professional fonts like Arial, Georgia, or system fonts
- Include a link back to the property page

❌ **DON'T:**
- Use external stylesheets or JavaScript
- Make templates wider than 600px
- Use too many images (email clients block them)
- Use complex layouts with floats
- Change variable names or add new ones

---

## Troubleshooting

### "Variables not being replaced?"
- Make sure you're using exact variable names: `{variable_name}`
- Check for typos in variable names
- Variables are case-sensitive

### "Email looks broken on mobile?"
- Use inline styles only
- Keep width ≤ 600px
- Test with different email clients

### "Need to go back to original?"
- Click "Reset to Default Template" button
- Settings are saved automatically

---

## Common Use Cases

### Use Case 1: Multi-language Support

Add language selector or multiple templates per language:
```html
<!-- English version -->
<h2>New Property Inquiry</h2>
<!-- Or Spanish version -->
<h2>Nueva Consulta de Propiedad</h2>
```

### Use Case 2: Property-Specific Messaging

Use conditional styling based on property details:
```html
<h3>Luxury Property Inquiry</h3>
<p>Premium inquiry from {name}</p>
```

### Use Case 3: Follow-up Actions

Add buttons for quick actions:
```html
<a href="mailto:{email}" style="...">Reply</a>
<a href="tel:{phone}" style="...">Call</a>
<a href="{property_url}" style="...">View</a>
```

---

## Support

For issues or questions:
1. Check the template variables list above
2. Verify HTML syntax (unclosed tags cause issues)
3. Use browser developer tools to inspect email
4. Test with a free email testing service

**Need help?** Contact support or check the documentation.

---

*Last Updated: December 2024*
*For WP Property Suite v1.0.0+*
