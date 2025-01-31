import { __, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';
import styles from '../health.module.scss';

const cacheIssuesLink = 'TBD'; // @todo - add proper link here.

const messages: { [ key: string ]: { title: string; message: React.ReactNode } } = {
	'wp-content-not-writable': {
		title: 'wp-content-not-writable',
		message: 'wp-content-not-writable',
	},
	'advanced-cache-incompatible': {
		title: 'advanced-cache-incompatible',
		message: 'advanced-cache-incompatible',
	},
	'unable-to-write-advanced-cache': {
		title: 'unable-to-write-advanced-cache',
		message: 'unable-to-write-advanced-cache',
	},
	'wp-cache-defined-not-true': {
		title: 'wp-cache-defined-not-true',
		message: 'wp-cache-defined-not-true',
	},
	'feature-disabled-advanced-cache-incompatible': {
		title: __( 'Cache loader script already exists', 'jetpack-boost' ),
		message: createInterpolateElement(
			sprintf(
				// translators: %d refers to the path of the cache loader script.
				__(
					`This feature cannot be enabled because <underline>%s</underline> was found on your site. It was created by another plugin, or your hosting provider. Please remove it to use this module. <link>Learn more.</link>`,
					'jetpack-boost'
				),
				'wp-content/advanced-cache.php'
			),
			{
				underline: <span className={ styles.underline } />,
				// eslint-disable-next-line jsx-a11y/anchor-has-content
				link: <a href={ cacheIssuesLink } target="_blank" rel="noopener noreferrer" />,
			}
		),
	},
	'page-cache-root-dir-not-writable': {
		title: __( 'Cache directory is not writable', 'jetpack-boost' ),
		message: createInterpolateElement(
			sprintf(
				// translators: %d refers to the path of the cache directory.
				__(
					`This feature cannot be enabled because the cache directory (<underline>%s</underline>) is not writable. This needs to be resolved before caching can be enabled.`,
					'jetpack-boost'
				),
				'wp-content/boost-cache'
			),
			{
				underline: <span className={ styles.underline } />,
			}
		),
	},
	'wp-config-not-writable': {
		title: __( 'wp-config.php not writable', 'jetpack-boost' ),
		message: createInterpolateElement(
			sprintf(
				// translators: %d refers to the path of wp-config.php.
				__(
					`This feature cannot be enabled because <underline>%s</underline> is not writable. This needs to be resolved before caching can be enabled.`,
					'jetpack-boost'
				),
				'wp-config.php'
			),
			{
				underline: <span className={ styles.underline } />,
			}
		),
	},
	default: {
		title: __( 'Unknown error', 'jetpack-boost' ),
		message: __( 'An unknown error ocurred.', 'jetpack-boost' ),
	},
};

export default ( status: string ) => {
	return messages[ status ] || messages.default;
};
