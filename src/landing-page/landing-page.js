/**
 * WordPress dependencies
 */
import { useState } from '@wordpress/element';
import {
	Button,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalHStack as HStack,
} from '@wordpress/components';

import {
	CreateThemeModal
} from './create-modal';

export default function LandingPage() {

	const [ createModalType, setCreateModalType ] = useState( false );

	return (

		<VStack alignment="center">
			{ createModalType && (
				<CreateThemeModal
					creationType={ createModalType }
					onRequestClose={ () => setCreateModalType( false ) }
				/>
			) }
			<img src="/wp-content/plugins/create-block-theme/.wordpress-org/banner-772x250.png" alt="Create Block Theme Logo" />

			<VStack alignment="left" style={{ maxWidth: "700px"}}>
			<p>
				Create Block Theme is a tool to help you make Block Themes using the WordPress Editor.
				It adds tools to the Editor to help you create and manage your theme.
				Themes created with Create Block Theme are built using the WordPress Editor and are
				compatible with the Full Site Editing features in WordPress.  Themes created with Create Block Theme
				don't require Create Block Theme to be installed on the site where the theme is used.
			</p>

			<p><strong>What would you like to do?</strong><br/>
			You can do everything from within the Editor but here are a few things you can do to get started.</p>

			<HStack alignment="left">
				<Button variant="secondary">Export this theme as a Zip File.</Button>
				<p>Export a zip file ready to be imported into another WordPress environment.</p>
			</HStack>

			<HStack alignment="left">
				<Button variant="secondary" onClick={()=>setCreateModalType('blank')}>Create a new Blank Theme</Button>
				<p>Start from scratch!  Create a blank theme to get started with your own design ideas.</p>
			</HStack>

			<HStack alignment="left">
				<Button variant="secondary" onClick={()=>setCreateModalType('clone')}>Create a Clone of This Theme</Button><br/>
				<p>Use the currently activated theme as a starting point.</p>
			</HStack>

			<HStack alignment="left">
				<Button variant="secondary" onClick={()=>setCreateModalType('child')}>Create a Child of This Theme</Button>
				<p>Make a theme that uses the currently activated theme as a parent.</p>
			</HStack>

			<p>
				<strong>Do you need some help?</strong><br/>
				Have a question? Ask for some help in the <a href="https://wordpress.org/support/plugin/create-block-theme/">forums</a>.<br/>
				Found a bug? <a href="https://github.com/WordPress/create-block-theme/issues/new">Report it on GitHub</a>.<br/>
				Want to contribute? Check out the <a href="https://github.com/WordPress/create-block-theme">project on GitHub</a>.<br/>
			</p>
			<div>
				<strong>Yes, but how do I...?</strong><br/>
				<details>
					<summary>How do I save the changes I made in the Site Editor to my Theme?</summary>
					<p>
					You click the 'Save' button in the Site Editor.  Seriously, we'll put some more instructions.
					I'm just typing stuff to have stuff typed here right now.
					</p>
				</details>
				<details>
					<summary>How do I convert lead into gold?</summary>
					<p>
					You have to follow the law of equivalent exchange.  For more information please ask the Elric brothers.
					</p>
				</details>
			</div>
			</VStack>
		</VStack>
	);
}
