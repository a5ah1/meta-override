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
    if (!$post_id) {
      // Return an array shaped like real meta data but with empty values so
      // callers can read field keys without isset() checks (e.g. on the
      // homepage when there is no associated post).
      return array_fill_keys(Meta_Override_Constants::get_all_fields(), '');
    }

    // Check cache first
    if (isset(self::$meta_cache[$post_id])) {
      return self::$meta_cache[$post_id];
    }

    $meta_data = array();
    foreach (Meta_Override_Constants::get_all_fields() as $field) {
      $meta_key = Meta_Override_Constants::get_meta_key($field);
      $meta_data[$field] = get_post_meta($post_id, $meta_key, true);
    }

    // Cache the result
    self::$meta_cache[$post_id] = $meta_data;

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
   * Clear meta cache for a specific post or all posts
   *
   * @param int|null $post_id Optional post ID. If null, clears all cache.
   * @return void
   * @since 1.1.0
   */
  public static function clear_cache($post_id = null)
  {
    if ($post_id) {
      unset(self::$meta_cache[$post_id]);
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
      return esc_url_raw($value);
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
   * Single source of truth — both admin meta box and settings page consult this.
   *
   * @return array
   * @since 1.3.0
   */
  public static function get_supported_post_types()
  {
    /**
     * Filter the post types that support Meta Override
     *
     * @param array $post_types Array of post type slugs
     * @since 1.1.0
     */
    return apply_filters('meta_override_supported_post_types', array('post', 'page'));
  }
}
