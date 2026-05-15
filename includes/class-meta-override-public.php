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
   * Override document title
   *
   * @param string $title The default title
   * @return string The overridden title or default
   * @since 1.1.0
   */
  public function override_title($title)
  {
    $post_id = $this->get_current_post_id();

    if ($post_id) {
      $meta_data = Meta_Override_Helper::get_all_meta_data($post_id);
      $meta_title = $meta_data[Meta_Override_Constants::FIELD_META_TITLE];

      if (!empty($meta_title)) {
        return esc_html($meta_title);
      }
    }

    return $title;
  }

  /**
   * Get the current post ID based on context
   *
   * @return int|null Post ID or null if not applicable
   * @since 1.1.0
   */
  private function get_current_post_id()
  {
    if (is_singular()) {
      return get_queried_object_id();
    } elseif (is_home()) {
      // Blog page - get the page set as "Posts page" in Settings → Reading
      return (int) get_option('page_for_posts');
    }

    return null;
  }

  /**
   * Output meta tags, Open Graph, Twitter cards, and Schema.org
   *
   * @since 1.1.0
   * @return void
   */
  public function output_meta_tags()
  {
    $is_singular = is_singular();
    $is_blog_page = is_home();
    $is_front_page = is_front_page();

    if (!$is_singular && !$is_blog_page && !$is_front_page) {
      return;
    }

    $post_id = $this->get_current_post_id(); // may be 0 on a posts-at-root home with no Posts page assigned
    $meta_data = Meta_Override_Helper::get_all_meta_data($post_id);
    $canonical_url = $this->get_canonical_url($post_id, $is_blog_page);
    $site_name = get_bloginfo('name');

    $description = $meta_data[Meta_Override_Constants::FIELD_META_DESCRIPTION];
    if (!empty($description)) {
      echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    $og_type = ($is_blog_page || !$post_id) ? 'website' : 'article';
    echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical_url) . '">' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr($site_name) . '">' . "\n";

    $og_title = $meta_data[Meta_Override_Constants::FIELD_OG_TITLE];
    if (!empty($og_title)) {
      echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
    }

    $og_description = $meta_data[Meta_Override_Constants::FIELD_OG_DESCRIPTION];
    if (!empty($og_description)) {
      echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
    }

    // Resolve OG image: featured (if opted-in) → per-post → per-post-type fallback → site-wide fallback
    $og_image_url = '';
    $og_image_id = '';
    if ($post_id && $meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_USE_FEATURED] === 'on' && has_post_thumbnail($post_id)) {
      $og_image_id = get_post_thumbnail_id($post_id);
      $og_image_url = get_the_post_thumbnail_url($post_id, 'full');
    } elseif (!empty($meta_data[Meta_Override_Constants::FIELD_OG_IMAGE])) {
      $og_image_url = $meta_data[Meta_Override_Constants::FIELD_OG_IMAGE];
      $og_image_id = $meta_data[Meta_Override_Constants::FIELD_OG_IMAGE_ID];
    } else {
      $post_type_for_fallback = $post_id ? get_post_type($post_id) : '';
      $fallback = Meta_Override_Settings::get_og_image_fallback($post_type_for_fallback);
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

    // Date meta tags only for singular posts/pages with a real post_id
    if ($post_id && !$is_blog_page) {
      $publish_date = get_the_date('Y-m-d', $post_id);
      $publish_time = get_the_date('Y-m-d\TH:i:s\Z', $post_id);
      $modified_time = get_the_modified_date('Y-m-d\TH:i:s\Z', $post_id);

      echo '<meta name="date" content="' . esc_attr($publish_date) . '">' . "\n";
      echo '<meta property="article:published_time" content="' . esc_attr($publish_time) . '">' . "\n";
      echo '<meta property="article:modified_time" content="' . esc_attr($modified_time) . '">' . "\n";
    }

    $this->output_schema_org($post_id, $is_blog_page, $canonical_url, $description, $og_image_url, $og_image_id);
  }

  /**
   * Output Schema.org JSON-LD structured data
   *
   * @param int    $post_id       The post ID
   * @param bool   $is_blog_page  Whether this is the blog page
   * @param string $canonical_url The canonical URL
   * @param string $description   The description to use
   * @param string $og_image_url  The resolved OG image URL
   * @param mixed  $og_image_id   The resolved OG image attachment ID
   * @return void
   * @since 1.1.0
   */
  private function output_schema_org($post_id, $is_blog_page, $canonical_url, $description, $og_image_url, $og_image_id)
  {
    $schema = array(
      '@context' => 'https://schema.org',
      '@type' => ($is_blog_page || !$post_id) ? 'CollectionPage' : 'WebPage',
      'name' => wp_get_document_title(),
      'url' => $canonical_url,
    );

    if (!empty($description)) {
      $schema['description'] = $description;
    }

    if ($post_id && !$is_blog_page) {
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
     * @param int   $post_id The post ID
     * @since 1.1.0
     */
    $schema = apply_filters('meta_override_schema_org', $schema, $post_id);

    echo '<script type="application/ld+json">'
      . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
      . '</script>' . "\n";
  }

  /**
   * Get canonical URL for current page
   *
   * @param int  $post_id      The post ID
   * @param bool $is_blog_page Whether this is the blog page
   * @return string The canonical URL
   * @since 1.1.0
   */
  private function get_canonical_url($post_id, $is_blog_page)
  {
    if ($is_blog_page) {
      // For the blog page, use get_permalink with the page_for_posts ID
      // This correctly handles cases where the blog is at /blog/ or similar
      return $post_id ? get_permalink($post_id) : home_url('/');
    }

    // For singular posts/pages, use the standard canonical URL
    $canonical = wp_get_canonical_url($post_id);
    return $canonical ? $canonical : get_permalink($post_id);
  }
}
