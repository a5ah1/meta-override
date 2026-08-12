<?php

/**
 * Admin functionality for Meta Override plugin
 *
 * Handles admin-side operations including meta boxes, term fields,
 * saving data, and enqueuing admin assets.
 *
 * @package Meta_Override
 * @since   1.1.0
 */
class Meta_Override_Admin
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
   * Initialize the admin class
   *
   * @param string $plugin_name The plugin name
   * @param string $version     The plugin version
   * @since 1.1.0
   */
  public function __construct($plugin_name, $version)
  {
    $this->plugin_name = $plugin_name;
    $this->version = $version;
  }

  /**
   * Whether the current screen is one Meta Override renders fields on
   *
   * Term screens used to pick the assets up by accident, because
   * $screen->post_type is populated on edit-tags.php. This makes it deliberate.
   *
   * @param WP_Screen|null $screen
   * @return bool
   * @since 2.0.0
   */
  private function is_plugin_screen($screen)
  {
    if (!$screen) {
      return false;
    }

    if (in_array($screen->base, array('edit-tags', 'term'), true)) {
      return !empty($screen->taxonomy)
        && in_array($screen->taxonomy, Meta_Override_Helper::get_supported_taxonomies(), true);
    }

    if ($screen->base === 'post') {
      return !empty($screen->post_type)
        && in_array($screen->post_type, $this->get_supported_post_types(), true);
    }

    return false;
  }

  /**
   * Enqueue admin styles
   *
   * @since 1.1.0
   * @return void
   */
  public function enqueue_styles()
  {
    if (!$this->is_plugin_screen(get_current_screen())) {
      return;
    }

    wp_enqueue_style(
      $this->plugin_name,
      META_OVERRIDE_PLUGIN_URL . 'assets/css/admin-styles.css',
      array(),
      $this->version,
      'all'
    );
  }

  /**
   * Enqueue admin scripts
   *
   * @since 1.1.0
   * @return void
   */
  public function enqueue_scripts()
  {
    if (!$this->is_plugin_screen(get_current_screen())) {
      return;
    }

    wp_enqueue_media();
    wp_enqueue_script(
      $this->plugin_name,
      META_OVERRIDE_PLUGIN_URL . 'assets/js/admin-script.js',
      array('jquery'),
      $this->version,
      false
    );

    $post_id = get_the_ID();
    $featured_image_url = '';
    if ($post_id && has_post_thumbnail($post_id)) {
      $featured_image_url = get_the_post_thumbnail_url($post_id, 'full');
    }
    wp_localize_script($this->plugin_name, 'metaOverrideData', array(
      'featuredImageUrl' => $featured_image_url ? $featured_image_url : '',
    ));
  }

  /**
   * Get supported post types (filterable)
   *
   * @since 1.1.0
   * @return array Array of post type slugs
   */
  private function get_supported_post_types()
  {
    return Meta_Override_Helper::get_supported_post_types();
  }

  /**
   * Add meta boxes to supported post types
   *
   * @since 1.1.0
   * @return void
   */
  public function add_meta_boxes()
  {
    $post_types = $this->get_supported_post_types();

    foreach ($post_types as $post_type) {
      add_meta_box(
        'meta_override_meta_box',
        __('Meta Override', 'meta-override'),
        array($this, 'render_meta_box'),
        $post_type,
        'normal',
        'low'
      );
    }
  }

  /**
   * Register the term form fields and save handlers for supported taxonomies
   *
   * Runs on admin_init, which is after init, so custom taxonomies are
   * registered by the time this reads the list.
   *
   * @since 2.0.0
   * @return void
   */
  public function register_term_fields()
  {
    foreach (Meta_Override_Helper::get_supported_taxonomies() as $taxonomy) {
      if (!taxonomy_exists($taxonomy)) {
        continue;
      }

      add_action($taxonomy . '_add_form_fields', array($this, 'render_term_add_fields'));
      add_action($taxonomy . '_edit_form_fields', array($this, 'render_term_edit_fields'));
      add_action('created_' . $taxonomy, array($this, 'save_term_meta'));
      add_action('edited_' . $taxonomy, array($this, 'save_term_meta'));
    }
  }

  /**
   * Render the meta box content
   *
   * @param WP_Post $post The post object
   * @since 1.1.0
   * @return void
   */
  public function render_meta_box($post)
  {
    wp_nonce_field('meta_override_meta_box', 'meta_override_meta_box_nonce');

    $meta_data = Meta_Override_Helper::get_all_meta_data($post->ID);

?>
    <div class="meta-override-metabox">
      <div class="mo-group">
        <h4>Meta Tags</h4>
        <div class="mo-section">
          <label for="meta_title">Meta Title</label>
          <input type="text" id="meta_title" name="<?php echo Meta_Override_Constants::FIELD_META_TITLE; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_META_TITLE]); ?>" class="mo-input">
          <span class="mo-char-count"></span>
        </div>
        <div class="mo-section">
          <label for="meta_description">Meta Description</label>
          <textarea id="meta_description" name="<?php echo Meta_Override_Constants::FIELD_META_DESCRIPTION; ?>" rows="3" class="mo-input"><?php echo esc_textarea($meta_data[Meta_Override_Constants::FIELD_META_DESCRIPTION]); ?></textarea>
          <span class="mo-char-count"></span>
        </div>
      </div>

      <div class="mo-groups-wrapper">
        <div class="mo-group">
          <h4>Open Graph / Facebook</h4>
          <div class="mo-section">
            <label for="og_title">OG Title</label>
            <input type="text" id="og_title" name="<?php echo Meta_Override_Constants::FIELD_OG_TITLE; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_OG_TITLE]); ?>" class="mo-input">
          </div>
          <div class="mo-section">
            <label for="og_description">OG Description</label>
            <textarea id="og_description" name="<?php echo Meta_Override_Constants::FIELD_OG_DESCRIPTION; ?>" rows="3" class="mo-input"><?php echo esc_textarea($meta_data[Meta_Override_Constants::FIELD_OG_DESCRIPTION]); ?></textarea>
          </div>
          <div class="mo-section mo-image-section">
            <label for="og_image">OG Image</label>
            <div class="mo-image-input-wrapper">
              <input type="text" id="og_image" name="<?php echo Meta_Override_Constants::FIELD_OG_IMAGE; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE]); ?>" class="mo-input">
              <input type="hidden" id="og_image_id" name="<?php echo Meta_Override_Constants::FIELD_OG_IMAGE_ID; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_ID]); ?>">
              <button type="button" class="button mo-image-picker-button" id="og_image_button">Choose Image</button>
            </div>
            <label class="mo-checkbox">
              <input type="checkbox" id="og_image_use_featured" name="<?php echo Meta_Override_Constants::FIELD_OG_IMAGE_USE_FEATURED; ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_USE_FEATURED], 'on'); ?>>
              Use Featured Image
            </label>
            <?php if (!has_post_thumbnail($post->ID)) : ?>
              <span class="mo-notice">No featured image is set for this post.</span>
            <?php endif; ?>
          </div>
        </div>

        <div class="mo-group">
          <h4>X / Twitter</h4>
          <div class="mo-section">
            <label for="twitter_title">Twitter Title</label>
            <input type="text" id="twitter_title" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_TITLE; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE]); ?>" class="mo-input">
            <label class="mo-checkbox">
              <input type="checkbox" id="twitter_title_same_as_og" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC; ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC], 'on'); ?>>
              Same as OG Title
            </label>
          </div>
          <div class="mo-section">
            <label for="twitter_description">Twitter Description</label>
            <textarea id="twitter_description" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION; ?>" rows="3" class="mo-input"><?php echo esc_textarea($meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION]); ?></textarea>
            <label class="mo-checkbox">
              <input type="checkbox" id="twitter_description_same_as_og" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC; ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC], 'on'); ?>>
              Same as OG Description
            </label>
          </div>
          <div class="mo-section mo-image-section">
            <label for="twitter_image">Twitter Image</label>
            <div class="mo-image-input-wrapper">
              <input type="text" id="twitter_image" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_IMAGE; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE]); ?>" class="mo-input">
              <input type="hidden" id="twitter_image_id" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_IMAGE_ID; ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE_ID]); ?>">
              <button type="button" class="button mo-image-picker-button" id="twitter_image_button">Choose Image</button>
            </div>
            <label class="mo-checkbox">
              <input type="checkbox" id="twitter_image_same_as_og" name="<?php echo Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC; ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC], 'on'); ?>>
              Same as OG Image
            </label>
          </div>
        </div>
      </div>
    </div>
<?php
  }

  /**
   * Render Meta Override fields on the "add new term" form
   *
   * Element IDs match the post meta box so the existing picker and sync
   * scripts work here unchanged.
   *
   * @param string $taxonomy The taxonomy slug
   * @since 2.0.0
   * @return void
   */
  public function render_term_add_fields($taxonomy)
  {
    // No referer field — core already emits _wp_http_referer in this form.
    wp_nonce_field('meta_override_term', 'meta_override_term_nonce', false);
?>
    <div class="mo-term-fields">
      <div class="form-field">
        <label for="meta_title"><?php esc_html_e('Meta Title', 'meta-override'); ?></label>
        <input type="text" id="meta_title" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_META_TITLE); ?>" value="">
        <span class="mo-char-count"></span>
      </div>
      <div class="form-field">
        <label for="meta_description"><?php esc_html_e('Meta Description', 'meta-override'); ?></label>
        <textarea id="meta_description" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_META_DESCRIPTION); ?>" rows="3"></textarea>
        <span class="mo-char-count"></span>
      </div>
      <div class="form-field">
        <label for="og_title"><?php esc_html_e('OG Title', 'meta-override'); ?></label>
        <input type="text" id="og_title" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_TITLE); ?>" value="">
      </div>
      <div class="form-field">
        <label for="og_description"><?php esc_html_e('OG Description', 'meta-override'); ?></label>
        <textarea id="og_description" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_DESCRIPTION); ?>" rows="3"></textarea>
      </div>
      <div class="form-field mo-image-section">
        <label for="og_image"><?php esc_html_e('OG Image', 'meta-override'); ?></label>
        <div class="mo-image-input-wrapper">
          <input type="text" id="og_image" class="mo-input" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_IMAGE); ?>" value="">
          <input type="hidden" id="og_image_id" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_IMAGE_ID); ?>" value="">
          <button type="button" class="button mo-image-picker-button" id="og_image_button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
        </div>
      </div>
      <?php $this->render_term_twitter_fields(); ?>
    </div>
<?php
  }

  /**
   * Render Meta Override fields on the "edit term" form
   *
   * The edit screen is a form-table, so each field is a table row.
   *
   * @param WP_Term $term     The term being edited
   * @param string  $taxonomy The taxonomy slug
   * @since 2.0.0
   * @return void
   */
  public function render_term_edit_fields($term, $taxonomy = '')
  {
    // No referer field — core already emits _wp_http_referer in this form.
    wp_nonce_field('meta_override_term', 'meta_override_term_nonce', false);

    $meta_data = Meta_Override_Helper::get_all_term_meta_data($term->term_id);
?>
    <tr class="form-field">
      <th scope="row" colspan="2"><h2><?php esc_html_e('Meta Override', 'meta-override'); ?></h2></th>
    </tr>
    <tr class="form-field">
      <th scope="row"><label for="meta_title"><?php esc_html_e('Meta Title', 'meta-override'); ?></label></th>
      <td>
        <input type="text" id="meta_title" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_META_TITLE); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_META_TITLE]); ?>">
        <span class="mo-char-count"></span>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row"><label for="meta_description"><?php esc_html_e('Meta Description', 'meta-override'); ?></label></th>
      <td>
        <textarea id="meta_description" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_META_DESCRIPTION); ?>" rows="3"><?php echo esc_textarea($meta_data[Meta_Override_Constants::FIELD_META_DESCRIPTION]); ?></textarea>
        <span class="mo-char-count"></span>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row"><label for="og_title"><?php esc_html_e('OG Title', 'meta-override'); ?></label></th>
      <td><input type="text" id="og_title" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_TITLE); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_OG_TITLE]); ?>"></td>
    </tr>
    <tr class="form-field">
      <th scope="row"><label for="og_description"><?php esc_html_e('OG Description', 'meta-override'); ?></label></th>
      <td><textarea id="og_description" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_DESCRIPTION); ?>" rows="3"><?php echo esc_textarea($meta_data[Meta_Override_Constants::FIELD_OG_DESCRIPTION]); ?></textarea></td>
    </tr>
    <tr class="form-field mo-image-section">
      <th scope="row"><label for="og_image"><?php esc_html_e('OG Image', 'meta-override'); ?></label></th>
      <td>
        <div class="mo-image-input-wrapper">
          <input type="text" id="og_image" class="mo-input" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_IMAGE); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE]); ?>">
          <input type="hidden" id="og_image_id" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_OG_IMAGE_ID); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_ID]); ?>">
          <button type="button" class="button mo-image-picker-button" id="og_image_button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
        </div>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row"><label for="twitter_title"><?php esc_html_e('Twitter Title', 'meta-override'); ?></label></th>
      <td>
        <input type="text" id="twitter_title" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_TITLE); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE]); ?>">
        <label class="mo-checkbox">
          <input type="checkbox" id="twitter_title_same_as_og" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC); ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC], 'on'); ?>>
          <?php esc_html_e('Same as OG Title', 'meta-override'); ?>
        </label>
      </td>
    </tr>
    <tr class="form-field">
      <th scope="row"><label for="twitter_description"><?php esc_html_e('Twitter Description', 'meta-override'); ?></label></th>
      <td>
        <textarea id="twitter_description" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION); ?>" rows="3"><?php echo esc_textarea($meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION]); ?></textarea>
        <label class="mo-checkbox">
          <input type="checkbox" id="twitter_description_same_as_og" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC); ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC], 'on'); ?>>
          <?php esc_html_e('Same as OG Description', 'meta-override'); ?>
        </label>
      </td>
    </tr>
    <tr class="form-field mo-image-section">
      <th scope="row"><label for="twitter_image"><?php esc_html_e('Twitter Image', 'meta-override'); ?></label></th>
      <td>
        <div class="mo-image-input-wrapper">
          <input type="text" id="twitter_image" class="mo-input" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_IMAGE); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE]); ?>">
          <input type="hidden" id="twitter_image_id" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_IMAGE_ID); ?>" value="<?php echo esc_attr($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE_ID]); ?>">
          <button type="button" class="button mo-image-picker-button" id="twitter_image_button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
        </div>
        <label class="mo-checkbox">
          <input type="checkbox" id="twitter_image_same_as_og" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC); ?>" <?php checked($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC], 'on'); ?>>
          <?php esc_html_e('Same as OG Image', 'meta-override'); ?>
        </label>
      </td>
    </tr>
<?php
  }

  /**
   * Render the Twitter block for the "add new term" form
   *
   * @since 2.0.0
   * @return void
   */
  private function render_term_twitter_fields()
  {
?>
    <div class="form-field">
      <label for="twitter_title"><?php esc_html_e('Twitter Title', 'meta-override'); ?></label>
      <input type="text" id="twitter_title" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_TITLE); ?>" value="">
      <label class="mo-checkbox">
        <input type="checkbox" id="twitter_title_same_as_og" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC); ?>">
        <?php esc_html_e('Same as OG Title', 'meta-override'); ?>
      </label>
    </div>
    <div class="form-field">
      <label for="twitter_description"><?php esc_html_e('Twitter Description', 'meta-override'); ?></label>
      <textarea id="twitter_description" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION); ?>" rows="3"></textarea>
      <label class="mo-checkbox">
        <input type="checkbox" id="twitter_description_same_as_og" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC); ?>">
        <?php esc_html_e('Same as OG Description', 'meta-override'); ?>
      </label>
    </div>
    <div class="form-field mo-image-section">
      <label for="twitter_image"><?php esc_html_e('Twitter Image', 'meta-override'); ?></label>
      <div class="mo-image-input-wrapper">
        <input type="text" id="twitter_image" class="mo-input" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_IMAGE); ?>" value="">
        <input type="hidden" id="twitter_image_id" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_IMAGE_ID); ?>" value="">
        <button type="button" class="button mo-image-picker-button" id="twitter_image_button"><?php esc_html_e('Choose Image', 'meta-override'); ?></button>
      </div>
      <label class="mo-checkbox">
        <input type="checkbox" id="twitter_image_same_as_og" name="<?php echo esc_attr(Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC); ?>">
        <?php esc_html_e('Same as OG Image', 'meta-override'); ?>
      </label>
    </div>
<?php
  }

  /**
   * Save meta box data
   *
   * @param int $post_id The post ID
   * @since 1.1.0
   * @return void
   */
  public function save_meta_data($post_id)
  {
    // Check if this is a POST request. isset: WP-CLI has no REQUEST_METHOD,
    // and save_post fires there — a warning here corrupts --format output.
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    // Check nonce
    if (!isset($_POST['meta_override_meta_box_nonce'])) {
      return;
    }

    if (!wp_verify_nonce($_POST['meta_override_meta_box_nonce'], 'meta_override_meta_box')) {
      return;
    }

    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
      return;
    }

    // Check permissions
    $post_type = get_post_type($post_id);
    $post_type_object = get_post_type_object($post_type);

    if (!$post_type_object) {
      return;
    }

    if (!current_user_can($post_type_object->cap->edit_post, $post_id)) {
      return;
    }

    // Check if this post type is supported
    if (!in_array($post_type, $this->get_supported_post_types(), true)) {
      return;
    }

    $this->save_fields(
      $post_id,
      Meta_Override_Constants::OBJECT_POST,
      Meta_Override_Constants::get_all_fields()
    );
  }

  /**
   * Save term meta from the add/edit term forms
   *
   * @param int $term_id The term ID
   * @since 2.0.0
   * @return void
   */
  public function save_term_meta($term_id)
  {
    if (!isset($_SERVER['REQUEST_METHOD']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
      return;
    }

    if (!isset($_POST['meta_override_term_nonce'])) {
      return;
    }

    if (!wp_verify_nonce($_POST['meta_override_term_nonce'], 'meta_override_term')) {
      return;
    }

    // The nonce is form-scoped, not term-scoped, so tie this hook firing to
    // the term the request is actually about. created_/edited_{$taxonomy} can
    // fire again in the same request for OTHER terms — term-sync plugins,
    // translation mirrors, default child terms — and those writes must not
    // inherit this form's field values.
    $request_action = isset($_POST['action']) && is_string($_POST['action'])
      ? wp_unslash($_POST['action'])
      : '';
    if (!in_array($request_action, array('add-tag', 'editedtag'), true)) {
      return;
    }
    if ($request_action === 'editedtag') {
      $posted_term_id = isset($_POST['tag_ID']) ? (int) $_POST['tag_ID'] : 0;
      if ($posted_term_id !== (int) $term_id) {
        return;
      }
    }

    $term = get_term($term_id);
    if (!$term || is_wp_error($term)) {
      return;
    }

    // Same request-vs-hook correlation for the taxonomy itself: the add-tag
    // flow has no tag_ID, so a mirrored create in another taxonomy would
    // otherwise still slip through.
    $posted_taxonomy = isset($_POST['taxonomy']) && is_string($_POST['taxonomy'])
      ? wp_unslash($_POST['taxonomy'])
      : '';
    if ($posted_taxonomy !== $term->taxonomy) {
      return;
    }

    if (!current_user_can('edit_term', $term_id)) {
      return;
    }

    if (!in_array($term->taxonomy, Meta_Override_Helper::get_supported_taxonomies(), true)) {
      return;
    }

    $this->save_fields(
      $term_id,
      Meta_Override_Constants::OBJECT_TERM,
      Meta_Override_Constants::get_term_fields()
    );
  }

  /**
   * Write a posted field set to post or term meta
   *
   * @param int    $object_id
   * @param string $object_type Meta_Override_Constants::OBJECT_POST or OBJECT_TERM
   * @param array  $fields      Field names to persist
   * @since 2.0.0
   * @return void
   */
  private function save_fields($object_id, $object_type, $fields)
  {
    $is_term = ($object_type === Meta_Override_Constants::OBJECT_TERM);
    $checkbox_fields = Meta_Override_Constants::get_checkbox_fields();

    foreach ($fields as $field) {
      $meta_key = Meta_Override_Constants::get_meta_key($field);

      if (in_array($field, $checkbox_fields, true)) {
        $value = isset($_POST[$field]) ? 'on' : 'off';
      } elseif (isset($_POST[$field])) {
        $value = Meta_Override_Helper::sanitize_meta_value($field, $_POST[$field]);
      } else {
        continue;
      }

      // Additional validation for image IDs. Blank rather than skip: a saved
      // ID whose attachment has been deleted must not survive a re-save.
      if (strpos($field, '_image_id') !== false && !empty($value) && $value !== 'off') {
        if (!wp_attachment_is_image($value)) {
          $value = '';
        }
      }

      if (empty($value) || $value === 'off') {
        $is_term
          ? delete_term_meta($object_id, $meta_key)
          : delete_post_meta($object_id, $meta_key);
      } else {
        $is_term
          ? update_term_meta($object_id, $meta_key, $value)
          : update_post_meta($object_id, $meta_key, $value);
      }
    }

    // Clear cache after saving
    Meta_Override_Helper::clear_cache($object_id, $object_type);
  }
}
