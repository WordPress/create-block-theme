import { useState } from "react";
import FontFamily from "./font-family";
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';

function ManageFonts () {
    const themeFontsJsonElement = document.querySelector("#theme-fonts-json");
    const manageFontsFormElement = document.querySelector("#manage-fonts-form");
    const themeFontsJsonValue = themeFontsJsonElement.value;
    const themeFontsJson = JSON.parse(themeFontsJsonValue);
    const [newThemeFonts, setNewThemeFonts] =  useState( themeFontsJson );
    const [ showConfirmDialog, setShowConfirmDialog ] = useState( false );
    const [ fontToDelete, setFontToDelete ] = useState( {} );

    function requestDeleteConfirmation(fontFamilyIndex, fontFaceIndex)  {
        setFontToDelete( { fontFamilyIndex, fontFaceIndex },  setShowConfirmDialog(true));
    }

    function confirmDelete() {
        if(
            fontToDelete.fontFamilyIndex !== undefined &&
            fontToDelete.fontFaceIndex !== undefined
        ) {
            deleteFontFace(fontToDelete.fontFamilyIndex, fontToDelete.fontFaceIndex);
        } else {
            deleteFontFamily(fontToDelete.fontFamilyIndex);
        }

        if (
            fontToDelete.fontFamilyIndex !== undefined ||
            fontToDelete.fontFaceIndex !== undefined
        ) {
            setTimeout(() => {
                manageFontsFormElement.submit();
            }, 0);
        }

        setFontToDelete({});
        setShowConfirmDialog(false);
    }

    function cancelDelete () {
        setFontToDelete({});
        setShowConfirmDialog(false);
    }

    function deleteFontFamily (fontFamilyIndex) {
        const updatedFonts = newThemeFonts.filter((_, index) => index !== fontFamilyIndex);
        setNewThemeFonts(updatedFonts);
    }

    function deleteFontFace () {
        const { fontFamilyIndex, fontFaceIndex } = fontToDelete;
        const updatedFonts = newThemeFonts.reduce((acc, fontFamily, index) => {
            if (index === fontFamilyIndex && fontFamily.fontFace.length > 1) {
                const {fontFace, ...updatedFontFamily} = fontFamily;
                updatedFontFamily.fontFace = fontFamily.fontFace.filter((_, index) => index !== fontFaceIndex);
                return [
                    ...acc,
                    updatedFontFamily
                ];
            }

            if (fontFamily.fontFace.length == 1) {
                return acc;
            }

            return [...acc, fontFamily];
        }, []);

        setNewThemeFonts(updatedFonts);
    }

    const fontFamilyToDelete = newThemeFonts[fontToDelete.fontFamilyIndex];
    const fontFaceToDelete = newThemeFonts[fontToDelete.fontFamilyIndex]?.fontFace[fontToDelete.fontFaceIndex];

    return (
        <>
            <button onClick={ () => { console.log(newThemeFonts); manageFontsFormElement.submit(); } }>Update</button>

            <input  type="input" name="new-theme-fonts-json" value={JSON.stringify(newThemeFonts)} />
            <ConfirmDialog
				isOpen={ showConfirmDialog }
				onConfirm={ confirmDelete }
				onCancel={ cancelDelete }
			>
                {(fontToDelete?.fontFamilyIndex !== undefined && fontToDelete?.fontFaceIndex !== undefined )
                    ? <h3>Are you sure you want to delete "{fontFaceToDelete?.fontStyle} - {fontFaceToDelete?.fontWeight}"  variant of "{fontFamilyToDelete?.fontFamily}" from your theme?</h3>
                    : <h3>Are you sure you want to delete "{fontFamilyToDelete?.fontFamily}" from your theme?</h3>
                }
                <p>This action will delete the font definition and the font file assets from your theme.</p>
			</ConfirmDialog>
            <div className="font-families">
                {newThemeFonts.map((fontFamily, i) => (
                    <FontFamily
                        fontFamily={fontFamily}
                        fontFamilyIndex={i}
                        key={`fontfamily${i}`}
                        deleteFontFamily={requestDeleteConfirmation}
                        deleteFontFace={requestDeleteConfirmation}
                    />
                ))}
            </div>
        </>
    );
}

export default ManageFonts;