import { useState } from 'react';
import { Button, IconButton } from '@wordpress/components';
import FontFace from "./font-face";

const { __ } = wp.i18n;
function FontFamily ( { fontFamily, fontFamilyIndex, deleteFontFamily, deleteFontFace, demoText } ) {

    const [isOpen, setIsOpen] = useState(true);

    const toggleIsOpen = () => {
        setIsOpen(!isOpen);
    }

    return (
        <table className="wp-list-table widefat table-view-list">
            <thead onClick={toggleIsOpen}>
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
                        <IconButton icon={isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2'} onClick={toggleIsOpen} />
                    </div>
                </td>
            </thead>
            <tbody className="font-family-contents">
                <div className="container">
                    <div className={` slide ${isOpen ? "open" : "close"}`}>
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
                </div>
            </tbody>
        </table>
    )
}

FontFamily.defaultProps = {
    demoText: __("The quick brown fox jumps over the lazy dog."),
};

export default FontFamily;
