<?php
/**
 * User Content Tracking Redirects
 *
 * The purpose of this file is to track user generated link clicks on the emails and redirect them to the original URL.
 * These links are generated by WPCOM and sent to the user in the emails.
 *
 * @package automattic/jetpack
 */

add_action( 'init', 'jetpack_user_content_tracking_redirect' );

/**
 * Tracks user generated link clicks on the emails and redirects them to the original URL.
 */
function jetpack_user_content_tracking_redirect() {
	if ( isset( $_GET['action'] ) && 'user_content_redirect' === $_GET['action'] && isset( $_GET['uuid'] ) && isset( $_GET['site_id'] ) && isset( $_GET['post_id'] ) && ( isset( $_GET['user_id'] ) || isset( $_GET['subscriber_id'] ) ) ) {
		$uuid          = sanitize_text_field( wp_unslash( $_GET['uuid'] ) );
		$post_id       = sanitize_text_field( wp_unslash( $_GET['post_id'] ) );
		$user_id       = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : null;
		$signature     = sanitize_text_field( wp_unslash( $_GET['post_id'] ) );
		$site_id       = get_current_blog_id();
		$subscriber_id = isset( $_GET['subscriber_id'] ) ?
			sanitize_text_field( wp_unslash( $_GET['subscriber_id'] ) ) :
			null;

		// Check the signature, if something wrong, redirect to the domain
		if ( ! check_signature( $signature, $post_id, $user_id, $subscriber_id ) ) {
			wp_safe_redirect( get_site_url() );
			exit;
		}

		// Grab the original URL from the database. The table has this structure:
		global $wpdb;
		$original_url = $wpdb->get_results(
			$wpdb->prepare( 'SELECT url, email_link_mapping_id FROM email_link_mapping WHERE uuid = %s', $uuid )
		);

		// Store in the click database the information about the click. This is the structure:
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO email_link_stats (subscriber_id, user_id, post_id, blog_id, email_link_mapping_id) VALUES (%d, %d, %d, %d, %d) ON DUPLICATE KEY UPDATE total_clicks = total_clicks + 1',
				$subscriber_id,
				$user_id,
				$post_id,
				$site_id,
				$uuid
			)
		);

		// Redirect the user to the original URL.
		wp_safe_redirect( $original_url );
		exit;
	}
}

/**
 * Check the signature of the link
 *
 * @param string $signature         The signature of the link.
 * @param int    $post_id           The post ID.
 * @param int    $user_id           The user ID.
 * @param int    $subscriber_id The subscriber ID.
 *
 * @return bool
 */
function check_signature( $signature, $post_id, $user_id, $subscriber_id ) {
	$salt = get_option( 'email_link_salt' );

	if ( false !== $salt ) {
		$signature_to_check = md5( $post_id . $user_id . $subscriber_id . $salt );

		return $signature_to_check === $signature;
	} else {
		return false;
	}
}
