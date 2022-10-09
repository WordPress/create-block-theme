import { Button } from '@wordpress/components'; 

function FontFace ( { fontFace, demoText } ) {
    const demoStyles = {
        fontFamily: fontFace.fontFamily,
        fontStyle: fontFace.fontStyle,
        fontWeight: fontFace.fontWeight,
    };

    return (
        <tr className="font-face">
            <td>{fontFace.fontStyle}</td>
            <td>{fontFace.fontWeight}</td>
            <td className="demo-cell"><p style={ demoStyles }>{demoText}</p></td>
            <td><Button variant="secondary">Edit</Button></td>
            <td><Button variant="tertiary" isDestructive={true}>Remove</Button></td>
        </tr>
    );
}

export default FontFace;
