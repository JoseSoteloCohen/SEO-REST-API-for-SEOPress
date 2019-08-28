<?php
/**
 * Plugin Name: SEO REST API for SEOPress
 * Description: Adds SEOPress fields to page and post metadata to WP REST API responses
 * Author: José Sotelo
 * Author URI: https://inboundlatino.com
 * Version: 1
 * Plugin URI: https://github.com/JoseSoteloCohen/SEO-REST-API-for-SEOPress
 */
class SEOPress_To_REST_API {
	protected $keys = array(
		'seopress_focuskw',
		'seopress_title',
		'seopress_metadesc',
		'seopress_linkdex',
		'seopress_metakeywords',
		'seopress_meta-robots-noindex',
		'seopress_meta-robots-nofollow',
		'seopress_meta-robots-adv',
		'seopress_canonical',
		'seopress_redirect',
		'seopress_opengraph-title',
		'seopress_opengraph-description',
		'seopress_opengraph-image',
		'seopress_twitter-title',
		'seopress_twitter-description',
		'seopress_twitter-image',
	);
	function __construct() {
		add_action( 'rest_api_init', array( $this, 'add_seopress_data' ) );
	}
	function add_seopress_data() {
		// Posts
		register_rest_field(
			'post',
			'seopress_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_seopress' ),
				'update_callback' => array( $this, 'wp_api_update_seopress' ),
				'schema'          => null,
			)
		);
		// Pages
		register_rest_field(
			'page',
			'seopress_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_seopress' ),
				'update_callback' => array( $this, 'wp_api_update_seopress' ),
				'schema'          => null,
			)
		);
		// Category
		register_rest_field(
			'category',
			'seopress_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_seopress_category' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
		// Tag
		register_rest_field(
			'tag',
			'seopress_meta',
			array(
				'get_callback'    => array( $this, 'wp_api_encode_seopress_tag' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
		// Public custom post types
		$types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			)
		);
		foreach ( $types as $key => $type ) {
			register_rest_field(
				$type,
				'seopress_meta',
				array(
					'get_callback'    => array( $this, 'wp_api_encode_seopress' ),
					'update_callback' => array( $this, 'wp_api_update_seopress' ),
					'schema'          => null,
				)
			);
		}
	}
	/**
	 * Updates post meta with values from post/put request.
	 *
	 * @param array $value
	 * @param object $data
	 * @param string $field_name
	 *
	 * @return array
	 */
	function wp_api_update_seopress( $value, $data, $field_name ) {
		foreach ( $value as $k => $v ) {
			if ( in_array( $k, $this->keys ) ) {
				! empty( $k ) ? update_post_meta( $data->ID, '_' . $k, $v ) : null;
			}
		}
		return $this->wp_api_encode_seopress( $data->ID, null, null );
	}
	function wp_api_encode_seopress( $p, $field_name, $request ) {
		$seopress_frontend = SEOPress_Frontend_To_REST_API::get_instance();
		$seopress_frontend->reset();
		query_posts(
			array(
				'p'         => $p['id'], // ID of a page, post, or custom type
				'post_type' => 'any',
			)
		);
		the_post();
		// title options — defaults.
		$seopress_meta = array(
			'seopress_title'                => get_post_meta( $p['id'], '_seopress_titles_title', true ), //$seopress_frontend->get_content_title() ?? '',
			'seopress_metadesc'             => get_post_meta( $p['id'], '_seopress_titles_desc', true ), //$seopress_frontend->metadesc( false ) ?? '',
			'seopress_canonical'            => get_post_meta( $p['id'], '_seopress_robots_canonical', true ), //$seopress_frontend->canonical( false ) ?? '',
			'seopress_facebook_title'       => get_post_meta( $p['id'], '_seopress_social_fb_title', true ),
			'seopress_facebook_description' => get_post_meta( $p['id'], '_seopress_social_fb_desc', true ),
			'seopressfacebook_type'        => $p['type'] ?? '',
			'seopress_facebook_image'       => get_post_meta( $p['id'], '_seopress_social_fb_img', true ),
			'seopress_twitter_title'        => get_post_meta( $p['id'], '_seopress_social_twitter_title', true ),
			'seopress_twitter_description'  => get_post_meta( $p['id'], '_seopress_social_twitter_desc', true ),
			'seopress_twitter_image'        => get_post_meta( $p['id'], '_seopress_social_twitter_img', true ),
			'seopress_social_url'           => get_permalink( $p['id'] ) ?? '',
		);
		/**
		 * Filter the returned seopress meta.
		 *
		 * @since 1.4.2
		 * @param array $seopress_meta Array of metadata to return from seopress.
		 * @param \WP_Post $p The current post object.
		 * @param \WP_REST_Request $request The REST request.
		 * @return array $seopress_meta Filtered meta array.
		 */
		$seopress_meta = apply_filters( 'seopress_to_api_meta', $seopress_meta, $p, $request );
		wp_reset_query();
		return (array) $seopress_meta;
	}
	private function wp_api_encode_taxonomy() {
		$seopress_frontend = SEOPress_Frontend_To_REST_API::get_instance();
		$seopress_frontend->reset();
		$seopress_meta = array(
			'seopress_title'           => get_option( 'seopress_titles_tax_titles' ), //$seopress_frontend->get_taxonomy_title(),
			'seopress_metadesc'        => get_option( 'seopress_titles_tax_desc' ), //$seopress_frontend->metadesc( false ),
			'seopress_social_defaults' => get_option( 'seopress_social_fb_title' ),
		);
		/**
		 * Filter the returned seopress meta for a taxonomy.
		 *
		 * @since 1.4.2
		 * @param array $seopress_meta Array of metadata to return from seopress.
		 * @return array $seopress_meta Filtered meta array.
		 */
		$seopress_meta = apply_filters( 'seopress_to_api_taxonomy_meta', $seopress_meta );
		return (array) $seopress_meta;
	}
	function wp_api_encode_seopress_category( $category ) {
		query_posts( array( 'cat' => $category['id'] ) );
		the_post();
		$res = $this->wp_api_encode_taxonomy();
		wp_reset_query();
		return $res;
	}
	function wp_api_encode_seopress_tag( $tag ) {
		query_posts( array( 'tag_id' => $tag['id'] ) );
		the_post();
		$res = $this->wp_api_encode_taxonomy();
		wp_reset_query();
		return $res;
	}
}
function WPAPIseopress_init() {
	if ( class_exists( 'SEOPress_Frontend' ) ) {
		include __DIR__ . '/classes/class-seopress-frontend-to-rest-api.php';
		$SEOPress_To_REST_API = new SEOPress_To_REST_API();
	} else {
		add_action( 'admin_notices', 'seopress_not_loaded' );
	}
}
function seopress_not_loaded() {
	printf(
		'<div class="error"><p>%s</p></div>',
		__( '<b>seopress to REST API</b> plugin not working because <b>seopress SEO</b> plugin is not active.' )
	);
}
include_once( 'seopress-to-api.php' );
add_action( 'plugins_loaded', 'WPAPIseopress_init' );
