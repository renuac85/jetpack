<?php
/**
 * Module Name: Blaze
 * Module Description: Grow your audience by promoting your content across Tumblr and WordPress.com.
 * Sort Order: 22
 * Recommendation Order: 12
 * First Introduced: 12.3
 * Requires Connection: Yes
 * Auto Activate: Yes
 * Module Tags: Traffic, Social
 * Additional Search Queries: advertising, ads
 *
 * @package automattic/jetpack
 */

use Automattic\Jetpack\Blaze;

Blaze::init();

// Remove post row Blaze actions in the Jetpack plugin.
add_filter( 'jetpack_blaze_post_row_actions_enable', '__return_false' );
