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
   * Get all meta data for a post with caching
   *
   * @param int $post_id The post ID
   * @return array Array of meta field values
   * @since 1.1.0
   */
  public static function get_all_meta_data($post_id)
  {
    if (!$post_id) {
      return array();
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

    // Verify this is actually an image attachment
    if (wp_attachment_is_image($image_id) === false) {
      return false;
    }

    $image_data = wp_get_attachment_image_src($image_id, 'full');
    if ($image_data && isset($image_data[1], $image_data[2])) {
      return array(
        'width' => $image_data[1],
        'height' => $image_data[2]
      );
    }

    return false;
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
   * Get fallback meta title for a post
   *
   * @param int $post_id The post ID
   * @return string The fallback title
   * @since 1.1.0
   */
  public static function get_fallback_title($post_id)
  {
    if (!$post_id) {
      return get_bloginfo('name');
    }

    $post = get_post($post_id);
    if (!$post) {
      return get_bloginfo('name');
    }

    return get_the_title($post_id) . ' - ' . get_bloginfo('name');
  }

  /**
   * Get fallback meta description for a post
   *
   * @param int $post_id The post ID
   * @return string The fallback description
   * @since 1.1.0
   */
  public static function get_fallback_description($post_id)
  {
    if (!$post_id) {
      return get_bloginfo('description');
    }

    $excerpt = get_the_excerpt($post_id);
    if ($excerpt) {
      return wp_strip_all_tags($excerpt);
    }

    return get_bloginfo('description');
  }
}
