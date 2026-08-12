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

      <h4>Where tags are emitted</h4>
      <ul>
        <li><strong>Posts, pages, and custom post types</strong> &mdash; only for the post types ticked under <em>Content types</em> below. All public post types are ticked by default. Unticking a type turns Meta Override off for it completely, front end included &mdash; its single pages and its post type archive.</li>
        <li><strong>The site home and front page</strong> &mdash; always, governed by their own settings. Unticking <em>Page</em> above does not affect them.</li>
        <li><strong>Archives</strong> &mdash; category, tag, custom taxonomy, and post type archives, but only when <em>Output tags on archive pages</em> is switched on. It is off by default.</li>
      </ul>
      <p>Author archives, date archives, search results, 404 pages, and attachment pages are left alone.</p>

      <h4>OG image fallback chain</h4>
      <ol>
        <li>Featured image &mdash; when <em>Use Featured Image</em> is checked on the post</li>
        <li>Per-post or per-term <em>OG Image</em> field</li>
        <li>Per-post-type fallback &mdash; set below for each enabled post type. Applies to single pages, the Posts page, and post type archives; taxonomy term archives skip this step</li>
        <li>Site-wide <em>Default OG Image</em> &mdash; set below</li>
        <li>Otherwise no <code>og:image</code> tag is emitted</li>
      </ol>

      <h4>Titles &amp; descriptions are explicit-only</h4>
      <p>The <code>meta description</code>, <code>og:title</code>, <code>og:description</code>, <code>twitter:title</code>, and <code>twitter:description</code> tags are emitted <strong>only when set</strong>. If a field is blank, the corresponding tag is omitted entirely. WordPress&#8217;s default <code>&lt;title&gt;</code> still applies unless you override it with <em>Meta Title</em>.</p>

      <h4>Twitter cascade</h4>
      <p>When the <em>Same as OG&hellip;</em> checkbox is on, the Twitter tag mirrors the resolved OG tag. If the corresponding OG tag is blank, the Twitter tag is skipped too. The home page and post type archive sections below have no separate Twitter fields &mdash; they always mirror their OG values.</p>

      <h4>og:type</h4>
      <p>Flat post types (such as <em>post</em>) emit <code>og:type=article</code> along with <code>article:published_time</code> and <code>article:modified_time</code>. Hierarchical types (such as <em>page</em>), the home page, the front page, and all archives emit <code>og:type=website</code> with no date tags.</p>
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
        <dt><strong>Content types</strong></dt>
        <dd>Which post types get the Meta Override box in their editor, and which ones emit tags on the front end. All public post types start ticked; the selection is stored on first save, so a post type registered later starts unticked. Unticking a type leaves any values already saved against it in the database untouched &mdash; they simply stop being used. Tick it again and they come back. Non-public post types (and the <code>post_format</code> taxonomy) are not listed here and can only be added with the developer filters.</dd>

        <dt><strong>Taxonomies</strong></dt>
        <dd>Which taxonomies get Meta Override fields on their term edit screens. Term values are only emitted while <em>Output tags on archive pages</em> is on.</dd>

        <dt><strong>Output tags on archive pages</strong></dt>
        <dd>Off by default. When on, category, tag, custom taxonomy, and post type archives emit the OG scaffolding and site-wide fallbacks, plus any term or archive overrides you have set.</dd>

        <dt><strong>Home page</strong></dt>
        <dd>Only shown when the home page has no page behind it &mdash; Settings &rarr; Reading set to <em>Your latest posts</em>, or a static-page mode with no pages actually assigned. In those modes there is nowhere else to put these values. If you use a static front page, edit that page directly instead; note that its values keep being emitted even if <em>Page</em> is unticked above, since the home is never governed by the content type list.</dd>

        <dt><strong>Default OG Image</strong></dt>
        <dd>Used as <code>og:image</code> when nothing more specific is set.</dd>

        <dt><strong>Twitter / X Site Handle</strong></dt>
        <dd>Emitted as <code>&lt;meta name="twitter:site"&gt;</code> on every page where Meta Override runs. Leading <code>@</code> is normalized automatically.</dd>

        <dt><strong>Per-post-type OG image fallback</strong></dt>
        <dd>When set, takes precedence over the site-wide default for posts of that type. Per-post values still win over both.</dd>

        <dt><strong>Post type archive overrides</strong></dt>
        <dd>For enabled post types registered with an archive. These pages have no post behind them, so their values live here. Unticking the post type silences its archive and hides this row; the saved values are kept and come back when the type is re-ticked.</dd>
      </dl>

      <p>Ticking a new content type and setting its OG image fallback or archive overrides takes two saves: those rows only appear once the type is enabled.</p>
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
      <p>Three filters are available for extending Meta Override:</p>

      <h4><code>meta_override_supported_post_types</code></h4>
      <p>Runs <strong>after</strong> the <em>Content types</em> setting, so it has the final say. A type added here is enabled even if its box is unticked, and shows as locked on the settings screen. Because the choice lives in code it is never written into the saved option &mdash; remove the filter and the screen reverts to whatever is actually ticked, so tick the box too if you want the choice to outlive the code.</p>
      <pre><code>add_filter( \'meta_override_supported_post_types\', function( $types ) {
    $types[] = \'product\';
    return $types;
} );</code></pre>

      <h4><code>meta_override_supported_taxonomies</code></h4>
      <p>The same arrangement for taxonomies.</p>
      <pre><code>add_filter( \'meta_override_supported_taxonomies\', function( $taxonomies ) {
    $taxonomies[] = \'product_cat\';
    return $taxonomies;
} );</code></pre>

      <h4><code>meta_override_schema_org</code></h4>
      <p>Modify the Schema.org array before it is rendered as JSON-LD. Receives <code>$schema</code>, <code>$post_id</code> (0 where the context has no post), and <code>$context</code> &mdash; the resolved page context, whose <code>context</code> key is one of <code>singular</code>, <code>front</code>, <code>home</code>, <code>term</code>, or <code>post_type_archive</code>.</p>
      <pre><code>add_filter( \'meta_override_schema_org\', function( $schema, $post_id, $context ) {
    if ( \'term\' === $context[\'context\'] ) {
        $schema[\'@type\'] = \'CollectionPage\';
    }
    return $schema;
}, 10, 3 );</code></pre>
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
    $option = Meta_Override_Constants::SETTINGS_OPTION;

    $stored_post_types = self::get_post_types();
    $effective_post_types = Meta_Override_Helper::get_supported_post_types();
    $candidate_post_types = Meta_Override_Helper::get_candidate_post_types();

    $stored_taxonomies = self::get_taxonomies();
    $effective_taxonomies = Meta_Override_Helper::get_supported_taxonomies();
    $candidate_taxonomies = Meta_Override_Helper::get_candidate_taxonomies();

    $archives_on = self::archives_enabled();

    // The option-backed Home fields are live whenever the resolver has no
    // posts page to read instead: "latest posts" mode, or "static page" mode
    // with neither page actually assigned (WordPress then serves the posts
    // index at the root). Mirroring that here keeps the live values editable.
    $latest_posts_home = (get_option('show_on_front') !== 'page')
      || (!(int) get_option('page_on_front') && !(int) get_option('page_for_posts'));

    $site_url = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_URL];
    $site_id = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_ID];
    $twitter_site = $settings[Meta_Override_Constants::SETTING_TWITTER_SITE];
    $by_type = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE];
?>
    <div class="wrap meta-override-settings">
      <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
      <p class="description">
        <?php esc_html_e('Choose which content Meta Override applies to, and set the site-wide fallbacks used when something has no value of its own.', 'meta-override'); ?>
      </p>

      <form method="post" action="options.php">
        <?php settings_fields('meta_override_settings_group'); ?>

        <div class="mo-group">
          <h2><?php esc_html_e('Content types', 'meta-override'); ?></h2>
          <p class="description"><?php esc_html_e('Meta Override adds its editor box to these post types and outputs tags for them. Unticking a type stops output for it but never deletes values you have already saved.', 'meta-override'); ?></p>

          <?php // Sentinel: guarantees the key is posted even when nothing is ticked. ?>
          <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_POST_TYPES . '][]'); ?>" value="">

          <?php
          // Preserve slugs whose post type isn't registered right now (e.g. a
          // CPT plugin is temporarily deactivated) so config isn't destroyed.
          foreach (array_diff($stored_post_types, array_keys($candidate_post_types)) as $orphan) : ?>
            <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_POST_TYPES . '][]'); ?>" value="<?php echo esc_attr($orphan); ?>">
          <?php endforeach; ?>

          <div class="mo-checkbox-grid">
            <?php foreach ($candidate_post_types as $slug => $pt_object) :
              $is_effective = in_array($slug, $effective_post_types, true);
              $is_stored = in_array($slug, $stored_post_types, true);
              $is_locked = ($is_effective !== $is_stored);
              $label = $pt_object->labels->name ? $pt_object->labels->name : $slug;
            ?>
              <label class="mo-checkbox">
                <input
                  type="checkbox"
                  name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_POST_TYPES . '][]'); ?>"
                  value="<?php echo esc_attr($slug); ?>"
                  <?php checked($is_effective); ?>
                  <?php disabled($is_locked); ?>>
                <span><?php echo esc_html($label); ?> <code><?php echo esc_html($slug); ?></code></span>
                <?php if ($is_locked) : ?>
                  <span class="mo-locked" title="<?php esc_attr_e('A filter in your theme or a plugin controls this type.', 'meta-override'); ?>"><?php esc_html_e('set in code', 'meta-override'); ?></span>
                  <?php if ($is_stored) : ?>
                    <?php // Keep the stored choice intact — disabled inputs aren't posted. ?>
                    <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_POST_TYPES . '][]'); ?>" value="<?php echo esc_attr($slug); ?>">
                  <?php endif; ?>
                <?php endif; ?>
              </label>
            <?php endforeach; ?>
          </div>

          <?php if (empty($effective_post_types)) : ?>
            <p class="mo-notice"><?php esc_html_e('No content types are selected, so Meta Override will not output tags on any single post or page.', 'meta-override'); ?></p>
          <?php endif; ?>
        </div>

        <div class="mo-group">
          <h2><?php esc_html_e('Archives', 'meta-override'); ?></h2>

          <div class="mo-section">
            <?php // Sentinel: an unticked checkbox posts nothing, so this makes "off" explicit. ?>
            <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_ARCHIVES_ENABLED . ']'); ?>" value="">
            <label class="mo-checkbox">
              <input
                type="checkbox"
                name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_ARCHIVES_ENABLED . ']'); ?>"
                value="on"
                <?php checked($archives_on); ?>>
              <span><?php esc_html_e('Output tags on archive pages', 'meta-override'); ?></span>
            </label>
            <p class="description"><?php esc_html_e('Covers category, tag, custom taxonomy, and post type archives. Off by default. Author archives, date archives, search results, and 404 pages are never touched.', 'meta-override'); ?></p>
          </div>

          <?php if (!empty($candidate_taxonomies)) : ?>
            <div class="mo-section">
              <label><?php esc_html_e('Taxonomies with editable term fields', 'meta-override'); ?></label>
              <p class="description"><?php esc_html_e('Adds Meta Override fields to these term edit screens.', 'meta-override'); ?></p>

              <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_TAXONOMIES . '][]'); ?>" value="">

              <?php foreach (array_diff($stored_taxonomies, array_keys($candidate_taxonomies)) as $orphan) : ?>
                <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_TAXONOMIES . '][]'); ?>" value="<?php echo esc_attr($orphan); ?>">
              <?php endforeach; ?>

              <div class="mo-checkbox-grid">
                <?php foreach ($candidate_taxonomies as $slug => $tax_object) :
                  $is_effective = in_array($slug, $effective_taxonomies, true);
                  $is_stored = in_array($slug, $stored_taxonomies, true);
                  $is_locked = ($is_effective !== $is_stored);
                  $label = $tax_object->labels->name ? $tax_object->labels->name : $slug;
                ?>
                  <label class="mo-checkbox">
                    <input
                      type="checkbox"
                      name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_TAXONOMIES . '][]'); ?>"
                      value="<?php echo esc_attr($slug); ?>"
                      <?php checked($is_effective); ?>
                      <?php disabled($is_locked); ?>>
                    <span><?php echo esc_html($label); ?> <code><?php echo esc_html($slug); ?></code></span>
                    <?php if ($is_locked) : ?>
                      <span class="mo-locked"><?php esc_html_e('set in code', 'meta-override'); ?></span>
                      <?php if ($is_stored) : ?>
                        <input type="hidden" name="<?php echo esc_attr($option . '[' . Meta_Override_Constants::SETTING_TAXONOMIES . '][]'); ?>" value="<?php echo esc_attr($slug); ?>">
                      <?php endif; ?>
                    <?php endif; ?>
                  </label>
                <?php endforeach; ?>
              </div>

              <?php if (!empty($effective_taxonomies) && !$archives_on) : ?>
                <p class="mo-notice"><?php esc_html_e('Archive output is off, so term values are saved but not emitted. Tick the box above to use them.', 'meta-override'); ?></p>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>

        <?php if ($latest_posts_home) : ?>
          <div class="mo-group">
            <h2><?php esc_html_e('Home page', 'meta-override'); ?></h2>
            <p class="description"><?php esc_html_e('Your site shows the latest posts on its home page, so there is no page to attach these values to. Set them here.', 'meta-override'); ?></p>
            <?php $this->render_context_fields(
              $option . '[' . Meta_Override_Constants::SETTING_HOME . ']',
              'mo_home',
              self::get_home_meta()
            ); ?>
          </div>
        <?php else : ?>
          <div class="mo-group">
            <h2><?php esc_html_e('Home page', 'meta-override'); ?></h2>
            <p class="description">
              <?php
              printf(
                /* translators: %s: link to the Reading settings screen */
                esc_html__('Your front page is set to a static page, so its meta is edited on that page directly. These settings apply only when %s is set to show your latest posts.', 'meta-override'),
                '<a href="' . esc_url(admin_url('options-reading.php')) . '">' . esc_html__('Settings → Reading', 'meta-override') . '</a>'
              );
              ?>
            </p>
          </div>
        <?php endif; ?>

        <div class="mo-group">
          <h2><?php esc_html_e('Site-wide fallbacks', 'meta-override'); ?></h2>

          <div class="mo-section mo-image-section">
            <label for="mo_settings_og_image"><?php esc_html_e('Default OG Image', 'meta-override'); ?></label>
            <p class="description"><?php esc_html_e('Used as og:image when nothing more specific is set. The per-post-type fallback below takes precedence over this.', 'meta-override'); ?></p>
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

        <?php if (!empty($effective_post_types)) : ?>
          <div class="mo-group">
            <h2><?php esc_html_e('Per-post-type OG image fallback', 'meta-override'); ?></h2>
            <p class="description"><?php esc_html_e('When set, this overrides the site-wide default for posts of the matching type.', 'meta-override'); ?></p>

            <?php foreach ($effective_post_types as $post_type_slug) :
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

        <?php
        $archive_types = array();
        foreach ($effective_post_types as $post_type_slug) {
          $pt_object = get_post_type_object($post_type_slug);
          if ($pt_object && $pt_object->has_archive) {
            $archive_types[$post_type_slug] = $pt_object;
          }
        }
        ?>
        <?php if (!empty($archive_types)) : ?>
          <div class="mo-group">
            <h2><?php esc_html_e('Post type archive overrides', 'meta-override'); ?></h2>
            <p class="description"><?php esc_html_e('These archive pages have no post behind them, so their values are set here. Only used while archive output is on.', 'meta-override'); ?></p>

            <?php foreach ($archive_types as $post_type_slug => $pt_object) :
              $label = $pt_object->labels->name ? $pt_object->labels->name : $post_type_slug;
            ?>
              <div class="mo-subgroup">
                <h3><?php echo esc_html($label); ?></h3>
                <?php $this->render_context_fields(
                  $option . '[' . Meta_Override_Constants::SETTING_ARCHIVE_META . '][' . $post_type_slug . ']',
                  'mo_archive_' . sanitize_key($post_type_slug),
                  self::get_archive_meta($post_type_slug)
                ); ?>
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
   * Render the shared field set used by option-backed contexts
   *
   * The image input IDs follow the {id}/{id}_id/{id}_button convention the
   * media picker script already expects, so no JS changes are needed.
   *
   * @param string $name_base Form name prefix, e.g. "opt[home]"
   * @param string $id_prefix DOM id prefix, e.g. "mo_home"
   * @param array  $values    Current values keyed by field name
   * @return void
   * @since 2.0.0
   */
  private function render_context_fields($name_base, $id_prefix, $values)
  {
    $get = function ($field) use ($values) {
      return isset($values[$field]) ? $values[$field] : '';
    };

    $title_id = $id_prefix . '_meta_title';
    $desc_id = $id_prefix . '_meta_description';
    $og_title_id = $id_prefix . '_og_title';
    $og_desc_id = $id_prefix . '_og_description';
    $image_id = $id_prefix . '_og_image';
?>
    <div class="mo-section">
      <label for="<?php echo esc_attr($title_id); ?>"><?php esc_html_e('Meta Title', 'meta-override'); ?></label>
      <input type="text" id="<?php echo esc_attr($title_id); ?>" class="mo-input"
        name="<?php echo esc_attr($name_base . '[' . Meta_Override_Constants::FIELD_META_TITLE . ']'); ?>"
        value="<?php echo esc_attr($get(Meta_Override_Constants::FIELD_META_TITLE)); ?>">
    </div>

    <div class="mo-section">
      <label for="<?php echo esc_attr($desc_id); ?>"><?php esc_html_e('Meta Description', 'meta-override'); ?></label>
      <textarea id="<?php echo esc_attr($desc_id); ?>" rows="3" class="mo-input"
        name="<?php echo esc_attr($name_base . '[' . Meta_Override_Constants::FIELD_META_DESCRIPTION . ']'); ?>"><?php echo esc_textarea($get(Meta_Override_Constants::FIELD_META_DESCRIPTION)); ?></textarea>
    </div>

    <div class="mo-section">
      <label for="<?php echo esc_attr($og_title_id); ?>"><?php esc_html_e('OG Title', 'meta-override'); ?></label>
      <input type="text" id="<?php echo esc_attr($og_title_id); ?>" class="mo-input"
        name="<?php echo esc_attr($name_base . '[' . Meta_Override_Constants::FIELD_OG_TITLE . ']'); ?>"
        value="<?php echo esc_attr($get(Meta_Override_Constants::FIELD_OG_TITLE)); ?>">
    </div>

    <div class="mo-section">
      <label for="<?php echo esc_attr($og_desc_id); ?>"><?php esc_html_e('OG Description', 'meta-override'); ?></label>
      <textarea id="<?php echo esc_attr($og_desc_id); ?>" rows="3" class="mo-input"
        name="<?php echo esc_attr($name_base . '[' . Meta_Override_Constants::FIELD_OG_DESCRIPTION . ']'); ?>"><?php echo esc_textarea($get(Meta_Override_Constants::FIELD_OG_DESCRIPTION)); ?></textarea>
    </div>

    <div class="mo-section mo-image-section">
      <label for="<?php echo esc_attr($image_id); ?>"><?php esc_html_e('OG Image', 'meta-override'); ?></label>
      <div class="mo-image-input-wrapper">
        <input type="text" id="<?php echo esc_attr($image_id); ?>" class="mo-input mo-image-url"
          name="<?php echo esc_attr($name_base . '[' . Meta_Override_Constants::FIELD_OG_IMAGE . ']'); ?>"
          value="<?php echo esc_attr($get(Meta_Override_Constants::FIELD_OG_IMAGE)); ?>">
        <input type="hidden" id="<?php echo esc_attr($image_id . '_id'); ?>" class="mo-image-id"
          name="<?php echo esc_attr($name_base . '[' . Meta_Override_Constants::FIELD_OG_IMAGE_ID . ']'); ?>"
          value="<?php echo esc_attr($get(Meta_Override_Constants::FIELD_OG_IMAGE_ID)); ?>">
        <button type="button" id="<?php echo esc_attr($image_id . '_button'); ?>" class="button mo-image-picker-button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
      </div>
      <p class="description"><?php esc_html_e('Twitter title, description, and image mirror the OG values above.', 'meta-override'); ?></p>
    </div>
<?php
  }

  /**
   * Sanitize the settings array before it's persisted
   *
   * Order matters: the content type list is resolved first, because the
   * per-post-type sections below are validated against it.
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

    // The stored value as it is right now — sanitize_callback runs before the
    // option is written, so this is the pre-save state.
    $previous = get_option(Meta_Override_Constants::SETTINGS_OPTION, array());
    if (!is_array($previous)) {
      $previous = array();
    }

    // Every top-level key is resolved the same way:
    //
    //   present in $input  → that value wins, even when empty (this is how a
    //                        value gets cleared)
    //   absent from $input → the stored value carries forward
    //   neither            → the default
    //
    // The settings form posts a hidden sentinel alongside every checkbox and
    // checkbox list, so "unticked" always arrives as an explicit empty value
    // rather than an absent key. An absent key therefore means some other
    // caller ran update_option() without that section, and clobbering it would
    // be destructive — a partial programmatic update behaves as a patch.
    $source = array();
    foreach (array_keys($clean) as $key) {
      if (array_key_exists($key, $input)) {
        $source[$key] = $input[$key];
      } elseif (array_key_exists($key, $previous)) {
        $source[$key] = $previous[$key];
      } else {
        $source[$key] = $clean[$key];
      }
    }

    // --- Content types -----------------------------------------------------
    $clean[Meta_Override_Constants::SETTING_POST_TYPES] =
      self::sanitize_slug_list($source[Meta_Override_Constants::SETTING_POST_TYPES]);

    // --- Taxonomies --------------------------------------------------------
    $clean[Meta_Override_Constants::SETTING_TAXONOMIES] =
      self::sanitize_slug_list($source[Meta_Override_Constants::SETTING_TAXONOMIES]);

    // --- Archive master switch ---------------------------------------------
    $clean[Meta_Override_Constants::SETTING_ARCHIVES_ENABLED] =
      ($source[Meta_Override_Constants::SETTING_ARCHIVES_ENABLED] === 'on') ? 'on' : '';

    // --- Site-wide OG image -------------------------------------------------
    // is_scalar: esc_url_raw() fatals on an array, and a crafted POST can
    // send one for any field ("...[og_image_url][]=x").
    $clean[Meta_Override_Constants::SETTING_OG_IMAGE_URL] =
      is_scalar($source[Meta_Override_Constants::SETTING_OG_IMAGE_URL])
        ? esc_url_raw((string) $source[Meta_Override_Constants::SETTING_OG_IMAGE_URL])
        : '';

    $id = Meta_Override_Helper::sanitize_image_id($source[Meta_Override_Constants::SETTING_OG_IMAGE_ID]);
    $clean[Meta_Override_Constants::SETTING_OG_IMAGE_ID] = $id ? $id : '';

    // --- Twitter site handle — normalize to start with @ --------------------
    $handle = is_scalar($source[Meta_Override_Constants::SETTING_TWITTER_SITE])
      ? sanitize_text_field((string) $source[Meta_Override_Constants::SETTING_TWITTER_SITE])
      : '';
    $handle = ltrim(trim($handle), '@');
    $clean[Meta_Override_Constants::SETTING_TWITTER_SITE] = $handle === '' ? '' : '@' . $handle;

    // --- Home page ----------------------------------------------------------
    // The section isn't rendered when a static front page is configured, so the
    // carry-forward above is what keeps those values across a Reading mode switch.
    $clean[Meta_Override_Constants::SETTING_HOME] = is_array($source[Meta_Override_Constants::SETTING_HOME])
      ? self::sanitize_context_fields($source[Meta_Override_Constants::SETTING_HOME])
      : array();

    // --- Per-post-type OG images -------------------------------------------
    // These two maps merge at the slug level rather than the key level: the form
    // only renders rows for enabled post types, so entries for the others have to
    // survive a save that legitimately posts the key without them.
    $by_type = array();
    if (isset($previous[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE]) && is_array($previous[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE])) {
      foreach ($previous[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE] as $slug => $entry) {
        $slug = sanitize_key($slug);
        if ($slug !== '' && is_array($entry)) {
          $by_type[$slug] = array(
            'url' => isset($entry['url']) && is_scalar($entry['url']) ? esc_url_raw((string) $entry['url']) : '',
            'id' => isset($entry['id']) ? Meta_Override_Helper::sanitize_image_id($entry['id']) : 0,
          );
        }
      }
    }
    if (isset($input[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE]) && is_array($input[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE])) {
      foreach ($input[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE] as $slug => $entry) {
        $slug = sanitize_key($slug);
        if ($slug === '' || !is_array($entry)) {
          continue;
        }
        $by_type[$slug] = array(
          'url' => isset($entry['url']) && is_scalar($entry['url']) ? esc_url_raw((string) $entry['url']) : '',
          'id' => isset($entry['id']) ? Meta_Override_Helper::sanitize_image_id($entry['id']) : 0,
        );
      }
    }
    foreach ($by_type as $slug => $entry) {
      if ($entry['url'] === '' && !$entry['id']) {
        unset($by_type[$slug]);
        continue;
      }
      $by_type[$slug]['id'] = $entry['id'] ? $entry['id'] : '';
    }
    $clean[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE] = $by_type;

    // --- Post type archive overrides ---------------------------------------
    $archive_meta = array();
    if (isset($previous[Meta_Override_Constants::SETTING_ARCHIVE_META]) && is_array($previous[Meta_Override_Constants::SETTING_ARCHIVE_META])) {
      foreach ($previous[Meta_Override_Constants::SETTING_ARCHIVE_META] as $slug => $entry) {
        $slug = sanitize_key($slug);
        if ($slug !== '' && is_array($entry)) {
          $archive_meta[$slug] = self::sanitize_context_fields($entry);
        }
      }
    }
    if (isset($input[Meta_Override_Constants::SETTING_ARCHIVE_META]) && is_array($input[Meta_Override_Constants::SETTING_ARCHIVE_META])) {
      foreach ($input[Meta_Override_Constants::SETTING_ARCHIVE_META] as $slug => $entry) {
        $slug = sanitize_key($slug);
        if ($slug === '' || !is_array($entry)) {
          continue;
        }
        $archive_meta[$slug] = self::sanitize_context_fields($entry);
      }
    }
    foreach ($archive_meta as $slug => $entry) {
      if (!array_filter($entry, function ($v) {
        return $v !== '' && $v !== 0;
      })) {
        unset($archive_meta[$slug]);
      }
    }
    $clean[Meta_Override_Constants::SETTING_ARCHIVE_META] = $archive_meta;

    // The memoized copy is now stale.
    self::flush_cache();

    return $clean;
  }

  /**
   * Sanitize a posted list of slugs
   *
   * Unregistered slugs are kept — a temporarily deactivated CPT plugin must
   * not silently destroy the site's configuration.
   *
   * @param mixed $list
   * @return array
   * @since 2.0.0
   */
  private static function sanitize_slug_list($list)
  {
    if (!is_array($list)) {
      return array();
    }

    $clean = array();
    foreach ($list as $slug) {
      if (!is_scalar($slug)) {
        continue;
      }
      // Not sanitize_key(): register_taxonomy() stores its name verbatim
      // (uppercase and punctuation included), so lowercasing here would store
      // a slug that never matches the registered name again — the checkbox
      // could never be made to stick. Registered names are compared exactly;
      // this only rejects hostile or accidental garbage.
      $slug = sanitize_text_field((string) $slug);
      if ($slug !== '' && strlen($slug) <= 64) {
        $clean[] = $slug;
      }
    }

    return array_values(array_unique($clean));
  }

  /**
   * Sanitize an option-backed context field set
   *
   * @param array $entry
   * @return array
   * @since 2.0.0
   */
  private static function sanitize_context_fields($entry)
  {
    $clean = array();

    foreach (Meta_Override_Constants::get_context_fields() as $field) {
      if (!isset($entry[$field])) {
        $clean[$field] = '';
        continue;
      }

      if ($field === Meta_Override_Constants::FIELD_OG_IMAGE_ID) {
        $id = Meta_Override_Helper::sanitize_image_id($entry[$field]);
        $clean[$field] = $id ? $id : '';
        continue;
      }

      $clean[$field] = Meta_Override_Helper::sanitize_meta_value($field, $entry[$field]);
    }

    return $clean;
  }

  /**
   * Default settings shape
   *
   * The content type default is every public post type, because that is what
   * 1.3.0 actually emitted for on the front end — its hardcoded post/page
   * pair only gated the admin meta box, not output. Defaulting narrower
   * would silence CPT singles on upgrade. The list freezes into the option
   * on first save; a type registered after that starts unticked.
   *
   * @return array
   */
  public static function get_defaults()
  {
    return array(
      Meta_Override_Constants::SETTING_POST_TYPES => array_keys(Meta_Override_Helper::get_candidate_post_types()),
      Meta_Override_Constants::SETTING_TAXONOMIES => array(),
      Meta_Override_Constants::SETTING_ARCHIVES_ENABLED => '',
      Meta_Override_Constants::SETTING_HOME => array(),
      Meta_Override_Constants::SETTING_ARCHIVE_META => array(),
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
   * Discard the memoized settings array
   *
   * @return void
   * @since 2.0.0
   */
  public static function flush_cache()
  {
    self::$cache = null;
  }

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
   * Get the stored content type list, before the filter runs
   *
   * @return array
   * @since 2.0.0
   */
  public static function get_post_types()
  {
    $settings = self::get_all();
    $types = $settings[Meta_Override_Constants::SETTING_POST_TYPES];
    return is_array($types) ? $types : array();
  }

  /**
   * Get the stored taxonomy list, before the filter runs
   *
   * @return array
   * @since 2.0.0
   */
  public static function get_taxonomies()
  {
    $settings = self::get_all();
    $taxonomies = $settings[Meta_Override_Constants::SETTING_TAXONOMIES];
    return is_array($taxonomies) ? $taxonomies : array();
  }

  /**
   * Whether archive output is switched on
   *
   * @return bool
   * @since 2.0.0
   */
  public static function archives_enabled()
  {
    $settings = self::get_all();
    return $settings[Meta_Override_Constants::SETTING_ARCHIVES_ENABLED] === 'on';
  }

  /**
   * Get the option-backed home page field values
   *
   * @return array
   * @since 2.0.0
   */
  public static function get_home_meta()
  {
    $settings = self::get_all();
    $home = $settings[Meta_Override_Constants::SETTING_HOME];
    return is_array($home) ? $home : array();
  }

  /**
   * Get the option-backed field values for a post type archive
   *
   * @param string $post_type
   * @return array
   * @since 2.0.0
   */
  public static function get_archive_meta($post_type)
  {
    $settings = self::get_all();
    $all = $settings[Meta_Override_Constants::SETTING_ARCHIVE_META];

    if (is_array($all) && isset($all[$post_type]) && is_array($all[$post_type])) {
      return $all[$post_type];
    }

    return array();
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

    // Type guards throughout: the sanitize callback only exists in admin
    // requests, so the stored option can legitimately arrive malformed
    // (WP-CLI, cron, or any front-end update_option()), and these values
    // flow straight into esc_url()/esc_attr().
    if ($post_type) {
      $by_type = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_BY_POST_TYPE];
      if (is_array($by_type) && isset($by_type[$post_type]) && is_array($by_type[$post_type])
        && !empty($by_type[$post_type]['url']) && is_string($by_type[$post_type]['url'])
      ) {
        $entry_id = isset($by_type[$post_type]['id']) ? $by_type[$post_type]['id'] : '';
        return array(
          'url' => $by_type[$post_type]['url'],
          'id' => is_scalar($entry_id) ? $entry_id : '',
        );
      }
    }

    $site_url = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_URL];
    if (!empty($site_url) && is_string($site_url)) {
      $site_id = $settings[Meta_Override_Constants::SETTING_OG_IMAGE_ID];
      return array(
        'url' => $site_url,
        'id' => is_scalar($site_id) ? $site_id : '',
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
    $handle = $settings[Meta_Override_Constants::SETTING_TWITTER_SITE];
    return is_string($handle) ? $handle : '';
  }
}
