# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.0.0] - 2026-08-12

### Added
- **Content type selection** — a checkbox list on the settings page controls which post types Meta Override applies to. All public post types are enabled by default: 1.3.0 emitted for every public type on the front end (its hardcoded `post` + `page` list only governed the meta box), so a narrower default would have silenced custom post type singles on upgrade. The selection freezes into the option on first save; a type registered after that starts unticked. The meta box now appears on every enabled type, so custom post types gain it on upgrade
- **Taxonomy support** — Meta Override fields on term add/edit screens for any selected public taxonomy, stored in term meta under the same `_mo_` keys
- **Archive output** — category, tag, custom taxonomy, and post type archives emit the OG scaffolding, site-wide fallbacks, and `CollectionPage` schema. Off by default on every install; switch it on under *Archives*
- **Post type archive overrides** — meta title, description, OG title, OG description, and OG image for post types registered with an archive
- **Home page settings** — for sites showing latest posts at the root, where there is no page to attach a meta box to. Hidden when a static front page is configured, with a pointer to that page instead
- `meta_override_supported_taxonomies` filter, mirroring the post types filter
- `Meta_Override_Helper::get_object_meta_data()`, `get_all_term_meta_data()`, `get_supported_taxonomies()`, `get_candidate_post_types()`, `get_candidate_taxonomies()`
- `Meta_Override_Constants::get_term_fields()`, `get_context_fields()`, `get_all_meta_keys()`

### Changed
- **`og:type` is now correct for pages.** Hierarchical post types (including `page`), the home page, the front page, and all archives emit `og:type=website`. Previously every page emitted `og:type=article` along with `article:published_time` and `article:modified_time`; those date tags are now emitted only where the context is genuinely article-like. **This changes live output on existing sites** — it is a correctness fix, not a behaviour toggle
- Unticking a post type stops Meta Override output for it on the front end — its singles and its post type archive — not just in the admin. Values already saved are left in the database untouched and are used again if the type is re-enabled
- Attachment pages no longer emit tags. 1.3.0 emitted for them along with every other public type; attachments are deliberately not selectable as a content type
- Option-backed contexts (the home page and post type archives) have no separate Twitter fields — `twitter:title`, `twitter:description`, and `twitter:image` always mirror the resolved OG values there, including a fallback OG image. The home page can therefore emit a `twitter:image` that 1.3.0 did not
- `meta_override_supported_post_types` now runs *after* the stored setting rather than being the only source. Filter-controlled types render as locked on the settings screen. Existing filter usage keeps working unchanged
- Page context is resolved once, up front, instead of being re-derived separately for `og:type`, the Schema.org `@type`, and the canonical URL
- `meta_override_schema_org` receives a third argument, the resolved page context
- `uninstall.php` derives its meta key list from `Meta_Override_Constants` and sweeps `termmeta` alongside `postmeta`
- Admin assets are enqueued on term screens deliberately rather than incidentally

### Fixed
- The blog index no longer trusts a stale `page_for_posts` when Settings → Reading is set to "Your latest posts". WordPress keeps that value after switching modes, so the home page was rendering an orphaned page's values — its title override, description, and OG fields, not just a wrong `og:url`. All of it is now ignored; sites that relied on the orphan should move those values into the new Home page settings
- `article:published_time` and `article:modified_time` are now genuine UTC timestamps. Since 1.1.0 they carried the site-local wall-clock time with a literal `Z` (UTC) suffix, misstating the moment on any non-UTC site
- The Schema.org `name` no longer contains HTML-entity-encoded characters when a Meta Title override with `&` or quotes is set — the JSON now carries the raw stored value instead of the string escaped for the `<title>` tag
- A saved OG/Twitter image attachment ID whose attachment has since been deleted is now cleared on the next save instead of surviving it
- Post and term meta caches could collide: post 12 and term 12 shared a cache key and served each other's values. Cache keys are now namespaced by object type
- Per-post-type OG image fallbacks are no longer dropped when their post type is unticked
- Post type slugs whose plugin is temporarily deactivated are preserved rather than silently removed from the settings
- A partial `update_option( 'meta_override_settings', … )` no longer resets the keys it omits. Every top-level setting carries its stored value forward when absent from the input, so a programmatic update behaves as a patch; passing a value explicitly — including an empty one — still sets it. Previously any partial update blanked the site-wide OG image and the Twitter handle

## [1.3.0] - 2026-05-15

### Added
- **Settings page** at *Settings → Meta Override* (`manage_options` capability) with contextual help tabs (Overview, Settings reference, Developers) wired via WordPress's native `add_help_tab()` API
- **Site-wide default OG image** — emitted as `og:image` when a post has no featured image and no per-post OG image
- **Per-post-type OG image fallback** — overrides the site-wide default for matching post types (driven by `meta_override_supported_post_types`)
- **`twitter:site` handle** — global setting emitted as `<meta name="twitter:site">`; leading `@` normalized on save
- **Homepage emission** — `og:image`, `twitter:site`, and core OG scaffolding now emit on the default "Show latest posts" home (sites with no Posts page assigned)
- "Settings" action link on the Plugins list row
- Helper API: `Meta_Override_Helper::sanitize_image_id()` and `Meta_Override_Helper::get_supported_post_types()` (single source of truth, used by both the admin meta box and the settings page)
- `META_OVERRIDE_PLUGIN_FILE` constant for `plugin_basename()`-based hook wiring

### Changed
- **OG image resolution** is now strictly: per-post "Use Featured Image" → per-post OG image → per-post-type fallback → site-wide fallback → omit the tag
- **Titles and descriptions are now explicit-only.** `meta description`, `og:title`, `og:description`, `twitter:title`, and `twitter:description` are emitted only when set on the post. If a field is blank the corresponding tag is omitted entirely (previously these auto-filled with post excerpt, post title, or site tagline — that behavior is gone)
- Schema.org JSON-LD now omits the `description` key when no explicit description is set, and uses `CollectionPage` for the homepage when no Posts page is assigned
- `Meta_Override_Helper::get_image_dimensions()` and `Meta_Override_Settings::get_all()` are now memoized per request
- `uninstall.php` removes the `meta_override_settings` option on plugin deletion

### Removed
- `Meta_Override_Helper::get_fallback_title()` and `Meta_Override_Helper::get_fallback_description()` — no longer called after the explicit-only refactor

## [1.2.1] - 2026-04-27

### Changed
- Meta Override meta box now renders at the bottom of the post editor (priority lowered from `high` to `low`)

## [1.1.1] - 2025-01-17

### Changed
- Updated license to MIT
- Added Author URI to plugin header

## [1.1.0] - 2025-01-15

### Added
- **Caching System**: Implemented meta data caching in Helper class to prevent duplicate database queries
- **Helper Class**: New `Meta_Override_Helper` class with utility methods for:
  - `get_all_meta_data()`: Centralized meta data retrieval with caching
  - `sanitize_meta_value()`: Type-aware sanitization
  - `get_fallback_title()`: Fallback title generation
  - `get_fallback_description()`: Fallback description generation
  - `get_image_dimensions()`: Enhanced image validation and dimension retrieval
  - `clear_cache()`: Cache management
- **Developer Hooks**:
  - `meta_override_supported_post_types` filter to customize which post types get meta boxes
  - `meta_override_schema_org` filter to modify Schema.org output before rendering
- **PHPDoc Comments**: Comprehensive documentation added to all classes and methods with `@since`, `@param`, and `@return` tags
- **Security**: Directory protection via index.php files in all plugin directories (`includes/`, `assets/`, `assets/css/`, `assets/js/`)
- **Cleanup**: `uninstall.php` for proper database cleanup when plugin is deleted
- **SEO Fallbacks**: Automatic fallback values for all meta fields when custom values aren't set:
  - Meta description falls back to post excerpt or site tagline
  - OG title falls back to post title
  - Twitter fields fall back to OG fields
- **Schema.org Enhancement**: Author information now included in Schema.org output for articles
- **Internationalization**: Added `__()` wrapper for "Meta Override" string (i18n ready)

### Changed
- **Performance**: Admin assets now only load on supported post type screens (conditional loading)
- **Performance**: Eliminated duplicate `get_all_meta_data()` method from Admin and Public classes (now uses Helper)
- **Security**: Enhanced `save_meta_data()` with comprehensive validation:
  - Added `$_SERVER['REQUEST_METHOD']` check to ensure POST requests
  - Improved capability checking using post type capabilities instead of hardcoded 'edit_post'
  - Added image ID validation using `wp_attachment_is_image()`
  - Post type verification against supported types list
- **Code Quality**: Replaced global `$post` with `get_queried_object_id()` for better performance
- **Code Quality**: Extracted Schema.org output into dedicated `output_schema_org()` method
- **SEO**: Blog page canonical URL now uses `get_permalink()` instead of `home_url('/')` for proper URL handling
- **SEO**: Schema.org dates now use ISO 8601 format (`'c'` parameter)
- **SEO**: JSON encoding now uses `JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE` flags
- **Admin**: Post types now filterable instead of hardcoded array
- **Admin**: Removed unnecessary blog page meta box special case (handled by normal flow)
- **Admin**: Explicitly enqueue `wp_enqueue_media()` for media library

### Fixed
- **Bug**: JavaScript media uploader now correctly handles switching between OG and Twitter image buttons
  - Previously, `customUploader` was reused causing the wrong input to be populated
  - Now creates fresh media uploader instance per button click
- **Bug**: Image dimensions helper now validates that attachment is actually an image before processing
- **Bug**: Cache is now properly cleared after saving post meta data

### Security
- Added REQUEST_METHOD validation in save handler
- Enhanced nonce verification flow
- Image attachment validation prevents non-image IDs
- Improved capability checks using post type object capabilities
- All user inputs properly sanitized using Helper class methods
- All outputs properly escaped with appropriate functions

### Developer Experience
- All classes now have complete PHPDoc documentation
- Code follows WordPress Coding Standards
- Improved code organization and separation of concerns
- Helper methods available for developers to use in custom implementations
- Clear filter documentation with usage examples

## [1.0.0] - Initial Release

### Added
- Basic meta tag override functionality
- Open Graph support for Facebook sharing
- Twitter Card support
- Schema.org JSON-LD structured data
- WordPress Media Library integration
- Character counting for meta fields
- Admin meta box interface
- Twitter/OG field synchronization options
- Support for posts and pages
- Blog homepage support

---

[2.0.0]: https://github.com/a5ah1/meta-override/compare/v1.3.0...v2.0.0
[1.3.0]: https://github.com/a5ah1/meta-override/compare/v1.2.1...v1.3.0
[1.2.1]: https://github.com/a5ah1/meta-override/compare/v1.2.0...v1.2.1
[1.1.1]: https://github.com/a5ah1/meta-override/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/a5ah1/meta-override/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/a5ah1/meta-override/releases/tag/v1.0.0
