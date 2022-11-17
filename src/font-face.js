import { Button } from '@wordpress/components'; 

const { __ } = wp.i18n;

function FontFace ( {
    fontFamily,
    fontWeight,
    fontStyle,
    demoText,
    deleteFontFace
} ) {
    
    const demoStyles = {
        fontFamily,
        fontStyle,
        // Handle cases like fontWeight is a number instead of a string or when the fontweight is a 'range', a string like "800 900".
        fontWeight: fontWeight ? String(fontWeight).split(' ')[0] : "normal",
    };

    return (
        <tr className="font-face">
            <td>{fontStyle}</td>
            <td>{fontWeight}</td>
            <td className="demo-cell"><p style={ demoStyles }>{demoText}</p></td>
            { deleteFontFace && <td><Button variant="tertiary" isDestructive={true} onClick={deleteFontFace}>{__('Remove')}</Button></td> }
        </tr>
    );
}

FontFace.defaultProps = {
    demoText: __("The quick brown fox jumps over the lazy dog.", "create-block-theme"),
    fontWeight: "normal",
    fontStyle: "normal",
};

export default FontFace;
