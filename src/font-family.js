import { useState } from 'react';
import { Button, Icon } from '@wordpress/components';
import FontFace from "./font-face";

const { __ } = wp.i18n;
function FontFamily ( { fontFamily, fontFamilyIndex, deleteFontFamily, deleteFontFace } ) {

    const [isOpen, setIsOpen] = useState(true);

    const toggleIsOpen = () => {
        setIsOpen(!isOpen);
    }

    const hasFontFaces = !!fontFamily.fontFace && !!fontFamily.fontFace.length;

    if ( fontFamily.shouldBeRemoved ) {
        return null;
    }

    return (
        <table className="wp-list-table widefat table-view-list">
            <thead>
                <td class="font-family-head">
                    <div><strong>{fontFamily.name || fontFamily.fontFamily}</strong></div>
                    <div>
                        <Button
                            variant="tertiary"
                            isDestructive={true}
                            onClick={(e) => {
                                e.stopPropagation();
                                deleteFontFamily(fontFamilyIndex)
                            }}
                        >
                            {__('Remove Font Family', 'create-block-theme')}
                        </Button>
                        <Button onClick={toggleIsOpen}>
                            <Icon icon={isOpen ? 'arrow-up-alt2' : 'arrow-down-alt2'} />
                        </Button>
                    </div>
                </td>
            </thead>
            <tbody className="font-family-contents">
                <div className="container">
                    <div className={` slide ${isOpen ? "open" : "close"}`}>
                        <table className="wp-list-table widefat striped table-view-list">
                            <thead>
                                <td>{__('Style', 'create-block-theme')}</td>
                                <td>{__('Weight', 'create-block-theme')}</td>
                                <td class="preview-head">{__('Preview', 'create-block-theme')}</td>
                                { hasFontFaces && <td></td> }
                            </thead>
                            <tbody>
                                { hasFontFaces && fontFamily.fontFace.map((fontFace, i) => (
                                    <FontFace
                                        { ...fontFace }
                                        fontFamilyIndex={fontFamilyIndex}
                                        fontFaceIndex={i}
                                        key={`fontface${i}`}
                                        deleteFontFace={
                                            () => deleteFontFace(fontFamilyIndex, i)
                                        }
                                    />
                                )) }
                                {
                                    ! hasFontFaces && fontFamily.fontFamily &&
                                    <FontFace
                                        { ...fontFamily }
                                    />
                                }
                            </tbody>
                        </table>
                    </div>
                </div>
            </tbody>
        </table>
    )
}

export default FontFamily;