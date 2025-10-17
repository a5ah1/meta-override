<?php

/**
 * Main plugin class for Meta Override
 *
 * Orchestrates all plugin functionality by loading dependencies,
 * defining hooks, and initializing admin and public-facing features.
 *
 * @package Meta_Override
 * @since   1.1.0
 */
class Meta_Override
{
  /**
   * The loader that's responsible for maintaining and registering all hooks
   *
   * @var Meta_Override_Loader
   */
  protected $loader;

  /**
   * The unique identifier of this plugin
   *
   * @var string
   */
  protected $plugin_name;

  /**
   * The current version of the plugin
   *
   * @var string
   */
  protected $version;

  /**
   * Initialize the plugin and set its properties
   *
   * @since 1.1.0
   */
  public function __construct()
  {
    if (defined('META_OVERRIDE_VERSION')) {
      $this->version = META_OVERRIDE_VERSION;
    } else {
      $this->version = '1.0.0';
    }
    $this->plugin_name = 'meta-override';

    $this->load_dependencies();
    $this->define_admin_hooks();
    $this->define_public_hooks();
  }

  /**
   * Load required dependencies for this plugin
   *
   * @since 1.1.0
   * @return void
   */
  private function load_dependencies()
  {
    require_once META_OVERRIDE_PLUGIN_DIR . 'includes/class-meta-override-constants.php';
    require_once META_OVERRIDE_PLUGIN_DIR . 'includes/class-meta-override-loader.php';
    require_once META_OVERRIDE_PLUGIN_DIR . 'includes/class-meta-override-admin.php';
    require_once META_OVERRIDE_PLUGIN_DIR . 'includes/class-meta-override-public.php';
    require_once META_OVERRIDE_PLUGIN_DIR . 'includes/class-meta-override-helper.php';

    $this->loader = new Meta_Override_Loader();
  }

  /**
   * Register all admin-related hooks
   *
   * @since 1.1.0
   * @return void
   */
  private function define_admin_hooks()
  {
    $plugin_admin = new Meta_Override_Admin($this->get_plugin_name(), $this->get_version());

    $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
    $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_meta_boxes');
    $this->loader->add_action('save_post', $plugin_admin, 'save_meta_data');
  }

  /**
   * Register all public-facing hooks
   *
   * @since 1.1.0
   * @return void
   */
  private function define_public_hooks()
  {
    $plugin_public = new Meta_Override_Public($this->get_plugin_name(), $this->get_version());

    $this->loader->add_filter('pre_get_document_title', $plugin_public, 'override_title', 10);
    $this->loader->add_action('wp_head', $plugin_public, 'output_meta_tags', 1);
  }

  /**
   * Run the loader to execute all hooks
   *
   * @since 1.1.0
   * @return void
   */
  public function run()
  {
    $this->loader->run();
  }

  /**
   * Get the plugin name
   *
   * @return string The plugin name
   * @since 1.1.0
   */
  public function get_plugin_name()
  {
    return $this->plugin_name;
  }

  /**
   * Get the plugin version
   *
   * @return string The plugin version
   * @since 1.1.0
   */
  public function get_version()
  {
    return $this->version;
  }
}
