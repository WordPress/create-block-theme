import { Button } from '@wordpress/components'; 
import { useContext } from '@wordpress/element';
import { ManageFontsContext } from '../fonts-context';
import Demo from "../demo-text-input/demo";
const { __ } = wp.i18n;

function FontFace ( {
    fontFamily,
    fontWeight,
    fontStyle,
    deleteFontFace,
    shouldBeRemoved,
    isFamilyOpen
} ) {
    const { demoText, handleDemoTextChange, resetDefaults } = useContext( ManageFontsContext );

    const demoStyles = {
        fontFamily,
        fontStyle,
        // Handle cases like fontWeight is a number instead of a string or when the fontweight is a 'range', a string like "800 900".
        fontWeight: fontWeight ? String(fontWeight).split(' ')[0] : "normal",
    };
    
    const handleChange = ( event ) => {
        const newDemoText = event.target.value;
        handleDemoTextChange( newDemoText );
    }

    if ( shouldBeRemoved ) {
        return null;
    }

    return (
        <tr className="font-face">
            <td>{fontStyle}</td>
            <td>{fontWeight}</td>
            <td className="demo-cell">
                {/* <input style={ demoStyles } onChange={ handleChange } value={ demoText }/> */}
                <Demo style={ demoStyles } />
            </td>
            { deleteFontFace && (
                <td>
                    <Button
                        variant="tertiary"
                        onClick={deleteFontFace}
                        tabindex={isFamilyOpen ? 0 : -1}
                    >
                        {__('Remove', 'create-block-theme')}
                    </Button>
                </td>
            )}
        </tr>
    );
}

FontFace.defaultProps = {
    fontWeight: "normal",
    fontStyle: "normal",
};

export default FontFace;
