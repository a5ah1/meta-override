<?php

/**
 * Admin functionality for Meta Override plugin
 *
 * Handles admin-side operations including meta boxes, saving data,
 * and enqueuing admin assets.
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
   * Enqueue admin styles
   *
   * @since 1.1.0
   * @return void
   */
  public function enqueue_styles()
  {
    $screen = get_current_screen();
    if ($screen && in_array($screen->post_type, $this->get_supported_post_types(), true)) {
      wp_enqueue_style(
        $this->plugin_name,
        META_OVERRIDE_PLUGIN_URL . 'assets/css/admin-styles.css',
        array(),
        $this->version,
        'all'
      );
    }
  }

  /**
   * Enqueue admin scripts
   *
   * @since 1.1.0
   * @return void
   */
  public function enqueue_scripts()
  {
    $screen = get_current_screen();
    if ($screen && in_array($screen->post_type, $this->get_supported_post_types(), true)) {
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
   * Save meta box data
   *
   * @param int $post_id The post ID
   * @since 1.1.0
   * @return void
   */
  public function save_meta_data($post_id)
  {
    // Check if this is a POST request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    $checkbox_fields = Meta_Override_Constants::get_checkbox_fields();

    foreach (Meta_Override_Constants::get_all_fields() as $field) {
      $meta_key = Meta_Override_Constants::get_meta_key($field);

      if (in_array($field, $checkbox_fields, true)) {
        $value = isset($_POST[$field]) ? 'on' : 'off';
      } elseif (isset($_POST[$field])) {
        $value = Meta_Override_Helper::sanitize_meta_value($field, $_POST[$field]);
      } else {
        continue;
      }

      // Additional validation for image IDs
      if (strpos($field, '_image_id') !== false && !empty($value) && $value !== 'off') {
        if (!wp_attachment_is_image($value)) {
          continue; // Skip invalid image IDs
        }
      }

      if (empty($value) || $value === 'off') {
        delete_post_meta($post_id, $meta_key);
      } else {
        update_post_meta($post_id, $meta_key, $value);
      }
    }

    // Clear cache after saving
    Meta_Override_Helper::clear_cache($post_id);
  }
}
