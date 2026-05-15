<?php

/**
 * Site-wide settings for Meta Override plugin
 *
 * Registers the Settings → Meta Override page, persists a single array
 * option, and exposes helpers for reading individual fallback values.
 *
 * @package Meta_Override
 * @since   1.3.0
 */
class Meta_Override_Settings
{
  /**
   * The plugin name
   *
   * @var string
   */
  private $plugin_name;

  /**
   * The plugin version
   *
   * @var string
   */
  private $version;

  /**
   * Menu slug for the settings page
   *
   * @var string
   */
  const MENU_SLUG = 'meta-override-settings';

  /**
   * Capability required to view/save settings
   *
   * @var string
   */
  const CAPABILITY = 'manage_options';

  public function __construct($plugin_name, $version)
  {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  /**
   * Register the option with the Settings API
   *
   * @return void
   */
  public function register_settings()
  {
    register_setting(
      'meta_override_settings_group',
      Meta_Override_Constants::SETTINGS_OPTION,
      array(
        'type' => 'array',
        'sanitize_callback' => array($this, 'sanitize_settings'),
        'default' => self::get_defaults(),
      )
    );
  }

  /**
   * Add the settings submenu under Settings
   *
   * @return void
   */
  public function add_settings_page()
  {
    $hook_suffix = add_options_page(
      __('Meta Override', 'meta-override'),
      __('Meta Override', 'meta-override'),
      self::CAPABILITY,
      self::MENU_SLUG,
      array($this, 'render_settings_page')
    );

    if ($hook_suffix) {
      add_action('load-' . $hook_suffix, array($this, 'register_help_tabs'));
    }
  }

  /**
   * Register contextual help tabs on the settings screen
   *
   * Fires on load- so get_current_screen() is available.
   *
   * @return void
   */
  public function register_help_tabs()
  {
    $screen = get_current_screen();
    if (!$screen) {
      return;
    }

    $screen->add_help_tab(array(
      'id' => 'meta-override-overview',
      'title' => __('Overview', 'meta-override'),
      'content' => $this->get_help_overview(),
    ));

    $screen->add_help_tab(array(
      'id' => 'meta-override-settings',
      'title' => __('Settings reference', 'meta-override'),
      'content' => $this->get_help_settings_reference(),
    ));

    $screen->add_help_tab(array(
      'id' => 'meta-override-developers',
      'title' => __('Developers', 'meta-override'),
      'content' => $this->get_help_developers(),
    ));

    $screen->set_help_sidebar($this->get_help_sidebar());
  }

  /**
   * Help tab content: Overview / fallback rules
   *
   * @return string
   */
  private function get_help_overview()
  {
    return '
      <p><strong>Meta Override</strong> emits <code>meta description</code>, Open Graph, Twitter Card, and Schema.org JSON-LD tags. It uses strict fallback chains &mdash; no tag is synthesized just to fill space.</p>

      <h4>OG image fallback chain</h4>
      <ol>
        <li>Featured image &mdash; when <em>Use Featured Image</em> is checked on the post</li>
        <li>Per-post <em>OG Image</em> field</li>
        <li>Per-post-type fallback &mdash; set below for each supported post type</li>
        <li>Site-wide <em>Default OG Image</em> &mdash; set below</li>
        <li>Otherwise no <code>og:image</code> tag is emitted</li>
      </ol>

      <h4>Titles &amp; descriptions are explicit-only</h4>
      <p>The <code>meta description</code>, <code>og:title</code>, <code>og:description</code>, <code>twitter:title</code>, and <code>twitter:description</code> tags are emitted <strong>only when set on the post</strong>. If a field is blank, the corresponding tag is omitted entirely. WordPress&#8217;s default <code>&lt;title&gt;</code> still applies unless you override it with <em>Meta Title</em> on the post.</p>

      <h4>Twitter cascade</h4>
      <p>When the <em>Same as OG&hellip;</em> checkbox is on, the Twitter tag mirrors the resolved OG tag. If the corresponding OG tag is blank, the Twitter tag is skipped too.</p>

      <h4>Where tags are emitted</h4>
      <p>On singular posts and pages, and on the site home / blog index. Other archives (category, tag, author, search) are left alone.</p>
    ';
  }

  /**
   * Help tab content: per-field reference for the Settings form
   *
   * @return string
   */
  private function get_help_settings_reference()
  {
    return '
      <dl>
        <dt><strong>Default OG Image</strong></dt>
        <dd>Used as <code>og:image</code> when a post has no featured image, no per-post OG image, and no per-post-type fallback set below.</dd>

        <dt><strong>Twitter / X Site Handle</strong></dt>
        <dd>Emitted as <code>&lt;meta name="twitter:site"&gt;</code> on every page where Meta Override runs. Leading <code>@</code> is normalized automatically &mdash; type the handle with or without it.</dd>

        <dt><strong>Per-post-type OG image fallback</strong></dt>
        <dd>When set, takes precedence over the site-wide default for posts of that type. Per-post values still win over both.</dd>
      </dl>

      <p>To register a custom post type for these settings, use the <code>meta_override_supported_post_types</code> filter (see the <em>Developers</em> tab).</p>
    ';
  }

  /**
   * Help tab content: developer filter reference
   *
   * @return string
   */
  private function get_help_developers()
  {
    return '
      <p>Two filters are available for extending Meta Override:</p>

      <h4><code>meta_override_supported_post_types</code></h4>
      <p>Add custom post types to the meta box and per-post-type fallback list.</p>
      <pre><code>add_filter( \'meta_override_supported_post_types\', function( $types ) {
    $types[] = \'product\';
    return $types;
} );</code></pre>

      <h4><code>meta_override_schema_org</code></h4>
      <p>Modify the Schema.org array before it is rendered as JSON-LD. Receives <code>$schema</code> and <code>$post_id</code>.</p>
      <pre><code>add_filter( \'meta_override_schema_org\', function( $schema, $post_id ) {
    $schema[\'publisher\'] = array(
        \'@type\' =&gt; \'Organization\',
        \'name\'  =&gt; \'Your Company\',
    );
    return $schema;
}, 10, 2 );</code></pre>
    ';
  }

  /**
   * Help sidebar (right-hand column)
   *
   * @return string
   */
  private function get_help_sidebar()
  {
    $version = defined('META_OVERRIDE_VERSION') ? META_OVERRIDE_VERSION : '';
    $version_line = $version ? '<p><strong>Meta Override</strong> v' . esc_html($version) . '</p>' : '';

    return $version_line . '
      <p><strong>' . esc_html__('More info', 'meta-override') . '</strong></p>
      <p><a href="https://github.com/a5ah1/meta-override" target="_blank" rel="noopener noreferrer">GitHub repository</a></p>
      <p><a href="https://github.com/a5ah1/meta-override/issues" target="_blank" rel="noopener noreferrer">Report an issue</a></p>
    ';
  }

  /**
   * Enqueue admin assets on the settings page only
   *
   * @param string $hook_suffix
   * @return void
   */
  public function enqueue_assets($hook_suffix)
  {
    if ($hook_suffix !== 'settings_page_' . self::MENU_SLUG) {
      return;
    }

    wp_enqueue_media();

    wp_enqueue_style(
      $this->plugin_name,
      META_OVERRIDE_PLUGIN_URL . 'assets/css/admin-styles.css',
      array(),
      $this->version,
      'all'
    );

    wp_enqueue_script(
      $this->plugin_name,
      META_OVERRIDE_PLUGIN_URL . 'assets/js/admin-script.js',
      array('jquery'),
      $this->version,
      false
    );
  }

  /**
   * Add a "Settings" link to the plugin row on the Plugins page
   *
   * @param array $links Existing action links
   * @return array
   */
  public function add_plugin_action_links($links)
  {
    $settings_url = admin_url('options-general.php?page=' . self::MENU_SLUG);
    $settings_link = '<a href="' . esc_url($settings_url) . '">' . esc_html__('Settings', 'meta-override') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
  }

  /**
   * Render the settings page
   *
   * @return void
   */
  public function render_settings_page()
  {
    if (!current_user_can(self::CAPABILITY)) {
      return;
    }

    $settings = self::get_all();
    $post_types = Meta_Override_Helper::get_supported_post_types();

    $site_url = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_URL];
    $site_id = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_ID];
    $twitter_site = $settings[Meta_Override_Constants::SETTING_TWITTER_SITE];
    $by_type = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE];

    $option = Meta_Override_Constants::SETTINGS_OPTION;
?>
    <div class="wrap meta-override-settings">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <p class="description">
        <?php esc_html_e('Site-wide fallbacks used when a post does not have its own value. Per-post values always win.', 'meta-override'); ?>
      </p>

      <form method="post" action="options.php">
        <?php settings_fields('meta_override_settings_group'); ?>

        <div class="mo-group">
          <h2><?php esc_html_e('Site-wide fallbacks', 'meta-override'); ?></h2>

          <div class="mo-section mo-image-section">
            <label for="mo_settings_og_image"><?php esc_html_e('Default OG Image', 'meta-override'); ?></label>
            <p class="description"><?php esc_html_e('Used as og:image when a post has no featured image and no per-post OG image. The per-post-type fallback below takes precedence over this.', 'meta-override'); ?></p>
            <div class="mo-image-input-wrapper">
              <input
                type="text"
                id="mo_settings_og_image"
                class="mo-input mo-image-url"
                name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_OG_IMAGE_URL . ']'); ?>"
                value="<?php echo esc_attr($site_url); ?>">
              <input
                type="hidden"
                id="mo_settings_og_image_id"
                class="mo-image-id"
                name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_OG_IMAGE_ID . ']'); ?>"
                value="<?php echo esc_attr($site_id); ?>">
              <button type="button" id="mo_settings_og_image_button" class="button mo-image-picker-button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
            </div>
          </div>

          <div class="mo-section">
            <label for="mo_settings_twitter_site"><?php esc_html_e('Twitter / X Site Handle', 'meta-override'); ?></label>
            <p class="description"><?php esc_html_e('Outputs as <meta name="twitter:site"> on every page. Example: @yourbrand', 'meta-override'); ?></p>
            <input
              type="text"
              id="mo_settings_twitter_site"
              class="mo-input"
              name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_TWITTER_SITE . ']'); ?>"
              value="<?php echo esc_attr($twitter_site); ?>"
              placeholder="@yourbrand">
          </div>
        </div>

        <?php if (!empty($post_types)) : ?>
          <div class="mo-group">
            <h2><?php esc_html_e('Per-post-type OG image fallback', 'meta-override'); ?></h2>
            <p class="description"><?php esc_html_e('When set, this overrides the site-wide default for posts of the matching type.', 'meta-override'); ?></p>

            <?php foreach ($post_types as $post_type_slug) :
              $pt_object = get_post_type_object($post_type_slug);
              if (!$pt_object) {
                continue;
              }
              $label = $pt_object->labels->singular_name ? $pt_object->labels->singular_name : $post_type_slug;
              $pt_url = isset($by_type[$post_type_slug]['url']) ? $by_type[$post_type_slug]['url'] : '';
              $pt_id = isset($by_type[$post_type_slug]['id']) ? $by_type[$post_type_slug]['id'] : '';
              $field_id = 'mo_settings_og_image_pt_' . sanitize_key($post_type_slug);
              $field_id_input = $field_id . '_id';
              $field_button = $field_id . '_button';
            ?>
              <div class="mo-section mo-image-section">
                <label for="<?php echo esc_attr($field_id); ?>"><?php echo esc_html($label); ?></label>
                <div class="mo-image-input-wrapper">
                  <input
                    type="text"
                    id="<?php echo esc_attr($field_id); ?>"
                    class="mo-input mo-image-url"
                    name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE . '][' . $post_type_slug . '][url]'); ?>"
                    value="<?php echo esc_attr($pt_url); ?>">
                  <input
                    type="hidden"
                    id="<?php echo esc_attr($field_id_input); ?>"
                    class="mo-image-id"
                    name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE . '][' . $post_type_slug . '][id]'); ?>"
                    value="<?php echo esc_attr($pt_id); ?>">
                  <button type="button" id="<?php echo esc_attr($field_button); ?>" class="button mo-image-picker-button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <?php submit_button(); ?>
      </form>
    </div>
<?php
  }

  /**
   * Sanitize the settings array before it's persisted
   *
   * @param mixed $input Raw posted value
   * @return array
   */
  public function sanitize_settings($input)
  {
    $clean = self::get_defaults();

    if (!is_array($input)) {
      return $clean;
    }

    // Site-wide OG image
    if (isset($input[Meta_Override_Constants::SETTING_OG_IMAGE_URL])) {
      $clean[Meta_Override_Constants::SETTING_OG_IMAGE_URL] = esc_url_raw($input[Meta_Override_Constants::SETTING_OG_IMAGE_URL]);
    }
    if (isset($input[Meta_Override_Constants::SETTING_OG_IMAGE_ID])) {
      $id = Meta_Override_Helper::sanitize_image_id($input[Meta_Override_Constants::SETTING_OG_IMAGE_ID]);
      $clean[Meta_Override_Constants::SETTING_OG_IMAGE_ID] = $id ? $id : '';
    }

    // Twitter site handle — normalize to start with @
    if (isset($input[Meta_Override_Constants::SETTING_TWITTER_SITE])) {
      $handle = sanitize_text_field($input[Meta_Override_Constants::SETTING_TWITTER_SITE]);
      $handle = ltrim(trim($handle), '@');
      $clean[Meta_Override_Constants::SETTING_TWITTER_SITE] = $handle === '' ? '' : '@' . $handle;
    }

    // Per-post-type OG images
    if (isset($input[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE]) && is_array($input[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE])) {
      $supported = array_flip(Meta_Override_Helper::get_supported_post_types());
      $by_type = array();
      foreach ($input[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE] as $slug => $entry) {
        if (!isset($supported[$slug]) || !is_array($entry)) {
          continue;
        }
        $url = isset($entry['url']) ? esc_url_raw($entry['url']) : '';
        $id = isset($entry['id']) ? Meta_Override_Helper::sanitize_image_id($entry['id']) : 0;
        if ($url === '' && !$id) {
          continue;
        }
        $by_type[$slug] = array(
          'url' => $url,
          'id' => $id ? $id : '',
        );
      }
      $clean[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE] = $by_type;
    }

    return $clean;
  }

  /**
   * Default settings shape
   *
   * @return array
   */
  public static function get_defaults()
  {
    return array(
      Meta_Override_Constants::SETTING_OG_IMAGE_URL => '',
      Meta_Override_Constants::SETTING_OG_IMAGE_ID => '',
      Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE => array(),
      Meta_Override_Constants::SETTING_TWITTER_SITE => '',
    );
  }

  /**
   * Per-request cache for the merged settings array
   *
   * @var array|null
   */
  private static $cache = null;

  /**
   * Get the full settings array (merged with defaults)
   *
   * @return array
   */
  public static function get_all()
  {
    if (self::$cache !== null) {
      return self::$cache;
    }

    $stored = get_option(Meta_Override_Constants::SETTINGS_OPTION, array());
    if (!is_array($stored)) {
      $stored = array();
    }
    self::$cache = array_merge(self::get_defaults(), $stored);
    return self::$cache;
  }

  /**
   * Resolve the OG image fallback for a given post type
   *
   * Returns ['url' => string, 'id' => int|string] from the per-post-type
   * setting if set, otherwise the site-wide setting, otherwise empty values.
   *
   * @param string $post_type
   * @return array
   */
  public static function get_og_image_fallback($post_type = '')
  {
    $settings = self::get_all();

    if ($post_type) {
      $by_type = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE];
      if (isset($by_type[$post_type]) && !empty($by_type[$post_type]['url'])) {
        return array(
          'url' => $by_type[$post_type]['url'],
          'id' => isset($by_type[$post_type]['id']) ? $by_type[$post_type]['id'] : '',
        );
      }
    }

    if (!empty($settings[Meta_Override_Constants::SETTING_OG_IMAGE_URL])) {
      return array(
        'url' => $settings[Meta_Override_Constants::SETTING_OG_IMAGE_URL],
        'id' => $settings[Meta_Override_Constants::SETTING_OG_IMAGE_ID],
      );
    }

    return array('url' => '', 'id' => '');
  }

  /**
   * Get the configured twitter:site handle (with leading @), or empty
   *
   * @return string
   */
  public static function get_twitter_site()
  {
    $settings = self::get_all();
    return $settings[Meta_Override_Constants::SETTING_TWITTER_SITE];
  }

}
