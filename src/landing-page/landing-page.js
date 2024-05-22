/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useState, createInterpolateElement } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	Button,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { fetchThemeStyleData, downloadExportedTheme } from '../resolvers';
import { downloadFile } from '../utils';
import { CreateThemeModal } from './create-modal';

export default function LandingPage() {
	const [ themeStyleData, setThemeStyleData ] = useState( '' );
	const [ createModalType, setCreateModalType ] = useState( false );

	useSelect( async () => {
		setThemeStyleData( await fetchThemeStyleData() );
	}, [] );

	const handleExportClick = async () => {
		const response = await downloadExportedTheme();
		downloadFile( response );
	};

	return (
		<div className="create-block-theme landing-page">
			{ createModalType && (
				<CreateThemeModal
					creationType={ createModalType }
					onRequestClose={ () => setCreateModalType( false ) }
				/>
			) }

			<div className="cbt-lp-header">
				<img
					src="/wp-content/plugins/create-block-theme/assets/header_logo.webp"
					alt="Create Block Theme Logo"
				/>
			</div>

			<HStack alignment="topLeft" className="cbt-lp-body">
				<VStack alignment="left" className="cbt-lp-left-column">
					<h1>
						{ __(
							'What would you like to do?',
							'create-block-theme'
						) }
					</h1>
					<p>
						{ createInterpolateElement(
							__(
								'You can do everything from within the <a>Editor</a> but here are a few things you can do to get started.',
								'create-block-theme'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a href="/wp-admin/site-editor.php?canvas=edit" />
								),
							}
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => handleExportClick() }
					>
						{ sprintf(
							// translators: %s: theme name.
							__(
								'Export "%s" as a Zip File',
								'create-block-theme'
							),
							themeStyleData?.name
						) }
					</Button>
					<p>
						{ __(
							'Export a zip file ready to be imported into another WordPress environment.',
							'create-block-theme'
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => setCreateModalType( 'blank' ) }
					>
						{ __(
							'Create a new Blank Theme',
							'create-block-theme'
						) }
					</Button>
					<p>
						{ __(
							'Start from scratch! Create a blank theme to get started with your own design ideas.',
							'create-block-theme'
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => setCreateModalType( 'clone' ) }
					>
						{ sprintf(
							// translators: %s: theme name.
							__(
								'Create a Clone of "%s"',
								'create-block-theme'
							),
							themeStyleData?.name
						) }
					</Button>
					<p>
						{ __(
							'Use the currently activated theme as a starting point.',
							'create-block-theme'
						) }
					</p>
					<Button
						variant="link"
						onClick={ () => setCreateModalType( 'child' ) }
					>
						{ sprintf(
							// translators: %s: theme name.
							__(
								'Create a Child of "%s"',
								'create-block-theme'
							),
							themeStyleData?.name
						) }
					</Button>
					<p>
						{ __(
							'Make a theme that uses the currently activated theme as a parent.',
							'create-block-theme'
						) }
					</p>
				</VStack>
				<VStack className="cbt-lp-right-column">
					<h4>{ __( 'About the Plugin', 'create-block-theme' ) }</h4>
					<p>
						{ __(
							"Create Block Theme is a tool to help you make Block Themes using the WordPress Editor. It does this by adding tools to the Editor to help you create and manage your theme. Themes created with Create Block Theme don't require Create Block Theme to be installed on the site where the theme is used.",
							'create-block-theme'
						) }
					</p>
					<h4>
						{ __( 'Do you need some help?', 'create-block-theme' ) }
					</h4>
					<p>
						{ createInterpolateElement(
							__(
								'Have a question? Ask for some help in the <a>forums</a>.',
								'create-block-theme'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a href="https://wordpress.org/support/plugin/create-block-theme/" />
								),
							}
						) }
					</p>
					<p>
						{ createInterpolateElement(
							__(
								'Found a bug? Report it on <a>GitHub</a>.',
								'create-block-theme'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a href="https://github.com/WordPress/create-block-theme/issues/new" />
								),
							}
						) }
					</p>
					<p>
						{ createInterpolateElement(
							__(
								'Want to contribute? Check out the <a>project on GitHub</a>.',
								'create-block-theme'
							),
							{
								a: (
									// eslint-disable-next-line jsx-a11y/anchor-has-content
									<a href="https://github.com/WordPress/create-block-theme" />
								),
							}
						) }
					</p>
					<div className="cbt-lp-faq">
						<h4>{ __( 'FAQ', 'create-block-theme' ) }</h4>
						<details>
							<summary>
								{ __(
									'How do I access the features of Create Block Theme from within the editor?',
									'create-block-theme'
								) }
							</summary>
							<p>
								{ __(
									'There is a new panel accessible from the WordPress Editor which you can open by clicking on a new icon to the right of the “Save” button, at the top of the Editor.',
									'create-block-theme'
								) }
							</p>
							<img
								src="/wp-content/plugins/create-block-theme/assets/faq_icon.webp"
								alt="A screenshot of the Create Block Theme icon in the editor"
							/>
						</details>
						<details>
							<summary>
								{ __(
									'How do I save the customizations I made with the Editor to the Theme?',
									'create-block-theme'
								) }
							</summary>
							<p>
								{ __(
									'In the Create Block Theme Panel click "Save Changes to Theme". You will be presented with a number of options of which things you want to be saved to your theme. Make your choices and then click "Save Changes".',
									'create-block-theme'
								) }
							</p>
							<img
								src="/wp-content/plugins/create-block-theme/assets/faq_save.webp"
								alt="A screenshot of the Create Block Theme save changes panel"
							/>
						</details>
						<details>
							<summary>
								{ __(
									'How do I install and remove fonts?',
									'create-block-theme'
								) }
							</summary>
							<p>
								{ __(
									'First Install and activate a font from any source using the WordPress Font Library. Then, using the Create Block Theme Panel select “Save Changes To Theme” and select “Save Fonts” before saving the theme. All of the active fonts will be activated in the theme and deactivated in the system (and may be safely deleted from the system). Any fonts that are installed in the theme that have been deactivated with the WordPress Font Library will be removed from the theme.',
									'create-block-theme'
								) }
							</p>
							<img
								src="/wp-content/plugins/create-block-theme/assets/faq_fonts.webp"
								alt="A screenshot of the WordPress Font Library modal window"
							/>
						</details>
					</div>
				</VStack>
			</HStack>
		</div>
	);
}
