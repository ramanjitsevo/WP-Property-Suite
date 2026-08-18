=== Evolvan Real Estate Listings ===
Contributors: evolvan
Tags: real estate, property, listings, react, shortcode
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A React-powered real estate plugin for property listings, search filters, lead capture, and single-property pages.

== Description ==

WP Property Suite helps site owners create and display real estate listings in WordPress. It adds a Property custom post type, property fields, taxonomies, frontend search, lead forms, property detail pages, and shortcodes for full listings, recent properties, and featured properties.

Main features:

* Property custom post type with price, area, address, gallery, agent, FAQ, and additional detail fields.
* Searchable React-powered property listing page.
* Single-property frontend detail pages.
* Lead capture form with leads stored in the WordPress database.
* Recent properties shortcode.
* Featured properties shortcode.
* Admin settings for colors, layout, banner, contact details, and lead form text.
* Optional sample data import from the plugin settings page.

= Shortcodes =

Full property search and listing experience:

`[wps_search]`

Recently added properties:

`[wps_recent_properties posts="6" columns="3"]`

Recently added properties slider:

`[wps_recent_properties posts="8" slider="yes"]`

Featured properties:

`[wps_featured_properties posts="6" columns="3"]`

Featured properties slider:

`[wps_featured_properties posts="8" slider="yes"]`

= External Services =

This plugin can connect to external services only when the site administrator enables or triggers related features.

Google Maps JavaScript API:

* Used for address autocomplete in the property admin edit screen.
* Loaded only when a Google Maps API key is saved in plugin settings and an administrator edits a property.
* Service provider: Google LLC.
* Terms: https://cloud.google.com/maps-platform/terms
* Privacy policy: https://policies.google.com/privacy

Unsplash image URLs in optional sample data:

* Used only when an administrator clicks the "Import Sample Data" button in the plugin settings.
* The importer may request image URLs from Unsplash and sideload them into the WordPress media library.
* Service provider: Unsplash Inc.
* Terms: https://unsplash.com/terms
* Privacy policy: https://unsplash.com/privacy

Social sharing links:

* Property detail pages include share links for Facebook, X/Twitter, and LinkedIn.
* These services are contacted only when a visitor clicks a share link.
* Facebook terms/privacy: https://www.facebook.com/legal/terms and https://www.facebook.com/privacy/policy/
* X terms/privacy: https://x.com/en/tos and https://x.com/en/privacy
* LinkedIn terms/privacy: https://www.linkedin.com/legal/user-agreement and https://www.linkedin.com/legal/privacy-policy

== Installation ==

1. Upload the `wp-property-suite` folder to `/wp-content/plugins/`.
2. Activate the plugin through the WordPress Plugins screen.
3. Add properties from the Properties menu in WordPress admin.
4. Add `[wps_search]` to a page to display the main property search and listing view.
5. Optional: use `[wps_recent_properties]` or `[wps_featured_properties]` in pages, posts, or widget areas.

== Frequently Asked Questions ==

= Does the plugin import sample data automatically? =

No. Sample data is imported only when an administrator clicks "Import Sample Data" in the plugin settings.

= How do I mark a property as featured? =

Edit a Property in WordPress admin and enable "Mark as Featured Property" in the Property Details box.

= Where are leads stored? =

Leads are stored in a custom database table in your WordPress database and can be viewed from the plugin's Leads admin page.

= Does the plugin require Google Maps? =

No. Google Maps is optional and is used only for admin address autocomplete when an API key is configured.

== Screenshots ==

1. Property listing and search page.
2. Single property detail page.
3. Property admin fields.
4. Plugin settings.

== Changelog ==

= 1.0.0 =

* Initial release.

== Upgrade Notice ==

= 1.0.0 =

Initial release.
