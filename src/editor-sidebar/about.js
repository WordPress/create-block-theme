/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalDivider as Divider,
	PanelBody,
	ExternalLink,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import ScreenHeader from './screen-header';

function AboutPlugin() {
	return (
		<PanelBody>
			<ScreenHeader
				title={ __( 'About the plugin', 'create-block-theme' ) }
			/>
			<VStack>
				<Text>
					{ __(
						'Create Block Theme is a tool to help you make Block Themes using the WordPress Editor. It does this by adding tools to the Editor to help you create and manage your theme.',
						'create-block-theme'
					) }
				</Text>

				<Text>
					{ __(
						"Themes created with Create Block Theme don't require Create Block Theme to be installed on the site where the theme is used.",
						'create-block-theme'
					) }
				</Text>

				<Divider />

				<Text weight="bold">
					{ __( 'Help', 'create-block-theme' ) }
				</Text>

				<Text>
					<>
						{ __( 'Have a question?', 'create-block-theme' ) }
						<br />
						<ExternalLink href="https://wordpress.org/support/plugin/create-block-theme/">
							{ __( 'Ask in the forums.', 'create-block-theme' ) }
						</ExternalLink>
					</>
				</Text>

				<Text>
					<>
						{ __( 'Found a bug?', 'create-block-theme' ) }
						<br />
						<ExternalLink href="https://github.com/WordPress/create-block-theme/issues">
							{ __(
								'Report it on GitHub.',
								'create-block-theme'
							) }
						</ExternalLink>
					</>
				</Text>

				<Text>
					<>
						{ __( 'Want to contribute?', 'create-block-theme' ) }
						<br />
						<ExternalLink href="https://github.com/WordPress/create-block-theme">
							{ __(
								'Check out the project on GitHub.',
								'create-block-theme'
							) }
						</ExternalLink>
					</>
				</Text>
			</VStack>
		</PanelBody>
	);
}

export default AboutPlugin;
