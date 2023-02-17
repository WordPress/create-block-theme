import { useState } from 'react';
import { Button, Icon } from '@wordpress/components';
import FontFace from "./font-face";

const { __, _n } = wp.i18n;
function FontFamily ( { fontFamily, fontFamilyIndex, deleteFontFamily, deleteFontFace } ) {

    const [isOpen, setIsOpen] = useState(false);

    const toggleIsOpen = () => {
        setIsOpen(!isOpen);
    }

    const hasFontFaces = !!fontFamily.fontFace && !!fontFamily.fontFace.length;

    if ( fontFamily.shouldBeRemoved ) {
        return null;
    }

    return (
        <table className="wp-list-table widefat table-view-list">
            <thead onClick={toggleIsOpen}>
                <tr>
                    <td className="font-family-head">
                        <div>
                            <strong>{fontFamily.name || fontFamily.fontFamily}</strong>
                            { hasFontFaces &&
                                <span className="variants-count"> ( { fontFamily.fontFace.length } { _n( "Variant", "Variants",  fontFamily.fontFace.length, "create-block-theme" ) } )</span>
                            }
                        </div>
                        <div>
                            <Button
                                variant="tertiary"
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
                </tr>
            </thead>
            <tbody className="font-family-contents">
                <tr className="container">
                    <td className={` slide ${isOpen ? "open" : "close"}`}>
                        <table className="wp-list-table widefat striped table-view-list">
                            <thead>
                                <tr>
                                    <td>{__('Style', 'create-block-theme')}</td>
                                    <td>{__('Weight', 'create-block-theme')}</td>
                                    <td className="preview-head">{__('Preview', 'create-block-theme')}</td>
                                    { hasFontFaces && <td></td> }
                                </tr>
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
                    </td>
                </tr>
            </tbody>
        </table>
    )
}

export default FontFamily;
