=== Meta Override ===
Contributors: a5ah1
Tags: meta tags, open graph, twitter cards, schema, seo
Requires at least: 5.0
Tested up to: 6.7
Requires PHP: 7.0
Stable tag: 1.1.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Override meta tags, Open Graph data, Twitter Cards, and add Schema.org structured data for posts and pages.

== Description ==

Meta Override is a powerful yet lightweight WordPress plugin that gives you complete control over your site's meta tags, Open Graph data, Twitter Cards, and Schema.org structured data on a per-post/page basis.

= Key Features =

**Meta Tag Management**

* Override document titles and meta descriptions on a per-post/page basis
* Character counter for meta fields to help with SEO best practices
* Automatic fallbacks when custom values aren't set (uses post title, excerpt, etc.)

**Open Graph Support**

* Custom Open Graph titles, descriptions, and images
* Automatic image dimensions in meta tags
* WordPress Media Library integration for easy image selection
* Fallback to post data when custom values aren't provided

**Twitter Card Integration**

* Custom Twitter Card titles, descriptions, and images
* Sync option to automatically use Open Graph values
* Supports `summary_large_image` card type
* Independent or synchronized with Open Graph data

**Schema.org JSON-LD**

* Automatic Schema.org structured data generation
* Includes author information for articles
* ISO 8601 formatted dates
* Image dimensions when available
* Filterable for custom implementations

**Performance & Security**

* Built-in caching system to prevent duplicate database queries
* Conditional asset loading (only loads on supported post types)
* Comprehensive nonce verification and capability checks
* Input sanitization and output escaping throughout
* Directory protection with index.php files

= Developer Friendly =

Meta Override provides several filters for extensibility:

* `meta_override_supported_post_types` - Add support for custom post types
* `meta_override_schema_org` - Modify Schema.org output

= Intelligent Fallbacks =

When custom values aren't provided, Meta Override uses intelligent fallback chains:

* Meta Description: Custom value → Post excerpt → Site tagline
* Open Graph Title: Custom value → Post title
* Twitter Cards: Custom value → Synced OG value → OG value

This ensures your pages always have proper meta tags even if you haven't customized them yet.

== Installation ==

1. Upload the `meta-override` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit any post or page and scroll down to the "Meta Override" meta box
4. Fill in the fields you want to customize and publish/update your content

== Frequently Asked Questions ==

= Does this work with custom post types? =

Yes! Use the `meta_override_supported_post_types` filter to add your custom post types:

`add_filter('meta_override_supported_post_types', function($post_types) {
    $post_types[] = 'product';
    return $post_types;
});`

= Will this conflict with SEO plugins? =

Meta Override outputs its tags with high priority. If you're using another SEO plugin, you may want to disable that plugin's meta tag output for posts/pages where you're using Meta Override.

= What if I don't fill in all the fields? =

No problem! The plugin provides intelligent fallbacks using your post title, excerpt, and WordPress defaults.

= Can I use this for the blog homepage? =

Yes! The plugin automatically detects the "Posts page" setting in WordPress and allows you to override meta tags for it.

= Does this affect my site's performance? =

No. The plugin includes a built-in caching system and only loads admin assets on edit screens for supported post types.

= What happens to my data when I uninstall the plugin? =

When you delete the plugin through WordPress admin, all meta data (with `_mo_` prefix) is automatically removed from your database. Note: Deactivating does NOT remove data, only uninstalling does.

== Screenshots ==

1. Meta Override meta box in the post/page editor
2. Character counters help optimize meta descriptions for SEO
3. WordPress Media Library integration for selecting images
4. Twitter Card fields with sync options

== Changelog ==

= 1.1.1 =
* Updated license to MIT
* Added Author URI

= 1.1.0 =
* Added comprehensive caching system
* Improved security validation
* Added image dimension validation
* Enhanced SEO fallback chain

= 1.0.0 =
* Initial release
* Meta tag override functionality
* Open Graph support
* Twitter Card integration
* Schema.org JSON-LD output

== Upgrade Notice ==

= 1.1.1 =
License updated to MIT. No functionality changes.

= 1.1.0 =
Performance improvements and enhanced security validation. Recommended update for all users.

== Developer Hooks ==

= meta_override_supported_post_types =

Add support for custom post types:

`add_filter('meta_override_supported_post_types', function($post_types) {
    $post_types[] = 'product';
    $post_types[] = 'portfolio';
    return $post_types;
});`

= meta_override_schema_org =

Customize Schema.org structured data:

`add_filter('meta_override_schema_org', function($schema, $post_id) {
    $schema['publisher'] = array(
        '@type' => 'Organization',
        'name' => 'Your Company',
        'logo' => array(
            '@type' => 'ImageObject',
            'url' => 'https://example.com/logo.png'
        )
    );
    return $schema;
}, 10, 2);`

== Database Storage ==

All meta data is stored with the `_mo_` prefix:

* `_mo_meta_title`
* `_mo_meta_description`
* `_mo_og_title`
* `_mo_og_description`
* `_mo_og_image`
* `_mo_og_image_id`
* `_mo_twitter_title`
* `_mo_twitter_description`
* `_mo_twitter_image`
* `_mo_twitter_image_id`
* `_mo_twitter_title_same_as_og`
* `_mo_twitter_description_same_as_og`
* `_mo_twitter_image_same_as_og`

== Support ==

For bug reports and feature requests, please visit the [GitHub repository](https://github.com/a5ah1/meta-override/issues).

== Source Code ==

The source code for this plugin is available on [GitHub](https://github.com/a5ah1/meta-override).
