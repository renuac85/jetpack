<?php

namespace Automattic\Jetpack_Boost\Modules\Lazy_Images;

use Automattic\Jetpack\Jetpack_Lazy_Images;
use Automattic\Jetpack_Boost\Contracts\Feature;

class Lazy_Images implements Feature {

	public function initialize() {
		add_action( 'wp', array( Jetpack_Lazy_Images::class, 'instance' ) );
	}

	public function get_slug() {
		return 'lazy-images';
	}
}
