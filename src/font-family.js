import { Button } from '@wordpress/components';
import FontFace from "./font-face";

function FontFamily ( { fontFamily, fontFamilyIndex, deleteFontFamily, deleteFontFace, demoText } ) {

    return (
        <table className="wp-list-table widefat table-view-list">
            <thead>
                <td class="font-family-head">
                    <div>Font Family: <strong>{fontFamily.fontFamily}</strong> | Slug: <strong>{fontFamily.slug}</strong></div>
                    <div>
                        <Button
                            variant="tertiary"
                            isDestructive={true}
                            onClick={() => deleteFontFamily(fontFamilyIndex)}
                        >
                            Remove Font Family
                        </Button>
                    </div>
                </td>
            </thead>
            <tbody>
                <div className="font-family-contents">
                    <table className="wp-list-table widefat striped table-view-list">
                        <thead>
                            <td>Style</td>
                            <td>Weight</td>
                            <td>Preview</td>
                            {/* <td>Edit</td> */}
                            <td></td>
                        </thead>
                        <tbody>
                            {fontFamily.fontFace.map((fontFace, i) => (
                                <FontFace
                                    fontFace={fontFace}
                                    fontFamilyIndex={fontFamilyIndex}
                                    fontFaceIndex={i}
                                    demoText={demoText}
                                    key={`fontface${i}`}
                                    deleteFontFace={
                                        () => deleteFontFace(fontFamilyIndex, i)
                                    }                                 
                                />
                            ))}  
                        </tbody>  
                    </table>
                </div>
            </tbody>
        </table>
    )
}

FontFamily.defaultProps = {
    demoText: "The quick brown fox jumps over the lazy dog."
};

export default FontFamily;
