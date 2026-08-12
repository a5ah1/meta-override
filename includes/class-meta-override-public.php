<?php

/**
 * Public-facing functionality for Meta Override plugin
 *
 * Handles front-end operations including outputting meta tags,
 * Open Graph data, Twitter cards, and Schema.org structured data.
 *
 * @package Meta_Override
 * @since   1.1.0
 */
class Meta_Override_Public
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
   * Memoized context for the current request
   *
   * @var array|null|false false = not yet resolved, null = no applicable context
   */
  private $context = false;

  /**
   * Initialize the public class
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
   * Resolve what kind of page this is, and everything that derives from it
   *
   * Single source of truth for context — og:type, the Schema.org @type, the
   * canonical URL and the meta source all come from here rather than being
   * re-derived independently at each use.
   *
   * @return array|null Context array, or null when Meta Override does not apply
   * @since 2.0.0
   */
  private function get_context()
  {
    if ($this->context !== false) {
      return $this->context;
    }

    // pre_get_document_title fires for any wp_get_document_title() call, and
    // a plugin may make one before the main query is parsed. Resolving then
    // would memoize a wrong null for the whole request, so don't cache until
    // the conditionals are trustworthy.
    if (!did_action('wp')) {
      return null;
    }

    $this->context = $this->resolve_context();

    if ($this->context !== null && !$this->is_context_enabled($this->context)) {
      $this->context = null;
    }

    return $this->context;
  }

  /**
   * Build the context descriptor for the current query
   *
   * @return array|null
   * @since 2.0.0
   */
  private function resolve_context()
  {
    $base = array(
      'context' => '',
      'object_id' => 0,
      'object_type' => '',
      'post_type' => '',
      'taxonomy' => '',
      'og_type' => 'website',
      'schema_type' => 'WebPage',
      'url' => '',
      'is_dated' => false,
      'fallback_post_type' => '',
    );

    // Static front page. Checked before is_singular() because it is also
    // singular, but a homepage is not an article.
    if (is_front_page() && !is_home()) {
      $id = (int) get_queried_object_id();
      return array_merge($base, array(
        'context' => 'front',
        'object_id' => $id,
        'object_type' => Meta_Override_Constants::OBJECT_POST,
        'post_type' => $id ? get_post_type($id) : '',
        'schema_type' => 'WebPage',
        'url' => $id ? get_permalink($id) : home_url('/'),
        'fallback_post_type' => $id ? get_post_type($id) : '',
      ));
    }

    // Blog index — either the site root in "latest posts" mode, or the
    // assigned Posts page.
    if (is_home()) {
      // Only trust page_for_posts when a static front page is actually
      // configured; WordPress keeps the value after switching back to
      // "Your latest posts", where it would point at an orphaned page.
      $posts_page = (get_option('show_on_front') === 'page')
        ? (int) get_option('page_for_posts')
        : 0;

      return array_merge($base, array(
        'context' => 'home',
        'object_id' => $posts_page,
        'object_type' => $posts_page ? Meta_Override_Constants::OBJECT_POST : '',
        'post_type' => $posts_page ? get_post_type($posts_page) : '',
        'schema_type' => 'CollectionPage',
        'url' => $posts_page ? get_permalink($posts_page) : home_url('/'),
        'fallback_post_type' => $posts_page ? get_post_type($posts_page) : '',
      ));
    }

    if (is_singular()) {
      $id = (int) get_queried_object_id();
      if (!$id) {
        return null;
      }

      $post_type = get_post_type($id);
      $post_type_object = get_post_type_object($post_type);

      // Hierarchical types are page-like; flat types are article-like. This
      // gives post => article and page => website, and a sane default for
      // custom types.
      $is_article = $post_type_object && !$post_type_object->hierarchical;

      $canonical = wp_get_canonical_url($id);

      return array_merge($base, array(
        'context' => 'singular',
        'object_id' => $id,
        'object_type' => Meta_Override_Constants::OBJECT_POST,
        'post_type' => $post_type,
        'og_type' => $is_article ? 'article' : 'website',
        'schema_type' => 'WebPage',
        'url' => $canonical ? $canonical : get_permalink($id),
        'is_dated' => $is_article,
        'fallback_post_type' => $post_type,
      ));
    }

    // Taxonomy term archives — category, tag, and custom taxonomies.
    if (is_category() || is_tag() || is_tax()) {
      $term = get_queried_object();
      if (!($term instanceof WP_Term)) {
        return null;
      }

      $link = get_term_link($term);

      return array_merge($base, array(
        'context' => 'term',
        'object_id' => (int) $term->term_id,
        'object_type' => Meta_Override_Constants::OBJECT_TERM,
        'taxonomy' => $term->taxonomy,
        'schema_type' => 'CollectionPage',
        'url' => is_wp_error($link) ? '' : $link,
      ));
    }

    // Post type archives.
    if (is_post_type_archive()) {
      $post_type = get_query_var('post_type');
      if (is_array($post_type)) {
        $post_type = reset($post_type);
      }
      if (!$post_type || !post_type_exists($post_type)) {
        return null;
      }

      $link = get_post_type_archive_link($post_type);

      return array_merge($base, array(
        'context' => 'post_type_archive',
        'post_type' => $post_type,
        'schema_type' => 'CollectionPage',
        'url' => $link ? $link : '',
        'fallback_post_type' => $post_type,
      ));
    }

    return null;
  }

  /**
   * Decide whether Meta Override is turned on for a resolved context
   *
   * @param array $context
   * @return bool
   * @since 2.0.0
   */
  private function is_context_enabled($context)
  {
    switch ($context['context']) {
      case 'front':
      case 'home':
        // The site home has its own settings and is never governed by the
        // post type checkboxes — unticking "page" must not blank the homepage.
        return true;

      case 'singular':
        return in_array($context['post_type'], Meta_Override_Helper::get_supported_post_types(), true);

      case 'term':
        // Terms of an unsupported taxonomy still emit scaffolding-only output
        // (get_context_meta() withholds their term meta), so the master switch
        // is the only gate here.
        return Meta_Override_Settings::archives_enabled();

      case 'post_type_archive':
        // Both gates: unticking a post type must silence its archive too,
        // not just its singles — the archive reads that type's own overrides.
        return Meta_Override_Settings::archives_enabled()
          && in_array($context['post_type'], Meta_Override_Helper::get_supported_post_types(), true);
    }

    return false;
  }

  /**
   * Get the resolved meta values for a context
   *
   * Post- and term-backed contexts read their own meta. Option-backed
   * contexts (the "latest posts" home, post type archives) read the settings
   * array and mirror OG values into the Twitter tags, since a separate
   * Twitter field set for them would be all cost and no benefit.
   *
   * @param array $context
   * @return array Field-shaped meta array
   * @since 2.0.0
   */
  private function get_context_meta($context)
  {
    switch ($context['context']) {
      case 'singular':
      case 'front':
        return Meta_Override_Helper::get_all_meta_data($context['object_id']);

      case 'home':
        if ($context['object_id']) {
          return Meta_Override_Helper::get_all_meta_data($context['object_id']);
        }
        return $this->meta_from_settings(Meta_Override_Settings::get_home_meta());

      case 'term':
        if (in_array($context['taxonomy'], Meta_Override_Helper::get_supported_taxonomies(), true)) {
          return Meta_Override_Helper::get_all_term_meta_data($context['object_id']);
        }
        // Archive output is on but this taxonomy has no editable fields —
        // emit the scaffolding and site-wide fallbacks only.
        return Meta_Override_Helper::get_object_meta_data(0);

      case 'post_type_archive':
        return $this->meta_from_settings(Meta_Override_Settings::get_archive_meta($context['post_type']));
    }

    return Meta_Override_Helper::get_object_meta_data(0);
  }

  /**
   * Expand an option-backed field set into the full meta shape
   *
   * @param array $values
   * @return array
   * @since 2.0.0
   */
  private function meta_from_settings($values)
  {
    $data = array_fill_keys(Meta_Override_Constants::get_all_fields(), '');

    foreach (Meta_Override_Constants::get_context_fields() as $field) {
      $data[$field] = isset($values[$field]) ? $values[$field] : '';
    }

    // Reuse the existing Twitter cascade rather than duplicating fields.
    $data[Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC] = 'on';
    $data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC] = 'on';
    $data[Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC] = 'on';

    return $data;
  }

  /**
   * Override document title
   *
   * @param string $title The default title
   * @return string The overridden title or default
   * @since 1.1.0
   */
  public function override_title($title)
  {
    $context = $this->get_context();

    if (!$context) {
      return $title;
    }

    $meta_data = $this->get_context_meta($context);
    $meta_title = $meta_data[Meta_Override_Constants::FIELD_META_TITLE];

    if (!empty($meta_title)) {
      return esc_html($meta_title);
    }

    return $title;
  }

  /**
   * Output meta tags, Open Graph, Twitter cards, and Schema.org
   *
   * @since 1.1.0
   * @return void
   */
  public function output_meta_tags()
  {
    $context = $this->get_context();

    if (!$context) {
      return;
    }

    $meta_data = $this->get_context_meta($context);
    $canonical_url = $context['url'];
    $site_name = get_bloginfo('name');
    $post_id = ($context['object_type'] === Meta_Override_Constants::OBJECT_POST)
      ? $context['object_id']
      : 0;

    $description = $meta_data[Meta_Override_Constants::FIELD_META_DESCRIPTION];
    if (!empty($description)) {
      echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    echo '<meta property="og:type" content="' . esc_attr($context['og_type']) . '">' . "\n";
    if (!empty($canonical_url)) {
      echo '<meta property="og:url" content="' . esc_url($canonical_url) . '">' . "\n";
    }
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";

    $og_title = $meta_data[Meta_Override_Constants::FIELD_OG_TITLE];
    if (!empty($og_title)) {
      echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
    }

    $og_description = $meta_data[Meta_Override_Constants::FIELD_OG_DESCRIPTION];
    if (!empty($og_description)) {
      echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
    }

    // Resolve OG image: featured (if opted-in) → per-object → per-post-type fallback → site-wide fallback
    $og_image_url = '';
    $og_image_id = '';
    if ($post_id && $meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_USE_FEATURED] === 'on' && has_post_thumbnail($post_id)) {
      $og_image_id = get_post_thumbnail_id($post_id);
      $og_image_url = get_the_post_thumbnail_url($post_id, 'full');
    } elseif (!empty($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE])) {
      $og_image_url = $meta_data[Meta_Override_Constants::FIELD_OG_IMAGE];
      $og_image_id = $meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_ID];
    } else {
      $fallback = Meta_Override_Settings::get_og_image_fallback($context['fallback_post_type']);
      $og_image_url = $fallback['url'];
      $og_image_id = $fallback['id'];
    }

    if (!empty($og_image_url)) {
      echo '<meta property="og:image" content="' . esc_url($og_image_url) . '">' . "\n";

      if (!empty($og_image_id)) {
        $image_dimensions = Meta_Override_Helper::get_image_dimensions($og_image_id);
        if ($image_dimensions) {
          echo '<meta property="og:image:width" content="' . esc_attr($image_dimensions['width']) . '">' . "\n";
          echo '<meta property="og:image:height" content="' . esc_attr($image_dimensions['height']) . '">' . "\n";
        }
      }
    }

    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

    $twitter_site = Meta_Override_Settings::get_twitter_site();
    if (!empty($twitter_site)) {
      echo '<meta name="twitter:site" content="' . esc_attr($twitter_site) . '">' . "\n";
    }

    $twitter_title = '';
    if ($meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE_SYNC] === 'on') {
      $twitter_title = $og_title;
    } elseif (!empty($meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE])) {
      $twitter_title = $meta_data[Meta_Override_Constants::FIELD_TWITTER_TITLE];
    }

    if (!empty($twitter_title)) {
      echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '">' . "\n";
    }

    $twitter_description = '';
    if ($meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION_SYNC] === 'on') {
      $twitter_description = $og_description;
    } elseif (!empty($meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION])) {
      $twitter_description = $meta_data[Meta_Override_Constants::FIELD_TWITTER_DESCRIPTION];
    }

    if (!empty($twitter_description)) {
      echo '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '">' . "\n";
    }

    $twitter_image = '';
    if ($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE_SYNC] === 'on' && !empty($og_image_url)) {
      $twitter_image = $og_image_url;
    } elseif (!empty($meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE])) {
      $twitter_image = $meta_data[Meta_Override_Constants::FIELD_TWITTER_IMAGE];
    }

    if (!empty($twitter_image)) {
      echo '<meta name="twitter:image" content="' . esc_url($twitter_image) . '">' . "\n";
    }

    // Date meta tags only where the context is genuinely article-like.
    if ($post_id && $context['is_dated']) {
      // The trailing Z means UTC, so the timestamps must be GMT — the local
      // wall-clock time here would misstate the moment on any non-UTC site.
      $publish_date = get_the_date('Y-m-d', $post_id);
      $publish_time = get_post_time('Y-m-d\TH:i:s\Z', true, $post_id);
      $modified_time = get_post_modified_time('Y-m-d\TH:i:s\Z', true, $post_id);

      echo '<meta name="date" content="' . esc_attr($publish_date) . '">' . "\n";
      echo '<meta property="article:published_time" content="' . esc_attr($publish_time) . '">' . "\n";
      echo '<meta property="article:modified_time" content="' . esc_attr($modified_time) . '">' . "\n";
    }

    $this->output_schema_org($context, $canonical_url, $description, $og_image_url, $og_image_id);
  }

  /**
   * Output Schema.org JSON-LD structured data
   *
   * @param array  $context       The resolved context
   * @param string $canonical_url The canonical URL
   * @param string $description   The description to use
   * @param string $og_image_url  The resolved OG image URL
   * @param mixed  $og_image_id   The resolved OG image attachment ID
   * @return void
   * @since 1.1.0
   */
  private function output_schema_org($context, $canonical_url, $description, $og_image_url, $og_image_id)
  {
    $post_id = ($context['object_type'] === Meta_Override_Constants::OBJECT_POST)
      ? $context['object_id']
      : 0;

    // The title override escapes for the HTML <title> context, and
    // wp_get_document_title() would hand that entity-encoded string straight
    // into the JSON. Prefer the raw stored value when there is one.
    $meta_data = $this->get_context_meta($context);
    $meta_title = $meta_data[Meta_Override_Constants::FIELD_META_TITLE];

    $schema = array(
      '@context' => 'https://schema.org',
      '@type' => $context['schema_type'],
      'name' => !empty($meta_title) ? $meta_title : wp_get_document_title(),
    );

    if (!empty($canonical_url)) {
      $schema['url'] = $canonical_url;
    }

    if (!empty($description)) {
      $schema['description'] = $description;
    }

    if ($post_id && $context['is_dated']) {
      $schema['datePublished'] = get_the_date('c', $post_id);
      $schema['dateModified'] = get_the_modified_date('c', $post_id);

      $author_id = get_post_field('post_author', $post_id);
      if ($author_id) {
        $schema['author'] = array(
          '@type' => 'Person',
          'name' => get_the_author_meta('display_name', $author_id),
        );
      }
    }

    // Add image if available
    if (!empty($og_image_url)) {
      $schema['image'] = array(
        '@type' => 'ImageObject',
        'url' => $og_image_url
      );

      if (!empty($og_image_id)) {
        $image_dimensions = Meta_Override_Helper::get_image_dimensions($og_image_id);
        if ($image_dimensions) {
          $schema['image']['width'] = $image_dimensions['width'];
          $schema['image']['height'] = $image_dimensions['height'];
        }
      }
    }

    /**
     * Filter the Schema.org structured data before output
     *
     * @param array $schema  The schema array
     * @param int   $post_id The post ID (0 for contexts with no post)
     * @param array $context The resolved page context
     * @since 1.1.0
     */
    $schema = apply_filters('meta_override_schema_org', $schema, $post_id, $context);

    // JSON_HEX_TAG keeps a "</script>" inside any value (e.g. from a
    // meta_override_schema_org callback) from closing the block early.
    echo '<script type="application/ld+json">'
      . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG)
      . '</script>' . "\n";
  }
}
