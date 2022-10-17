import { Button, IconButton, Icon } from '@wordpress/components';
import FontFace from "./font-face";

const { __ } = wp.i18n;
function FontFamily ( { fontFamily, fontFamilyIndex, deleteFontFamily, deleteFontFace, demoText } ) {

    return (
        <table className="wp-list-table widefat table-view-list">
            <thead>
                <td class="font-family-head">
                    <div><strong>{fontFamily.fontFamily}</strong></div>
                    <div>
                        <Button
                            variant="tertiary"
                            isDestructive={true}
                            onClick={() => deleteFontFamily(fontFamilyIndex)}
                        >
                            {__('Remove Font Family')}
                        </Button>
                        <IconButton icon="arrow-down-alt2" />
                    </div>
                </td>
            </thead>
            <tbody>
                <div className="font-family-contents">
                    <table className="wp-list-table widefat striped table-view-list">
                        <thead>
                            <td>{__('Style')}</td>
                            <td>{__('Weight')}</td>
                            <td>{__('Preview')}</td>
                            {/* <td>{__('Edit')}</td> */}
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
    demoText: __("The quick brown fox jumps over the lazy dog."),
};

export default FontFamily;
