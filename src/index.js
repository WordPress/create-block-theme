import { render } from '@wordpress/element';
import ManageFonts from './manage-fonts';
 
function App() {
    const params = new URLSearchParams(document.location.search);
    let page = params.get("page");

    switch (page) {
        case "manage-fonts":
            return <ManageFonts />;
        case "add-google-font-to-theme-json":
            return <p>Google Fonts!!!!!!!!!!!!!!!</p>;
        default:
            return <p>Default</p>;
    }
}
 
window.addEventListener(
    'load',
    function () {
        render(
            <App />,
            document.querySelector( '#fonts-app' )
        );
    },
    false
);