<?php

namespace Automattic\Jetpack_Boost\Modules\Page_Cache;

/*
 * This file is loaded by advanced-cache.php when it loads Boost_File_Cache.php
 * As it is loaded before WordPress is loaded, it is not autoloaded by Boost.
 */
require_once __DIR__ . '/Boost_Cache_Settings.php';
require_once __DIR__ . '/Boost_Cache_Utils.php';

abstract class Boost_Cache {
	/*
	 * @var array - The settings for the page cache.
	 */
	private $settings;

	/*
	 * @var string - The normalized path for the current request. This is not sanitized. Only to be used for comparison purposes.
	 */
	protected $request_uri = false;

	/*
	 * @var array - The cookies for the current request.
	 */
	protected $cookies;

	/*
	 * @var array - The get parameters for the current request.
	 */
	protected $get;

	public function __construct() {
		$this->settings    = Boost_Cache_Settings::get_instance();
		$this->request_uri = isset( $_SERVER['REQUEST_URI'] )
			? Boost_Cache_Utils::sanitize_file_path( $this->normalize_request_uri( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
			: false;

		/*
		 * Set the cookies and get parameters for the current request.
		 * Sometimes these arrays are modified by WordPress or other plugins.
		 * We need to cache them here so they can be used for the cache key
		 * later.
		 * We don't need to sanitize them, as they are only used for comparison.
		 */
		$this->cookies = $_COOKIE; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$this->get     = $_GET; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended

		$this->init_actions();
	}

	protected function init_actions() {
		/*
		 * I'm not using edit_post because I think we can get everything we need from the other actions.
		 * but we might need it for other events.
		 *  add_action( 'edit_post', array( $this, 'delete_cache_post_edit' ), 0 );
		 */
		add_action( 'transition_post_status', array( $this, 'delete_on_post_transition' ), 10, 3 );
		add_action( 'transition_comment_status', array( $this, 'delete_on_comment_transition' ), 10, 3 );
		add_action( 'comment_post', array( $this, 'delete_on_comment_post' ), 10, 3 );
	}

	/*
	 * Serve the cached page if it exists, otherwise start output buffering.
	 */
	public function serve() {
		if ( ! $this->get() ) {
			$this->ob_start();
		}
	}

	/*
	 * Returns true if the current request has a fatal error.
	 *
	 * @return bool
	 */
	private function is_fatal_error() {
		$error = error_get_last();
		if ( $error === null ) {
			return false;
		}

		$fatal_errors = array(
			E_ERROR,
			E_PARSE,
			E_CORE_ERROR,
			E_COMPILE_ERROR,
			E_USER_ERROR,
		);

		return in_array( $error['type'], $fatal_errors, true );
	}

	/*
	 * Returns true if the request is cacheable.
	 *
	 * If a request is in the backend, or is a POST request, or is not an
	 * html request, it is not cacheable.
	 * The filter boost_cache_cacheable can be used to override this.
	 *
	 * @return bool
	 */
	public function is_cacheable() {
		if ( ! apply_filters( 'boost_cache_cacheable', $this->request_uri ) ) {
			return false;
		}

		if ( defined( 'DONOTCACHEPAGE' ) ) {
			return false;
		}

		// do not cache post previews or customizer previews
		if ( ! empty( $_GET ) && ( isset( $_GET['preview'] ) || isset( $_GET['customize_changeset_uuid'] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
			return false;
		}

		if ( $this->is_fatal_error() ) {
			return false;
		}

		if ( function_exists( 'is_404' ) && is_404() ) {
			return false;
		}

		if ( function_exists( 'is_feed' ) && is_feed() ) {
			return false;
		}

		if ( $this->is_backend() ) {
			return false;
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] !== 'GET' ) {
			return false;
		}

		$accept_headers = apply_filters( 'boost_accept_headers', array( 'application/json', 'application/activity+json', 'application/ld+json' ) );
		$accept_headers = array_map( 'strtolower', $accept_headers );
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.VariableNotSnakeCase
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- $accept is checked and set below.
		$accept = isset( $_SERVER['HTTP_ACCEPT'] ) ? strtolower( filter_var( $_SERVER['HTTP_ACCEPT'] ) ) : '';

		if ( $accept !== '' ) {
			foreach ( $accept_headers as $header ) {
				if ( str_contains( $accept, $header ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/*
	 * Normalize the request uri so it can be used for caching purposes.
	 * It removes the query string and the trailing slash, and characters
	 * that might cause problems with the filesystem.
	 *
	 * **THIS DOES NOT SANITIZE THE VARIABLE IN ANY WAY.**
	 * Only use it for comparison purposes or to generate an MD5 hash.
	 *
	 * @param string $request_uri - The request uri to normalize.
	 * @return string - The normalized request uri.
	 */
	protected function normalize_request_uri( $request_uri ) {
		// get path from request uri
		$request_uri = parse_url( $request_uri, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url
		if ( $request_uri === '' || $request_uri === false || $request_uri === null ) {
			$request_uri = '/';
		} elseif ( substr( $request_uri, -1 ) !== '/' ) {
			$request_uri .= '/';
		}

		return $request_uri;
	}

	/*
	 * Returns a key to identify the visitor's cache file from the request uri,
	 * cookies and get parameters.
	 * Without a parameter, it will use the current request uri.
	 *
	 * @param array $args (optional) An array containing the request uri, cookies and get parameters to calculate the cache key. Defaults to the current request uri, cookies and get parameters.
	 * @return string
	 */
	public function cache_key( $args = array() ) {
		if ( isset( $args['request_uri'] ) && $args['request_uri'] !== $this->request_uri ) {
			$args['request_uri'] = $this->normalize_request_uri( $args['request_uri'] );
		}

		$defaults = array(
			'request_uri' => $this->request_uri,
			'cookies'     => $this->cookies, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			'get'         => $this->get, // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.NonceVerification.Recommended
		);
		$args     = array_merge( $defaults, $args );

		$key_components = apply_filters(
			'boost_cache_key_components',
			$args
		);

		return md5( json_encode( $key_components ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode
	}

	/*
	 * Starts output buffering and sets the callback to save the cache file.
	 *
	 * @return bool - false if page is not cacheable.
	 */
	public function ob_start() {
		if ( ! $this->is_cacheable() ) {
			return false;
		}

		ob_start( array( $this, 'ob_callback' ) );
	}

	/*
	 * Callback function from output buffer. This function saves the output
	 * buffer to a cache file and then returns the buffer so PHP will send it
	 * to the browser.
	 *
	 * @param string $buffer - The output buffer to save to the cache file.
	 * @return string - The output buffer.
	 */
	public function ob_callback( $buffer ) {
		$result = $this->set( $buffer );

		if ( is_wp_error( $result ) ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedIf
			// TODO: log error for site owner
		}

		return $buffer;
	}

	/*
	 * Returns true if the current request is one of the following:
	 * 1. wp-admin
	 * 2. wp-login.php, xmlrpc.php or wp-cron.php/cron request
	 * 3. WP_CLI
	 * 4. REST request.
	 *
	 * @return bool
	 */
	public function is_backend() {

		$is_backend = is_admin();
		if ( $is_backend ) {
			return $is_backend;
		}

		$script = isset( $_SERVER['PHP_SELF'] ) ? basename( $_SERVER['PHP_SELF'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
		if ( $script !== 'index.php' ) {
			if ( in_array( $script, array( 'wp-login.php', 'xmlrpc.php', 'wp-cron.php' ), true ) ) {
				$is_backend = true;
			}
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$is_backend = true;
		}

		if ( PHP_SAPI === 'cli' || ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) ) {
			$is_backend = true;
		}

		if ( defined( 'REST_REQUEST' ) ) {
			$is_backend = true;
		}

		return $is_backend;
	}

	protected function is_visible_post_type( $post ) {
		$post_type = is_object( $post ) ? get_post_type_object( $post->post_type ) : null;
		if ( empty( $post_type ) || ! $post_type->public ) {
			return false;
		}
		return true;
	}

	protected function maybe_clear_front_page_cache( $post ) {
		$front_page_id = get_option( 'show_on_front' ); // posts page
		if ( $front_page_id === 'page' ) {
			$front_page_id = get_option( 'page_on_front' ); // static page
			if ( $front_page_id === $post->ID ) {
				$this->delete_cache_for_post( $post->ID );
			}
		} else {
			// get a list of posts that show on the front page. If $post_id is there delete the cache
			$posts_per_page     = get_option( 'posts_per_page' );
			$latest_posts_query = new \WP_Query(
				array(
					'posts_per_page' => $posts_per_page,
					'post_status'    => 'publish',
					'no_found_rows'  => true,
					'fields'         => 'ids',
				)
			);
			$latest_posts       = $latest_posts_query->get_posts();
			foreach ( $latest_posts as $id ) {
				if ( (int) $id === (int) $post->ID ) {
					$this->delete_cache_for_url( get_home_url(), true );
					return;
				}
			}
		}
	}

	/*
	 * Deletes the cache file for the given post_id.
	 *
	 * @param int post_id of the post to delete the cache for.
	 */
	public function delete_cache_post_edit( $post_id ) {
		$post = get_post( $post_id );
		error_log( "Boost_File_Cache::delete_cache_post_edit( $post_id )" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->delete_cache_for_post( $post );

		$this->maybe_clear_front_page_cache( $post_id );

		/*
		 * Don't delete the cached files for tag/category archives for posts
		 * that are not published.
		 * When this function is called by edit_post it can't know the previous
		 * post status. If the previous post status was "published" or "private"
		 * and now it's "draft" or "pending", or "future" then that will be
		 * handled by delete_on_post_transition().
		 */
		if ( in_array( $post->post_status, array( 'draft', 'pending', 'future', 'auto-draft', 'inherit' ), true ) ) {
			return;
		}
		$this->delete_cache_for_post_terms( $post );
	}

	public function delete_on_comment_transition( $new_status, $old_status, $comment ) {
		$post = get_post( $comment->comment_post_ID );
		error_log( "Boost_File_Cache::delete_on_comment( $new_status, $old_status, {$comment->comment_post_ID} )" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->delete_cache_for_post( $post );
	}

	public function delete_on_comment_post( $comment_id, $comment_approved, $commentdata ) {
		$post = get_post( $commentdata['comment_post_ID'] );

		if ( $comment_approved !== '1' ) {
			$this->delete_post_for_visitor( $post );
			error_log( 'comment not approved!!' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			return;
		}
		error_log( 'comment was approved' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

		error_log( "Boost_File_Cache::delete_on_comment_post( $comment_id, $comment_approved, {$commentdata['comment_post_ID']} )" ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		$this->delete_cache_for_post( $post );
	}

	abstract public function get();
	abstract public function set( $data );
	abstract public function delete_cache_for_url( $url );
	abstract public function delete_cache_for_post( $post_id );
	abstract public function delete_post_for_visitor( $post );
	abstract public function delete_on_post_transition( $new_status, $old_status, $post );
	abstract public function delete_cache_for_post_terms( $post );
}
