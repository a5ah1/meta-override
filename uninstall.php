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
 * Delete all plugin meta data from posts
 *
 * This removes all meta keys with the _mo_ prefix from all posts.
 */
function meta_override_delete_plugin_data()
{
  global $wpdb;

  // Meta keys to delete
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
    '_mo_twitter_image_same_as_og'
  );

  // Delete meta data for each key
  foreach ($meta_keys as $meta_key) {
    $wpdb->query(
      $wpdb->prepare(
        "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s",
        $meta_key
      )
    );
  }

  // Delete site-wide settings option
  delete_option('meta_override_settings');

  // Clear any cached data
  wp_cache_flush();
}

// Only delete data if user has chosen to delete plugin data
// You can add an option in the plugin settings to control this behavior
// For now, we'll always clean up on uninstall (WordPress best practice)
meta_override_delete_plugin_data();
