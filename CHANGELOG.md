# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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

[1.2.1]: https://github.com/a5ah1/meta-override/compare/v1.2.0...v1.2.1
[1.1.1]: https://github.com/a5ah1/meta-override/compare/v1.1.0...v1.1.1
[1.1.0]: https://github.com/a5ah1/meta-override/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/a5ah1/meta-override/releases/tag/v1.0.0
