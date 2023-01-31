import { render } from '@wordpress/element';
import ManageFonts from './manage-fonts';
import GoogleFonts from './google-fonts';
import { ManageFontsProvider } from "./fonts-context";
import VersionControl from './version-control';
 
function App() {
    const params = new URLSearchParams(document.location.search);
    let page = params.get("page");

    let PageComponent = null;
    switch (page) {
        case "manage-fonts":
            PageComponent = ManageFonts;
            break;
        case "add-google-font-to-theme-json":
            PageComponent = GoogleFonts;
            break;
        case "version-control":
            PageComponent = VersionControl;
            break;
        default:
            PageComponent = () => ( <h1>This page is not implemented yet.</h1> );
            break;
    }

    return (
        <ManageFontsProvider>
            <PageComponent />
        </ManageFontsProvider>
    );
}
 
window.addEventListener(
    'load',
    function () {
        render(
            <App />,
<<<<<<< HEAD
            document.querySelector( '#fonts-app' )
=======
            document.querySelector( '#app-container' )
>>>>>>> a22f375 (Get themes and display them in a submit theme panel.)
        );
    },
    false
);