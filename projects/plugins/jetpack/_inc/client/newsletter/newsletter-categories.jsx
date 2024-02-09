import { ToggleControl, getRedirectUrl } from '@automattic/jetpack-components';
import { __ } from '@wordpress/i18n';
import SettingsCard from 'components/settings-card';
import SettingsGroup from 'components/settings-group';
import React, { useCallback } from 'react';
import { connect } from 'react-redux';
import { withModuleSettingsFormHelpers } from '../components/module-settings/with-module-settings-form-helpers';

/**
 * NewsletterCategories settings component.
 *
 * @param {object} props - Component props.
 * @returns {React.Component} Subscription settings component.
 */
function NewsletterCategories( props ) {
	const { updateFormStateModuleOption, isNewsletterCategoriesEnabled } = props;

	const handleEnagleNewsletterCategoriesToggleChange = useCallback( () => {
		updateFormStateModuleOption( 'subscriptions', 'wpcom_newsletter_categories_enabled' );
	}, [ updateFormStateModuleOption ] );

	return (
		<SettingsCard { ...props } hideButton module="subscriptions">
			<SettingsGroup
				hasChild
				disableInOfflineMode
				disableInSiteConnectionMode
				// module={ subscriptions }
				support={ {
					text: __(
						'Allows readers to subscribe to your posts or comments, and receive notifications of new content by email.',
						'jetpack'
					),
					link: getRedirectUrl( 'jetpack-support-subscriptions' ),
				} }
			>
				<ToggleControl
					checked={ isNewsletterCategoriesEnabled }
					onChange={ handleEnagleNewsletterCategoriesToggleChange }
					label={ __( 'Enable newsletter categories', 'jetpack' ) }
				/>
			</SettingsGroup>
		</SettingsCard>
	);
}

export default withModuleSettingsFormHelpers(
	connect( ( state, ownProps ) => {
		return {
			isNewsletterCategoriesEnabled: ownProps.getOptionValue(
				'wpcom_newsletter_categories_enabled'
			),
		};
	} )( NewsletterCategories )
);
