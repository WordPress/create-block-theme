import { Button } from '@wordpress/components'; 
import { useContext } from '@wordpress/element';
import { ManageFontsContext } from '../fonts-context';
const { __ } = wp.i18n;

function FontFace ( {
    fontFamily,
    fontWeight,
    fontStyle,
    deleteFontFace,
    shouldBeRemoved
} ) {
    const { demoText, handleDemoTextChange, resetDemoText } = useContext( ManageFontsContext );

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

    const onBlur = ( event ) => {
        if ( ! event.target.value ) {
            resetDemoText();
        }
    }

    if ( shouldBeRemoved ) {
        return null;
    }

    return (
        <tr className="font-face">
            <td>{fontStyle}</td>
            <td>{fontWeight}</td>
            <td className="demo-cell">
                <input style={ demoStyles } onChange={ handleChange } value={ demoText } onBlur={ onBlur }/>
            </td>
            { deleteFontFace && <td><Button variant="tertiary" isDestructive={true} onClick={deleteFontFace}>{__('Remove', 'create-block-theme')}</Button></td> }
        </tr>
    );
}

FontFace.defaultProps = {
    fontWeight: "normal",
    fontStyle: "normal",
};

export default FontFace;
