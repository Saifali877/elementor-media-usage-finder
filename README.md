# Elementor Media Usage Finder

By [DevPulse360](https://devpulse360.com/).

Development candidate: **1.2.0**. This version is not published on GitHub yet. It adds ACF image, gallery and repeater image fields to the v1.1.0 Elementor and page settings scan.

Select a Media Library image and find posts, pages and templates that reference its attachment ID in saved Elementor content or ACF fields. The scanner reads up to 50 items per request. It does not write to the database, delete files, or run on the frontend.

## Install and use

1. Make a backup and test on a staging WordPress site first.
2. Upload the `elementor-media-usage-finder` folder as a ZIP in **Plugins → Add New → Upload Plugin**, then activate it.
3. Open **Tools → Elementor Media Finder**, choose an image, and click **Find usage**. Media Library list view also has a **Find Elementor usage** row action.
4. Open a matching page or template from the results. You can cancel a long scan.

The screen is limited to administrators with `manage_options`. With ACF active, it scans posts, pages, custom post types with an admin screen, and Elementor templates, including those without Elementor data. Without ACF, it scans items with saved `_elementor_data` or `_elementor_page_settings`. Draft, pending, private and scheduled items are included. The result gives the post/page/template, status and number of saved image references.

## What this candidate checks

Elementor stores its document layout in JSON post meta. The scanner matches image objects with both an attachment `id` and a `url`, including those nested in image widgets, galleries, container backgrounds and page settings. Saved pages may still be found if Elementor is inactive, but its template post type must be registered for templates to be included.

With ACF active, it also reads raw post meta for image and gallery field IDs whose ACF field-key references resolve to the correct field types. Repeater row image/gallery subfields are included. ACF return format (ID, array or URL) does not affect saved IDs. It does not inspect ACF options pages, field data attached to attachments/users/terms, Elementor template placement rules, CSS URLs, shortcodes, theme files or other plugins' private data. A missing result is **not** proof that an image is unused or safe to delete. A reusable template is listed as a template, not as every page that renders it.

## Compatibility and checks

- Requires WordPress 6.8+ and PHP 7.4+. JavaScript and CSS load only on this plugin's admin page.
- No Elementor Pro or ACF dependency. When ACF is active, it resolves ACF field keys to image/gallery field types. It does not change the editor or frontend.
- PHP lint and reference matching tests are configured in GitHub Actions for PHP 7.4, 8.3, 8.4 and 8.5. The admin JavaScript test runs in Node.js. The new ACF test still needs to run before publishing.
- WordPress 7.1.5 and specific plugin combinations still need real site testing. Compatibility with every plugin cannot be guaranteed.

## Test checklist

On staging, scan images saved in an ACF image field, gallery field, and image and gallery fields inside separate repeater rows. Include a post without Elementor data. Verify a numeric text field with the same attachment ID does not match. Repeat an Elementor widget, container background, template, unused image, draft, more than 50 posts, and **Cancel**. Keep a screenshot of any mismatch and the page URL.

## Files

`includes/class-emuf-scanner.php` reads document data; `includes/class-emuf-admin.php` handles the admin screen and AJAX permission checks. `assets/css/admin.css` and `assets/js/admin.js` are loaded only on this screen. No settings or uninstall routine are needed because the plugin stores no data.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
