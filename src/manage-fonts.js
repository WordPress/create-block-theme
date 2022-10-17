import { useState, useEffect } from "react";
import FontFamily from "./font-family";
import { __experimentalConfirmDialog as ConfirmDialog } from '@wordpress/components';

function ManageFonts () {
    // The element where the list of theme fonts is rendered coming from the server as JSON
    const themeFontsJsonElement = document.querySelector("#theme-fonts-json");

    // The form element that will be submitted to the server
    const manageFontsFormElement = document.querySelector("#manage-fonts-form");

    // The theme font list coming from the server as JSON
    const themeFontsJsonValue = themeFontsJsonElement.value;
    const themeFontsJson = JSON.parse(themeFontsJsonValue);

    // The client-side theme font list is initizaliased with the server-side theme font list
    const [newThemeFonts, setNewThemeFonts] =  useState( themeFontsJson );

    // Object where we store the font family or font face index position in the newThemeFonts array that is about to be removed
    const [ fontToDelete, setFontToDelete ] = useState( { fontFamilyIndex: undefined, fontFaceIndex : undefined } );

    // Confirm dialog state
    const [ showConfirmDialog, setShowConfirmDialog ] = useState( false );


    // When client side font list changes, we update the server side font list
    useEffect( () => {
        // Avoids running this effect on the first render
        if ( 
            fontToDelete.fontFamilyIndex !== undefined ||
            fontToDelete.fontFaceIndex !== undefined
        ) {
            // Submit the form to the server
            manageFontsFormElement.submit();
        }
    }, [newThemeFonts] );

    function requestDeleteConfirmation(fontFamilyIndex, fontFaceIndex)  {
        setFontToDelete( { fontFamilyIndex, fontFaceIndex },  setShowConfirmDialog(true));
    }

    function confirmDelete() {
        // if fontFaceIndex is undefined, we are deleting a font family
        if(
            fontToDelete.fontFamilyIndex !== undefined &&
            fontToDelete.fontFaceIndex !== undefined
        ) {
            deleteFontFace(fontToDelete.fontFamilyIndex, fontToDelete.fontFaceIndex);
        } else {
            deleteFontFamily(fontToDelete.fontFamilyIndex);
        }
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

            if (fontFamily.fontFace.length == 1 && index === fontFamilyIndex) {
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
            <input type="hidden" name="new-theme-fonts-json" value={JSON.stringify(newThemeFonts)} />
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