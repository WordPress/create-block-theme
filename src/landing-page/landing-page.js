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

		<>
			{ createModalType && (
				<CreateThemeModal
					creationType={ createModalType }
					onRequestClose={ () => setCreateModalType( false ) }
				/>
			) }

		<div style={{width:"100%", backgroundColor: "#2D59F2", marginLeft: "-20px", paddingRight: "20px" }}>
			<img src="/wp-content/plugins/create-block-theme/assets/header_logo.png" alt="Create Block Theme Logo" />
		</div>

		<HStack alignment="topLeft" style={{paddingTop: "40px"}}>

			<VStack alignment="left" style={{ maxWidth: "620px", padding: "0 40px"}}>
				<h1>What would you like to do?</h1>
				<p>You can do everything from within the Editor but here are a few things you can do to get started.</p>


				<Button variant="link" style={{ fontSize:"22px"}}>Export this theme as a Zip File.</Button>
				<p>Export a zip file ready to be imported into another WordPress environment.</p>

				<Button variant="link" style={{ fontSize:"22px"}} onClick={()=>setCreateModalType('blank')}>Create a new Blank Theme</Button>
				<p>Start from scratch!  Create a blank theme to get started with your own design ideas.</p>

				<Button variant="link" style={{ fontSize:"22px"}} onClick={()=>setCreateModalType('clone')}>Create a Clone of This Theme</Button><br/>
				<p>Use the currently activated theme as a starting point.</p>

				<Button variant="link" style={{ fontSize:"22px"}} onClick={()=>setCreateModalType('child')}>Create a Child of This Theme</Button>
				<p>Make a theme that uses the currently activated theme as a parent.</p>

			</VStack>
			<VStack style={{width:"330px"}}>
				<h4>About the Plugin</h4>
			<p>
				Create Block Theme is a tool to help you make Block Themes using the WordPress Editor.
				It adds tools to the Editor to help you create and manage your theme.
				Themes created with Create Block Theme are built using the WordPress Editor and are
				compatible with the Full Site Editing features in WordPress.  Themes created with Create Block Theme
				don't require Create Block Theme to be installed on the site where the theme is used.
			</p>
				<h4>Do you need some help?</h4>
			<p>
				Have a question? Ask for some help in the <a href="https://wordpress.org/support/plugin/create-block-theme/">forums</a>.<br/>
				Found a bug? <a href="https://github.com/WordPress/create-block-theme/issues/new">Report it on GitHub</a>.<br/>
				Want to contribute? Check out the <a href="https://github.com/WordPress/create-block-theme">project on GitHub</a>.<br/>
			</p>
			<div>
				<h4>FAQ's</h4>
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
		</HStack>
</>
	);
}
