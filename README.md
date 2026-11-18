# Elementor Media Usage Finder

Select a Media Library image and find pages and templates that reference its attachment ID in saved Elementor content. The scanner reads up to 50 Elementor documents per request. It does not write to the database, delete files, or run on the frontend.

## Install and use

1. Make a backup and test on a staging WordPress site first.
2. Upload the `elementor-media-usage-finder` folder as a ZIP in **Plugins → Add New → Upload Plugin**, then activate it.
3. Open **Tools → Elementor Media Finder**, choose an image, and click **Find usage**. Media Library list view also has a **Find Elementor usage** row action.
4. Open a matching page or template from the results. You can cancel a long scan.

The screen is limited to administrators with `manage_options`. It searches posts, pages, custom post types with an admin screen, and Elementor templates with saved `_elementor_data`. Draft, pending, private and scheduled items are included. The result gives the page/template, status and number of saved attachment objects.

## What v1.0 checks

Elementor stores its document layout in JSON post meta. The scanner matches image objects with both an attachment `id` and a `url`, including those nested in image widgets, galleries, container backgrounds and page settings. Saved pages may still be found if Elementor is inactive, but its template post type must be registered for templates to be included.

It does not follow dynamic ACF fields, Elementor template placement rules, CSS URLs, shortcodes, theme files, or other plugins' private data. A missing result is **not** proof that an image is unused or safe to delete. A reusable template is listed as a template, not as every page that renders it.

## Compatibility and checks

- Requires WordPress 6.8+ and PHP 7.4+. JavaScript and CSS load only on this plugin's admin page.
- No Elementor Pro or ACF dependency. It uses WordPress APIs to read saved Elementor data and does not change Elementor's editor or frontend.
- PHP parser and JavaScript syntax checks were run during development. PHP lint and reference matching tests are configured in GitHub Actions for PHP 7.4, 8.3, 8.4 and 8.5.
- WordPress 7.1.5 and specific plugin combinations still need real site testing. Compatibility with every plugin cannot be guaranteed.

## Test checklist

On staging, create or identify five images: one in an Elementor Image widget, one in a container background, one in a gallery, one in a template, and one unused in Elementor. Scan each image and compare the result with the expected page or template. Test a draft, a site with more than 50 Elementor documents, and **Cancel** if available. Keep a screenshot of any mismatch and the page URL.

## Files

`includes/class-emuf-scanner.php` reads document data; `includes/class-emuf-admin.php` handles the admin screen and AJAX permission checks. `assets/css/admin.css` and `assets/js/admin.js` are loaded only on this screen. No settings or uninstall routine are needed because the plugin stores no data.

## License

GPL-2.0-or-later. See [LICENSE](LICENSE).
