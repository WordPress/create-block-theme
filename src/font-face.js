import { Button } from '@wordpress/components'; 

const { __ } = wp.i18n;

function FontFace ( { fontFamily, fontWeight, fontStyle, demoText, deleteFontFace } ) {

    // Handle cases like fontWeight is a number instead of a string or when the fontweight is a 'range', a string like "800 900".
    const demoStyles = {
        fontFamily,
        fontStyle,
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

export default FontFace;
