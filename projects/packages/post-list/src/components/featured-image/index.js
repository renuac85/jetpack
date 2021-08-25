/**
 * External dependencies
 */
import { Tooltip } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function FeaturedImage( { url, thumb } ) {
	if ( ! url ) {
		return (
			<Tooltip
				text={ __( 'No featured image set.', 'jetpack-post-list' ) }
				position="top"
				className="jetpack-post-list__featured-image-tooltip"
			>
				<div className="post-list__post-featured-image">
					<img
						alt="Placeholder"
						src="data:image/png;base64,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"
						width="50px"
						height="50px"
					/>
				</div>
			</Tooltip>
		);
	}

	return (
		// TODO: Pass the right alt text to the client.
		<img
			alt=""
			className="post-list__post-featured-image"
			src={ thumb }
			width="50px"
			height="50px"
		/>
	);
}
