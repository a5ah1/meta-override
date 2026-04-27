<?php

/**
 * Plugin Name: Meta Override
 * Description: A plugin to override meta tags, Open Graph data, and add Schema.org structured data.
 * Version: 1.2.1
 * Author: a5ah1
 * Author URI: https://github.com/a5ah1/meta-override
 * License: MIT
 * License URI: https://opensource.org/licenses/MIT
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
  die;
}

define('META_OVERRIDE_VERSION', '1.2.1');
define('META_OVERRIDE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('META_OVERRIDE_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require META_OVERRIDE_PLUGIN_DIR . 'includes/class-meta-override.php';

// Load Plugin Update Checker
require_once plugin_dir_path(__FILE__) . 'vendor/yahnis-elsts/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * Begins execution of the plugin.
 */
function run_meta_override()
{
  $plugin = new Meta_Override();
  $plugin->run();
}
add_action('plugins_loaded', 'run_meta_override');

/**
 * Initialize automatic updates from GitHub
 */
function meta_override_updates_init()
{
  $updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/a5ah1/meta-override/',
    __FILE__,
    'meta-override'
  );

  // Set the branch that contains the stable release
  $updateChecker->setBranch('master');

  // Use the attached ZIP asset from releases (includes vendor/ directory)
  $updateChecker->getVcsApi()->enableReleaseAssets();
}
add_action('plugins_loaded', 'meta_override_updates_init');
