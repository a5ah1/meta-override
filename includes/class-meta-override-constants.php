<?php

/**
 * Constants for Meta Override plugin
 *
 * Defines all field names and provides helper methods for field categorization.
 *
 * @package Meta_Override
 * @since   1.1.0
 */
class Meta_Override_Constants
{
  /**
   * Meta key prefix for all plugin fields
   */
  const META_PREFIX = '_mo_';

  /**
   * Option name where site-wide settings are stored (single array option)
   */
  const SETTINGS_OPTION = 'meta_override_settings';

  /**
   * Settings keys (used inside the SETTINGS_OPTION array)
   */
  const SETTING_OG_IMAGE_URL = 'og_image_url';
  const SETTING_OG_IMAGE_ID = 'og_image_id';
  const SETTING_OG_IMAGE_BY_POST_TYPE = 'og_image_by_post_type';
  const SETTING_TWITTER_SITE = 'twitter_site';

  /**
   * Field name constants
   */
  const FIELD_META_TITLE = 'meta_title';
  const FIELD_META_DESCRIPTION = 'meta_description';
  const FIELD_OG_TITLE = 'og_title';
  const FIELD_OG_DESCRIPTION = 'og_description';
  const FIELD_OG_IMAGE = 'og_image';
  const FIELD_OG_IMAGE_ID = 'og_image_id';
  const FIELD_OG_IMAGE_USE_FEATURED = 'og_image_use_featured';
  const FIELD_TWITTER_TITLE = 'twitter_title';
  const FIELD_TWITTER_DESCRIPTION = 'twitter_description';
  const FIELD_TWITTER_IMAGE = 'twitter_image';
  const FIELD_TWITTER_IMAGE_ID = 'twitter_image_id';
  const FIELD_TWITTER_TITLE_SYNC = 'twitter_title_same_as_og';
  const FIELD_TWITTER_DESCRIPTION_SYNC = 'twitter_description_same_as_og';
  const FIELD_TWITTER_IMAGE_SYNC = 'twitter_image_same_as_og';

  /**
   * Get all field names
   *
   * @return array Array of all field name constants
   * @since 1.1.0
   */
  public static function get_all_fields()
  {
    return array(
      self::FIELD_META_TITLE,
      self::FIELD_META_DESCRIPTION,
      self::FIELD_OG_TITLE,
      self::FIELD_OG_DESCRIPTION,
      self::FIELD_OG_IMAGE,
      self::FIELD_OG_IMAGE_ID,
      self::FIELD_OG_IMAGE_USE_FEATURED,
      self::FIELD_TWITTER_TITLE,
      self::FIELD_TWITTER_DESCRIPTION,
      self::FIELD_TWITTER_IMAGE,
      self::FIELD_TWITTER_IMAGE_ID,
      self::FIELD_TWITTER_TITLE_SYNC,
      self::FIELD_TWITTER_DESCRIPTION_SYNC,
      self::FIELD_TWITTER_IMAGE_SYNC
    );
  }

  /**
   * Get the meta key for a field (adds prefix)
   *
   * @param string $field The field name
   * @return string The meta key with prefix
   * @since 1.1.0
   */
  public static function get_meta_key($field)
  {
    return self::META_PREFIX . $field;
  }

  /**
   * Get fields that are checkboxes
   *
   * @return array Array of checkbox field names
   * @since 1.1.0
   */
  public static function get_checkbox_fields()
  {
    return array(
      self::FIELD_OG_IMAGE_USE_FEATURED,
      self::FIELD_TWITTER_TITLE_SYNC,
      self::FIELD_TWITTER_DESCRIPTION_SYNC,
      self::FIELD_TWITTER_IMAGE_SYNC
    );
  }

  /**
   * Get fields that contain URLs
   *
   * @return array Array of URL field names
   * @since 1.1.0
   */
  public static function get_url_fields()
  {
    return array(
      self::FIELD_OG_IMAGE,
      self::FIELD_TWITTER_IMAGE
    );
  }

  /**
   * Get fields that are textareas
   *
   * @return array Array of textarea field names
   * @since 1.1.0
   */
  public static function get_textarea_fields()
  {
    return array(
      self::FIELD_META_DESCRIPTION,
      self::FIELD_OG_DESCRIPTION,
      self::FIELD_TWITTER_DESCRIPTION
    );
  }
}