# Meta Override

A WordPress plugin that allows you to override meta tags, Open Graph data, Twitter Cards, and add Schema.org structured data for posts, pages, custom post types, taxonomy terms, and archives.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)](https://wordpress.org/)

## Features

### Content Types & Taxonomies
- Choose exactly which post types Meta Override applies to, from the settings page
- Meta Override fields on taxonomy term screens for any selected public taxonomy
- Unticking a type stops output for it without deleting anything already saved

### Meta Tag Management
- Override document titles and meta descriptions per post, term, or archive
- Character counter for the title and description fields in the post and term editors
- Tags emit only when an explicit value is set — no synthesized defaults

### Open Graph Support
- Custom Open Graph titles, descriptions, and images
- Automatic image dimensions in meta tags
- WordPress Media Library integration for easy image selection
- Site-wide and per-post-type OG image fallbacks via the Settings page
- Home page settings for sites that show latest posts at the root

### Archives
- Optional output on category, tag, custom taxonomy, and post type archives
- Off by default — switch it on under *Archives* on the settings page
- Per-term and per-archive overrides, with site-wide fallbacks behind them

### Twitter Card Integration
- Custom Twitter Card titles, descriptions, and images
- Sync option to automatically use Open Graph values
- Supports `summary_large_image` card type
- Independent or synchronized with Open Graph data

### Schema.org JSON-LD
- Automatic Schema.org structured data generation
- Includes author information for articles
- ISO 8601 formatted dates
- Image dimensions when available
- Filterable for custom implementations

### Performance
- Built-in caching system to prevent duplicate database queries
- Conditional asset loading (only on screens for enabled post types and taxonomies)
- Optimized meta data retrieval

### Automatic Updates
- Automatic update notifications from GitHub releases
- One-click updates directly from WordPress admin
- No need to manually download and upload new versions
- Works just like official WordPress.org plugins

### Security
- Comprehensive nonce verification
- POST request validation
- User capability checks using post type capabilities
- Image attachment validation
- Input sanitization and output escaping throughout
- Directory protection with index.php files

## Installation

### From GitHub

1. Download the latest release or clone this repository:
   ```bash
   git clone https://github.com/a5ah1/meta-override.git
   ```

2. Install Composer dependencies:
   ```bash
   cd meta-override
   composer install --no-dev
   ```

3. Upload the `meta-override` directory to your WordPress `wp-content/plugins/` directory

4. Activate the plugin through the 'Plugins' menu in WordPress

**Note**: The plugin requires Composer dependencies for automatic updates. If you download a pre-built release zip from GitHub Releases, dependencies are already included.

### Manual Installation

1. Download the plugin zip file
2. In WordPress admin, go to Plugins → Add New → Upload Plugin
3. Choose the zip file and click "Install Now"
4. Activate the plugin

## Usage

### Basic Usage

1. After activation, edit any post or page
2. Scroll down to find the "Meta Override" meta box
3. Fill in the fields you want to customize:
   - **Meta Title**: The document title (appears in browser tabs and search results)
   - **Meta Description**: The meta description (appears in search results)
   - **Open Graph fields**: For Facebook and other social media platforms
   - **Twitter fields**: Specific Twitter Card data (or sync with Open Graph)

4. Publish or update your post

### Field Descriptions

#### Meta Tags
- **Meta Title**: Overrides the page title in `<title>` tag and search results
- **Meta Description**: The description shown in search engine results

#### Open Graph (Facebook)
- **OG Title**: Title when shared on Facebook/social media
- **OG Description**: Description when shared on Facebook/social media
- **OG Image**: Featured image for social media shares (use "Choose Image" button)

#### Twitter/X
- **Twitter Title**: Title for Twitter Cards (or check "Same as OG Title")
- **Twitter Description**: Description for Twitter Cards (or check "Same as OG Description")
- **Twitter Image**: Image for Twitter Cards (or check "Same as OG Image")

### Supported Post Types

Tick the post types you want under **Content types** on the settings page. All public post types are enabled by default — matching what earlier versions emitted for on the front end. The selection is stored on first save, so a post type registered later starts unticked.

Unticking a type turns Meta Override off for it completely, front end included — its singles and its post type archive. Values already saved against it stay in the database and are used again if you re-enable it.

Post types can also be controlled in code — see Developer Hooks below. The filter runs after the setting, so a type added in code stays enabled and shows as locked on the settings screen.

### Where Tags Are Emitted

| Context | Emits |
|---|---|
| Posts, pages, custom post types | Only for ticked content types |
| Home page and front page | Always — governed by their own settings, not the content type list |
| Category, tag, custom taxonomy archives | Only when archive output is on |
| Post type archives | Only when archive output is on *and* the type is ticked |
| Attachment pages | Never (attachments are not selectable) |
| Author, date, search, 404 | Never |

## Developer Hooks

### Add Custom Post Type Support

Runs after the **Content types** setting, so it has the final say. A type added here is enabled even if its box is unticked.

```php
/**
 * Add 'product' post type to Meta Override
 */
add_filter('meta_override_supported_post_types', function($post_types) {
    $post_types[] = 'product';
    return $post_types;
});
```

### Add Taxonomy Support

```php
/**
 * Add 'product_cat' taxonomy to Meta Override
 */
add_filter('meta_override_supported_taxonomies', function($taxonomies) {
    $taxonomies[] = 'product_cat';
    return $taxonomies;
});
```

### Customize Schema.org Output

```php
/**
 * Add organization/publisher info to Schema.org
 */
add_filter('meta_override_schema_org', function($schema, $post_id, $context) {
    // $context['context'] is one of:
    // singular | front | home | term | post_type_archive
    if ($schema['@type'] === 'WebPage') {
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => 'Your Company Name',
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => 'https://example.com/logo.png'
            )
        );
    }

    return $schema;
}, 10, 3);
```

## Helper Functions

Developers can access helper methods:

```php
// Get all meta data for a post (with caching)
$meta_data = Meta_Override_Helper::get_all_meta_data($post_id);

// ...or for a term
$meta_data = Meta_Override_Helper::get_all_term_meta_data($term_id);

// Validate an attachment ID — returns int (0 if not a valid image attachment)
$id = Meta_Override_Helper::sanitize_image_id($id);

// Get the filtered list of supported post types / taxonomies
$types = Meta_Override_Helper::get_supported_post_types();
$taxonomies = Meta_Override_Helper::get_supported_taxonomies();

// Get image dimensions for an attachment (memoized per request)
$dims = Meta_Override_Helper::get_image_dimensions($image_id);

// Clear meta cache for a specific post, or a term
Meta_Override_Helper::clear_cache($post_id);
Meta_Override_Helper::clear_cache($term_id, Meta_Override_Constants::OBJECT_TERM);
```

## Resolution Rules

Meta Override emits tags only when an explicit value is available. The OG image is the one tag with a multi-level fallback.

### Meta description, OG title/description, Twitter title/description
Emitted only when set. If blank, the tag is omitted entirely.

### og:type
Flat post types (such as `post`) emit `og:type=article`, along with `article:published_time` and `article:modified_time`. Hierarchical types (such as `page`), the home page, the front page, and every archive emit `og:type=website` with no date tags.

### OG Image (cascading)
1. Featured image — when "Use Featured Image" is checked on the post
2. Per-post or per-term `_mo_og_image`
3. Per-post-type fallback (Settings → Meta Override) — applies to singles, the Posts page, and post type archives; term archives skip this step
4. Site-wide fallback (Settings → Meta Override)
5. Otherwise: no `og:image` tag

### Twitter sync behavior
When "Same as OG…" is checked on the post, the Twitter tag mirrors the resolved OG tag. If the corresponding OG tag has no value, the Twitter tag is skipped too.

## Database Storage

Post values live in `postmeta` and term values in `termmeta`, both with the `_mo_` prefix and the same field names. The content type and taxonomy selection, site-wide fallbacks, and the home page and post type archive values live in the `meta_override_settings` option, since those have no object to attach meta to.

- `_mo_meta_title`
- `_mo_meta_description`
- `_mo_og_title`
- `_mo_og_description`
- `_mo_og_image`
- `_mo_og_image_id`
- `_mo_twitter_title`
- `_mo_twitter_description`
- `_mo_twitter_image`
- `_mo_twitter_image_id`
- `_mo_twitter_title_same_as_og`
- `_mo_twitter_description_same_as_og`
- `_mo_twitter_image_same_as_og`

## Uninstall

When you delete the plugin through WordPress admin, all of its data is automatically removed from your database. This includes:
- All post meta with the `_mo_` prefix
- All term meta with the `_mo_` prefix
- The `meta_override_settings` option
- Cache flushing

**Note**: Deactivating the plugin does NOT remove data. Only uninstalling (deleting) removes data.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Composer (for installation from source)

## Frequently Asked Questions

### Does this work with custom post types?

Yes — all public post types are enabled by default. Adjust the selection under **Content types** on the settings page, or control it in code with the `meta_override_supported_post_types` filter (non-public types can only be added via the filter).

### Will this conflict with SEO plugins?

Meta Override outputs its tags with high priority. If you're using another SEO plugin, you may want to disable that plugin's meta tag output for posts/pages where you're using Meta Override.

### What if I don't fill in all the fields?

Most tags (meta description, OG/Twitter title and description) are omitted when no value is set — Meta Override does not synthesize defaults. The OG image is the one exception: it cascades from the post field → per-post-type fallback → site-wide fallback. Configure the fallbacks at *Settings → Meta Override*.

### Can I use this for taxonomy archives?

Yes. Tick the taxonomy under **Archives** on the settings page to get Meta Override fields on its term screens, and switch on **Output tags on archive pages** so those values are emitted.

### Can I use this for the blog homepage?

Yes. The plugin emits core OG scaffolding plus your site-wide fallbacks on both kinds of WordPress blog homes: a "Posts page" assigned via Reading Settings, and the default "Show latest posts" front page.

### Does this affect my site's performance?

No. The plugin includes a built-in caching system and only loads admin assets on edit screens for enabled post types and taxonomies.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This plugin is licensed under the MIT License.

Copyright (c) 2025 a5ah1

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

## Author

a5ah1

## Support

For bug reports and feature requests, please use the [GitHub issue tracker](https://github.com/a5ah1/meta-override/issues).
