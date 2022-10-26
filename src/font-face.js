import { Button } from '@wordpress/components'; 

const { __ } = wp.i18n;

function FontFace ( { fontFace, demoText, deleteFontFace } ) {

    // Handle cases like fontWeight is a number instead of a string or when the fontweight is a 'range', a string like "800 900".
    const fontWeight = fontFace.fontWeight ? String(fontFace.fontWeight).split(' ')[0] : "normal";

    const demoStyles = {
        fontFamily: fontFace.fontFamily,
        fontStyle: fontFace.fontStyle,
        fontWeight: fontWeight,
    };

    return (
        <tr className="font-face">
            <td>{fontFace.fontStyle}</td>
            <td>{fontFace.fontWeight}</td>
            <td className="demo-cell"><p style={ demoStyles }>{demoText}</p></td>
            <td><Button variant="tertiary" isDestructive={true} onClick={deleteFontFace}>{__('Remove')}</Button></td>
        </tr>
    );
}

export default FontFace;
