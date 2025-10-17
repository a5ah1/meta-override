# Meta Override

A WordPress plugin that allows you to override meta tags, Open Graph data, Twitter Cards, and add Schema.org structured data for posts and pages.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)](https://wordpress.org/)

## Features

### Meta Tag Management
- Override document titles and meta descriptions on a per-post/page basis
- Character counter for meta fields to help with SEO best practices
- Automatic fallbacks when custom values aren't set (uses post title, excerpt, etc.)

### Open Graph Support
- Custom Open Graph titles, descriptions, and images
- Automatic image dimensions in meta tags
- WordPress Media Library integration for easy image selection
- Fallback to post data when custom values aren't provided

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
- Conditional asset loading (only loads on supported post types)
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

By default, Meta Override supports:
- Posts
- Pages

You can add support for custom post types using the filter (see Developer Hooks below).

## Developer Hooks

### Add Custom Post Type Support

```php
/**
 * Add 'product' post type to Meta Override
 */
add_filter('meta_override_supported_post_types', function($post_types) {
    $post_types[] = 'product';
    return $post_types;
});
```

### Customize Schema.org Output

```php
/**
 * Add organization/publisher info to Schema.org
 */
add_filter('meta_override_schema_org', function($schema, $post_id) {
    // Add publisher for articles
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
}, 10, 2);
```

## Helper Functions

While the plugin is designed to work automatically, developers can access helper methods:

```php
// Get all meta data for a post (with caching)
$meta_data = Meta_Override_Helper::get_all_meta_data($post_id);

// Get fallback title if custom title isn't set
$title = Meta_Override_Helper::get_fallback_title($post_id);

// Get fallback description if custom description isn't set
$description = Meta_Override_Helper::get_fallback_description($post_id);

// Clear cache for a specific post
Meta_Override_Helper::clear_cache($post_id);
```

## SEO Fallback Chain

When custom values aren't provided, Meta Override intelligently falls back to default WordPress content:

### Meta Description Priority
1. Custom `_mo_meta_description` (if set)
2. Post excerpt (if available)
3. Site tagline

### Open Graph Title Priority
1. Custom `_mo_og_title` (if set)
2. Post title

### Twitter Card Priority
1. Custom Twitter-specific value (if set)
2. Synced Open Graph value (if sync checkbox enabled)
3. Falls back to Open Graph value

This ensures your pages always have proper meta tags even if you haven't customized them yet.

## Database Storage

All meta data is stored with the `_mo_` prefix:

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

When you delete the plugin through WordPress admin, all meta data is automatically removed from your database. This includes:
- All post meta with `_mo_` prefix
- Cache flushing

**Note**: Deactivating the plugin does NOT remove data. Only uninstalling (deleting) removes data.

## Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Composer (for installation from source)

## Frequently Asked Questions

### Does this work with custom post types?

Yes! Use the `meta_override_supported_post_types` filter to add your custom post types.

### Will this conflict with SEO plugins?

Meta Override outputs its tags with high priority. If you're using another SEO plugin, you may want to disable that plugin's meta tag output for posts/pages where you're using Meta Override.

### What if I don't fill in all the fields?

No problem! The plugin provides intelligent fallbacks using your post title, excerpt, and WordPress defaults.

### Can I use this for the blog homepage?

Yes! The plugin automatically detects the "Posts page" setting in WordPress and allows you to override meta tags for it.

### Does this affect my site's performance?

No. The plugin includes a built-in caching system and only loads admin assets on edit screens for supported post types.

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
