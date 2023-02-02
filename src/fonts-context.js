import { useState, createContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const ManageFontsContext = createContext();

const DEFAULT_HEADING_TEXT = __( "The quick brown fox jumps over the lazy dog.", "create-block-theme" );
const DEFAULT_SENTENCE_TEXT = __( "Incredible as it may seem, I believe that the Aleph of Garay Street was a false Aleph.", "create-block-theme" );
const DEFAULT_PARAGRAPH_TEXT = __(
    "First a glass of pseudo-cognac, he ordered, and then down you dive into the cellar. Let me warn you, you'll have to lie flat on your back. Total darkness, total immobility, and a certain ocular adjustment will also be necessary. From the floor, you must focus your eyes on the nineteenth step. Once I leave you, I'll lower the trapdoor and you'll be quite alone. You needn't fear the rodents very much though I know you will. In a minute or two, you'll see the Aleph, the microcosm of the alchemists and Kabbalists, our true proverbial friend, the multum in parvo!",
    "create-block-theme" )
const DEFAULT_DEMO_TYPE = "sentence";

export const DEMO_DEFAULTS = {
    heading: {
        text: DEFAULT_HEADING_TEXT,
        size: 40,
        lineHeight: 1.1,
        margin: "0.5em",
        component: "h2",
    },
    sentence: {
        text: DEFAULT_SENTENCE_TEXT,
        size: 24,
        lineHeight: 1.3,
        margin: "0.5em",
        component: "p",
    },
    paragraph: {
        text: DEFAULT_PARAGRAPH_TEXT,
        size: 16,
        lineHeight: 1.5,
        margin: "0.5em",
        component: "p",
    },
};

export function ManageFontsProvider( { children } ) {
    const [ demoType, setDemoType ] = useState(
        localStorage.getItem( "cbt_default-demo-type" ) || DEFAULT_DEMO_TYPE
    );

    const [ demoText, setDemoText ] = useState( localStorage.getItem(
        "cbt_default-demo-text" ) || DEMO_DEFAULTS[ demoType ][ "text" ]
    );

    const [ demoFontSize, setDemoFontSize ] = useState(
        parseInt( localStorage.getItem( "cbt_default-demo-font-size" )) || DEMO_DEFAULTS[ demoType ][ "size" ]
    );

    const handleDemoTextChange = ( newDemoText ) => {
        setDemoText( newDemoText );
        localStorage.setItem( "cbt_default-demo-text", newDemoText );
    }

    const handleDemoTypeChange = ( newDemoType ) => {
        setDemoType( newDemoType );
        localStorage.setItem( "cbt_default-demo-type", newDemoType );
        resetDefaults( newDemoType );
    }

    const handleDemoFontSizeChange = ( newDemoFontSize ) => {
        setDemoFontSize( newDemoFontSize );
        localStorage.setItem( "cbt_default-demo-font-size", newDemoFontSize );
    }

    const resetDefaults = ( newDemoType ) => {
        handleDemoTextChange ( DEMO_DEFAULTS[ newDemoType || demoType ][ "text" ] );
        handleDemoFontSizeChange( DEMO_DEFAULTS[ newDemoType || demoType ][ "size" ] );
    }

    return (
        <ManageFontsContext.Provider value={{
            demoText,
            handleDemoTextChange,
            resetDefaults,
            demoType,
            handleDemoTypeChange,
            demoFontSize,
            handleDemoFontSizeChange,
        }}>
            { children }
        </ManageFontsContext.Provider>
    );
}
