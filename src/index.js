import { createRoot } from '@wordpress/element';
import ManageFonts from './manage-fonts';
import GoogleFonts from './google-fonts';
import LocalFonts from './local-fonts';
import { ManageFontsProvider } from './fonts-context';
import CreateBlockTheme from './create-theme';

function App( { metadata } ) {
	const params = new URLSearchParams( document.location.search );
	const page = params.get( 'page' );

	let PageComponent = null;
	switch ( page ) {
		case 'create-block-theme':
			PageComponent = CreateBlockTheme;
			break;
		case 'manage-fonts':
			PageComponent = ManageFonts;
			break;
		case 'add-google-font-to-theme-json':
			PageComponent = GoogleFonts;
			break;
		case 'add-local-font-to-theme-json':
			PageComponent = LocalFonts;
			break;
		default:
			PageComponent = () => <h1>This page is not implemented yet.</h1>;
			break;
	}

	return (
		<ManageFontsProvider>
			<PageComponent metadata={ metadata } />
		</ManageFontsProvider>
	);
}

window.addEventListener(
	'load',
	function () {
		const rootElement = document.getElementById( 'create-block-theme-app' );
		rootElement.style = 'height: calc(100vh - 32px)';
		const metadata = getAppMetadata( rootElement );
		const root = createRoot( rootElement );
		root.render( <App metadata={ metadata } /> );
	},
	false
);

function getAppMetadata( rootElement ) {
	try {
		return JSON.parse( rootElement.getAttribute( 'data-metadata' ) );
	} catch ( error ) {
		return {};
	}
}
