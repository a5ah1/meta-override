=== Meta Override ===
Contributors: a5ah1
Tags: meta tags, open graph, twitter cards, schema, seo
Requires at least: 5.0
Tested up to: 7.0.4
Requires PHP: 7.0
Stable tag: 2.0.0
License: MIT
License URI: https://opensource.org/licenses/MIT

Override meta tags, Open Graph data, Twitter Cards, and add Schema.org structured data for posts, pages, custom post types, taxonomy terms, and archives.

== Description ==

Meta Override is a lightweight WordPress plugin that gives you control over your site's meta tags, Open Graph data, Twitter Cards, and Schema.org structured data. Choose exactly which post types and taxonomies it applies to, and set site-wide fallbacks for everything else.

= Key Features =

**Meta Tag Management**

* Override document titles and meta descriptions per post, page, custom post type, or taxonomy term
* Character counter for the title and description fields in the post and term editors
* Tags are emitted only when you set a value — nothing is synthesized to fill space

**Open Graph Support**

* Custom Open Graph titles, descriptions, and images
* Automatic image dimensions in meta tags
* WordPress Media Library integration for easy image selection
* Site-wide, per-post-type, and featured-image fallbacks

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
* Conditional asset loading (only on screens for enabled post types and taxonomies)
* Comprehensive nonce verification and capability checks
* Input sanitization and output escaping throughout
* Directory protection with index.php files

= Developer Friendly =

Meta Override provides several filters for extensibility:

* `meta_override_supported_post_types` - Add or remove post types in code
* `meta_override_supported_taxonomies` - Add or remove taxonomies in code
* `meta_override_schema_org` - Modify Schema.org output

= Resolution rules =

Meta Override emits tags only when an explicit value is available, with one exception:

* Meta Description, OG Title, OG Description, Twitter Title, Twitter Description: emitted only when set on the post, term, or context (home page / post type archive settings). If blank, the tag is omitted.
* Open Graph Image (cascades): "Use Featured Image" on the post → per-post or per-term OG Image field → per-post-type fallback (applies to posts and the Posts page) → site-wide fallback → omit.
* Twitter title/description/image with the "Same as OG…" checkbox: mirrors the resolved OG value, or is omitted if the OG value is blank. Home page and post type archive values always mirror their OG values.
* og:type: flat post types emit `article` with `article:published_time` / `article:modified_time`; hierarchical types, the home page, the front page, and all archives emit `website` with no date tags.

== Installation ==

1. Upload the `meta-override` directory to your `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Edit any post or page and scroll down to the "Meta Override" meta box
4. Fill in the fields you want to customize and publish/update your content

== Frequently Asked Questions ==

= Does this work with custom post types? =

Yes — all public post types are enabled by default, and you can change the selection under *Settings → Meta Override → Content types*. The `meta_override_supported_post_types` filter still works and runs after the checkboxes, so it has the final say:

`add_filter('meta_override_supported_post_types', function($post_types) {
    $post_types[] = 'product';
    return $post_types;
});`

= Will this conflict with SEO plugins? =

Meta Override outputs its tags with high priority. If you're using another SEO plugin, you may want to disable that plugin's meta tag output for posts/pages where you're using Meta Override.

= What if I don't fill in all the fields? =

Tags you leave blank are simply omitted — nothing is synthesized from your content to fill space. The one cascade is the OG image, which falls back from the post's own value to the per-post-type and site-wide defaults set under Settings → Meta Override.

= Can I use this for the blog homepage? =

Yes. If a Posts page is assigned under Settings → Reading, edit that page's Meta Override box. If your site shows the latest posts at the root, use the Home page section under Settings → Meta Override instead.

= Does this affect my site's performance? =

No. The plugin includes a built-in caching system and only loads admin assets on edit screens for enabled post types and taxonomies.

= What happens to my data when I uninstall the plugin? =

When you delete the plugin through WordPress admin, all of its post meta and term meta (keys with the `_mo_` prefix) and the `meta_override_settings` option are removed from your database. Note: Deactivating does NOT remove data, only uninstalling does.

== Screenshots ==

1. Meta Override meta box in the post/page editor
2. Character counters help optimize meta descriptions for SEO
3. WordPress Media Library integration for selecting images
4. Twitter Card fields with sync options

== Changelog ==

= 2.0.0 =
* Added: choose which post types Meta Override applies to, from the settings page. All public post types are enabled by default, matching what previous versions emitted for
* Added: Meta Override fields on taxonomy term screens
* Added: output on category, tag, custom taxonomy, and post type archives (off by default)
* Added: home page settings for sites that show latest posts at the root
* Changed: pages and front pages now emit `og:type=website` instead of `article`, and no longer emit `article:published_time` / `article:modified_time`. This changes live output
* Changed: unticking a post type stops output for it on the front end — its singles and its post type archive; saved values are kept
* Changed: attachment pages no longer emit tags (attachments are not selectable as a content type)
* Fixed: a stale `page_for_posts` no longer feeds the home page after switching Reading back to "latest posts" — the orphaned page's values are ignored entirely; use the new Home page settings instead
* Fixed: `article:published_time` / `article:modified_time` are now genuine UTC timestamps; previously they carried local time with a UTC marker
* Fixed: post and term meta caches could collide on matching IDs

= 1.3.0 =
* Added Settings → Meta Override page for site-wide configuration, with contextual help tabs (Overview, Settings reference, Developers)
* Added site-wide OG image fallback used when a post has no featured image and no per-post OG image
* Added per-post-type OG image fallback (driven by `meta_override_supported_post_types`)
* Added `twitter:site` handle setting, emitted as `<meta name="twitter:site">` on every page
* Added homepage emission on sites configured with the default "Show latest posts" home (no Posts page assigned)
* Added "Settings" link to the Plugins list row
* **Behavior change**: `meta description`, `og:title`, `og:description`, `twitter:title`, and `twitter:description` now emit only when set on the post. Previously these auto-filled from post excerpt, post title, or site tagline. If you relied on the previous auto-fill, set those fields explicitly or expect the tags to be absent
* Schema.org JSON-LD omits the `description` key when no explicit description is set

= 1.2.1 =
* Moved the Meta Override meta box to the bottom of the post editor (priority changed from "high" to "low")

= 1.2.0 =
* Added "Use Featured Image" option for OG images — resolves at render time so changing the featured image updates OG/Twitter output automatically
* Twitter image cascades naturally via existing "Same as OG Image" sync
* Improved admin UI: consistent section grouping, bolder labels, clearer disabled field states with smooth transitions, and earlier side-by-side layout breakpoint

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

= 2.0.0 =
Adds custom post type, taxonomy, and archive support. All public post types are enabled by default, matching previous front-end behaviour, and saved values carry over unchanged. Pages now emit og:type=website rather than article, attachment pages no longer emit tags, and article timestamps are now true UTC — correctness fixes that change live output.

= 1.3.0 =
New Settings page with site-wide and per-post-type OG image fallbacks, plus a twitter:site handle. **Behavior change**: meta description, og:title, og:description, and Twitter title/description are now emitted only when set on the post. If you relied on the previous auto-fill (post excerpt, post title, site tagline), set those fields explicitly or those tags will be absent.

= 1.2.1 =
Meta Override meta box now appears at the bottom of the post editor instead of the top.

= 1.2.0 =
New "Use Featured Image" option for OG images. Featured image changes take effect without re-saving meta fields.

= 1.1.1 =
License updated to MIT. No functionality changes.

= 1.1.0 =
Performance improvements and enhanced security validation. Recommended update for all users.

== Developer Hooks ==

= meta_override_supported_post_types =

Add or remove post types in code. Runs after the Content types setting, so it has the final say; filter-controlled types show as locked on the settings screen:

`add_filter('meta_override_supported_post_types', function($post_types) {
    $post_types[] = 'product';
    $post_types[] = 'portfolio';
    return $post_types;
});`

= meta_override_supported_taxonomies =

The same arrangement for taxonomies:

`add_filter('meta_override_supported_taxonomies', function($taxonomies) {
    $taxonomies[] = 'product_cat';
    return $taxonomies;
});`

= meta_override_schema_org =

Customize Schema.org structured data. Receives the schema array, the post ID (0 where the context has no post), and the resolved page context:

`add_filter('meta_override_schema_org', function($schema, $post_id, $context) {
    $schema['publisher'] = array(
        '@type' => 'Organization',
        'name' => 'Your Company',
        'logo' => array(
            '@type' => 'ImageObject',
            'url' => 'https://example.com/logo.png'
        )
    );
    return $schema;
}, 10, 3);`

== Database Storage ==

Per-post and per-term values are stored as post meta and term meta with the `_mo_` prefix:

* `_mo_meta_title`
* `_mo_meta_description`
* `_mo_og_title`
* `_mo_og_description`
* `_mo_og_image`
* `_mo_og_image_id`
* `_mo_og_image_use_featured`
* `_mo_twitter_title`
* `_mo_twitter_description`
* `_mo_twitter_image`
* `_mo_twitter_image_id`
* `_mo_twitter_title_same_as_og`
* `_mo_twitter_description_same_as_og`
* `_mo_twitter_image_same_as_og`

Site-wide fallbacks, the content type and taxonomy selection, home page values, and post type archive overrides live in a single `meta_override_settings` option. Uninstalling removes all of the above.

== Support ==

For bug reports and feature requests, please visit the [GitHub repository](https://github.com/a5ah1/meta-override/issues).

== Source Code ==

The source code for this plugin is available on [GitHub](https://github.com/a5ah1/meta-override).
