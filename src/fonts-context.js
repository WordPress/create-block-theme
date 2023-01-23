import { useState, createContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const ManageFontsContext = createContext();
export const DEFAULT_DEMO_TEXT = __( "The quick brown fox jumps over the lazy dog.", "create-block-theme" );


export function ManageFontsProvider( { children } ) {
    const [ demoText, setDemoText ] = useState( localStorage.getItem( "create-block-theme_default-demo-text" ) || DEFAULT_DEMO_TEXT );

    const handleDemoTextChange = ( newDemoText ) => {
        setDemoText( newDemoText );
        localStorage.setItem( "create-block-theme_default-demo-text", newDemoText );
    }

    const resetDemoText = () => {
        setDemoText( DEFAULT_DEMO_TEXT );
    }

    return (
        <ManageFontsContext.Provider value={{ demoText, handleDemoTextChange, resetDemoText }}>
            { children }
        </ManageFontsContext.Provider>
    );
}
