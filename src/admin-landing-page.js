import { render, createRoot } from '@wordpress/element';
import './index.scss';

function App() {

	return (
		<>Landing Page Goes Here.</>
	);
}

window.addEventListener(
	'load',
	function () {
		const domNode = document.getElementById( 'create-block-theme-app' );

		// If version is less than 18 use `render` to render the app
		// otherwise use `createRoot` to render the app
		if ( createRoot === undefined ) {
			render( <App />, domNode );
		} else {
			const root = createRoot( domNode );
			root.render( <App /> );
		}
	},
	false
);
