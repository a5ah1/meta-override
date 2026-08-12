<?php

/**
 * Fired when the plugin is uninstalled
 *
 * @package Meta_Override
 * @since   1.1.0
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
  exit;
}

/**
 * Delete all plugin meta data from posts and terms
 *
 * The key list is derived from Meta_Override_Constants rather than repeated
 * here, so it can never drift from the fields the plugin actually writes.
 */
function meta_override_delete_plugin_data()
{
  global $wpdb;

  $constants_file = plugin_dir_path(__FILE__) . 'includes/class-meta-override-constants.php';
  if (file_exists($constants_file)) {
    require_once $constants_file;
  }

  if (class_exists('Meta_Override_Constants')) {
    $meta_keys = Meta_Override_Constants::get_all_meta_keys();
    $settings_option = Meta_Override_Constants::SETTINGS_OPTION;
  } else {
    // A partially deleted or damaged install must still clean up rather than
    // fatal and silently leave everything behind. Frozen copy of the 2.0.0
    // key list — update alongside Meta_Override_Constants::get_all_fields().
    $meta_keys = array(
      '_mo_meta_title',
      '_mo_meta_description',
      '_mo_og_title',
      '_mo_og_description',
      '_mo_og_image',
      '_mo_og_image_id',
      '_mo_og_image_use_featured',
      '_mo_twitter_title',
      '_mo_twitter_description',
      '_mo_twitter_image',
      '_mo_twitter_image_id',
      '_mo_twitter_title_same_as_og',
      '_mo_twitter_description_same_as_og',
      '_mo_twitter_image_same_as_og',
    );
    $settings_option = 'meta_override_settings';
  }

  // Legacy keys from versions that wrote fields no longer defined above.
  // Nothing here today, but the merge keeps removals safe.
  $legacy_keys = array();

  $meta_keys = array_unique(array_merge($meta_keys, $legacy_keys));

  foreach ($meta_keys as $meta_key) {
    $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
        $meta_key
      )
    );

    $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM {$wpdb->termmeta} WHERE meta_key = %s",
        $meta_key
      )
    );
  }

  // Delete site-wide settings option
  delete_option($settings_option);

  // Clear any cached data
  wp_cache_flush();
}

// Only delete data if user has chosen to delete plugin data
// You can add an option in the plugin settings to control this behavior
// For now, we'll always clean up on uninstall (WordPress best practice)
meta_override_delete_plugin_data();
