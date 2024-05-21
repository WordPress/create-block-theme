/**
 * WordPress dependencies
 */
import { sprintf, __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import {
	Button,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
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

			<div
				style={ {
					width: '100%',
					backgroundColor: '#2D59F2',
					marginLeft: '-20px',
					paddingRight: '20px',
				} }
			>
				<img
					src="/wp-content/plugins/create-block-theme/assets/header_logo.png"
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
						{ __(
							'You can do everything from within the Editor but here are a few things you can do to get started.',
							'create-block-theme'
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
							"Create Block Theme is a tool to help you make Block Themes using the WordPress Editor. It adds tools to the Editor to help you create and manage your theme. Themes created with Create Block Theme are built using the WordPress Editor and are compatible with the Full Site Editing features in WordPress. Themes created with Create Block Theme don't require Create Block Theme to be installed on the site where the theme is used.",
							'create-block-theme'
						) }
					</p>
					<h4>
						{ __( 'Do you need some help?', 'create-block-theme' ) }
					</h4>
					<p>
						Have a question? Ask for some help in the{ ' ' }
						<a href="https://wordpress.org/support/plugin/create-block-theme/">
							forums
						</a>
						.<br />
						Found a bug?{ ' ' }
						<a href="https://github.com/WordPress/create-block-theme/issues/new">
							Report it on GitHub
						</a>
						.<br />
						Want to contribute? Check out the{ ' ' }
						<a href="https://github.com/WordPress/create-block-theme">
							project on GitHub
						</a>
						.<br />
					</p>
					<div>
						<h4>{ __( "FAQ's", 'create-block-theme' ) }</h4>
						<details>
							<summary>
								How do I save the changes I made in the Site
								Editor to my Theme?
							</summary>
							<p>
								You click the Save button in the Site Editor.
								Seriously, we will put some more instructions. I
								am just typing stuff to have stuff typed here
								right now.
							</p>
						</details>
						<details>
							<summary>How do I convert lead into gold?</summary>
							<p>
								You have to follow the law of equivalent
								exchange. For more information please ask the
								Elric brothers.
							</p>
						</details>
					</div>
				</VStack>
			</HStack>
		</div>
	);
}
