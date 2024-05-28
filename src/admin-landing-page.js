/**
 * WordPress dependencies
 */
import { createRoot } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './admin-landing-page.scss';
import LandingPage from './landing-page/landing-page';

function App() {
	return <LandingPage />;
}

window.addEventListener(
	'load',
	function () {
		const domNode = document.getElementById( 'create-block-theme-app' );
		const root = createRoot( domNode );
		root.render( <App /> );
	},
	false
);
