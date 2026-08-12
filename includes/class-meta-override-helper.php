<?php

/**
 * Helper class for Meta Override plugin
 *
 * Provides utility functions for meta data retrieval, image handling,
 * and caching functionality.
 *
 * @package Meta_Override
 * @since   1.1.0
 */
class Meta_Override_Helper
{
  /**
   * Cache for meta data to avoid multiple database queries
   *
   * Keyed "{object_type}:{object_id}" — a bare ID would let post 12 and
   * term 12 collide and serve each other's meta.
   *
   * @var array
   */
  private static $meta_cache = array();

  /**
   * Cache for image dimensions keyed by attachment ID
   *
   * @var array
   */
  private static $dimensions_cache = array();

  /**
   * Get all meta data for a post with caching
   *
   * @param int $post_id The post ID
   * @return array Array of meta field values
   * @since 1.1.0
   */
  public static function get_all_meta_data($post_id)
  {
    return self::get_object_meta_data($post_id, Meta_Override_Constants::OBJECT_POST);
  }

  /**
   * Get all meta data for a term with caching
   *
   * @param int $term_id The term ID
   * @return array Array of meta field values
   * @since 2.0.0
   */
  public static function get_all_term_meta_data($term_id)
  {
    return self::get_object_meta_data($term_id, Meta_Override_Constants::OBJECT_TERM);
  }

  /**
   * Get all meta data for a post or term with caching
   *
   * Always returns the full field shape — fields that don't apply to the
   * object type come back empty — so callers can read any key without
   * isset() checks regardless of what they were handed.
   *
   * @param int    $object_id   The post or term ID
   * @param string $object_type Meta_Override_Constants::OBJECT_POST or OBJECT_TERM
   * @return array Array of meta field values
   * @since 2.0.0
   */
  public static function get_object_meta_data($object_id, $object_type = Meta_Override_Constants::OBJECT_POST)
  {
    $all_fields = Meta_Override_Constants::get_all_fields();

    if (!$object_id) {
      // Return an array shaped like real meta data but with empty values so
      // callers can read field keys without isset() checks (e.g. on the
      // homepage when there is no associated post).
      return array_fill_keys($all_fields, '');
    }

    $cache_key = $object_type . ':' . (int) $object_id;

    // Check cache first
    if (isset(self::$meta_cache[$cache_key])) {
      return self::$meta_cache[$cache_key];
    }

    $is_term = ($object_type === Meta_Override_Constants::OBJECT_TERM);
    $readable = $is_term
      ? Meta_Override_Constants::get_term_fields()
      : $all_fields;

    $meta_data = array_fill_keys($all_fields, '');
    foreach ($readable as $field) {
      $meta_key = Meta_Override_Constants::get_meta_key($field);
      $meta_data[$field] = $is_term
        ? get_term_meta($object_id, $meta_key, true)
        : get_post_meta($object_id, $meta_key, true);
    }

    // Cache the result
    self::$meta_cache[$cache_key] = $meta_data;

    return $meta_data;
  }

  /**
   * Get image dimensions from attachment ID
   *
   * @param int $image_id The attachment ID
   * @return array|false Array with 'width' and 'height' keys, or false on failure
   * @since 1.1.0
   */
  public static function get_image_dimensions($image_id)
  {
    if (!$image_id || !is_numeric($image_id)) {
      return false;
    }

    $image_id = (int) $image_id;

    if (array_key_exists($image_id, self::$dimensions_cache)) {
      return self::$dimensions_cache[$image_id];
    }

    $result = false;

    if (wp_attachment_is_image($image_id)) {
      $image_data = wp_get_attachment_image_src($image_id, 'full');
      if ($image_data && isset($image_data[1], $image_data[2])) {
        $result = array(
          'width' => $image_data[1],
          'height' => $image_data[2],
        );
      }
    }

    self::$dimensions_cache[$image_id] = $result;
    return $result;
  }

  /**
   * Clear meta cache for a specific object or all objects
   *
   * @param int|null $object_id   Optional object ID. If null, clears all cache.
   * @param string   $object_type Object type the ID belongs to
   * @return void
   * @since 1.1.0
   */
  public static function clear_cache($object_id = null, $object_type = Meta_Override_Constants::OBJECT_POST)
  {
    if ($object_id) {
      unset(self::$meta_cache[$object_type . ':' . (int) $object_id]);
    } else {
      self::$meta_cache = array();
    }
  }

  /**
   * Sanitize and validate meta field value based on field type
   *
   * @param string $field The field name constant
   * @param mixed  $value The value to sanitize
   * @return string Sanitized value
   * @since 1.1.0
   */
  public static function sanitize_meta_value($field, $value)
  {
    if (in_array($field, Meta_Override_Constants::get_checkbox_fields(), true)) {
      return $value ? 'on' : 'off';
    } elseif (in_array($field, Meta_Override_Constants::get_url_fields(), true)) {
      // esc_url_raw() fatals on an array; a crafted POST can send one.
      return is_scalar($value) ? esc_url_raw((string) $value) : '';
    } elseif (in_array($field, Meta_Override_Constants::get_textarea_fields(), true)) {
      return sanitize_textarea_field($value);
    } else {
      return sanitize_text_field($value);
    }
  }

  /**
   * Validate an attachment ID and return it as int, or 0 if invalid
   *
   * @param mixed $id Raw image ID input
   * @return int Valid image attachment ID, or 0
   * @since 1.3.0
   */
  public static function sanitize_image_id($id)
  {
    $id = absint($id);
    return ($id && wp_attachment_is_image($id)) ? $id : 0;
  }

  /**
   * Get the list of post types Meta Override supports
   *
   * Single source of truth — the admin meta box, the save guard, the settings
   * page and the front-end output all consult this. The stored setting supplies
   * the list; the filter runs last so code can still add or remove types.
   *
   * @return array
   * @since 1.3.0
   */
  public static function get_supported_post_types()
  {
    $post_types = Meta_Override_Settings::get_post_types();

    /**
     * Filter the post types that support Meta Override
     *
     * Runs after the stored setting, so a type added here is enabled even if
     * its box is unticked on the settings screen (and shows as locked there).
     *
     * @param array $post_types Array of post type slugs
     * @since 1.1.0
     */
    $post_types = apply_filters('meta_override_supported_post_types', $post_types);

    return is_array($post_types) ? array_values(array_unique($post_types)) : array();
  }

  /**
   * Get the list of taxonomies Meta Override supports
   *
   * @return array
   * @since 2.0.0
   */
  public static function get_supported_taxonomies()
  {
    $taxonomies = Meta_Override_Settings::get_taxonomies();

    /**
     * Filter the taxonomies that support Meta Override
     *
     * Runs after the stored setting, mirroring
     * meta_override_supported_post_types.
     *
     * @param array $taxonomies Array of taxonomy slugs
     * @since 2.0.0
     */
    $taxonomies = apply_filters('meta_override_supported_taxonomies', $taxonomies);

    return is_array($taxonomies) ? array_values(array_unique($taxonomies)) : array();
  }

  /**
   * Get the post types offered on the settings screen
   *
   * @return array Array of WP_Post_Type objects keyed by slug
   * @since 2.0.0
   */
  public static function get_candidate_post_types()
  {
    $candidates = get_post_types(array('public' => true, 'show_ui' => true), 'objects');

    // Attachments qualify on both flags but a meta box in the media modal is
    // not something anyone wants.
    unset($candidates['attachment']);

    return $candidates;
  }

  /**
   * Get the taxonomies offered on the settings screen
   *
   * @return array Array of WP_Taxonomy objects keyed by slug
   * @since 2.0.0
   */
  public static function get_candidate_taxonomies()
  {
    $candidates = get_taxonomies(array('public' => true, 'show_ui' => true), 'objects');

    // Public, but meaningless as a shareable archive.
    unset($candidates['post_format']);

    return $candidates;
  }
}
