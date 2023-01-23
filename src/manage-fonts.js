import { useState, useEffect } from "react";
import FontFamily from "./font-family";
import { __experimentalConfirmDialog as ConfirmDialog, Modal, Icon, Button } from '@wordpress/components';
import { ManageFontsProvider } from "./fonts-context";

const { __ } = wp.i18n;

function ManageFonts () {
    // The element where the list of theme fonts is rendered coming from the server as JSON
    const themeFontsJsonElement = document.querySelector("#theme-fonts-json");

    // The form element that will be submitted to the server
    const manageFontsFormElement = document.querySelector("#manage-fonts-form");

    // The theme font list coming from the server as JSON
    const themeFontsJsonValue = themeFontsJsonElement.innerHTML;

    const themeFontsJson = JSON.parse(themeFontsJsonValue);

    // The client-side theme font list is initizaliased with the server-side theme font list
    const [newThemeFonts, setNewThemeFonts] =  useState( themeFontsJson );

    // Object where we store the font family or font face index position in the newThemeFonts array that is about to be removed
    const [ fontToDelete, setFontToDelete ] = useState( { fontFamilyIndex: undefined, fontFaceIndex : undefined } );

    // dialogs states
    const [ showConfirmDialog, setShowConfirmDialog ] = useState( false );
    const [ isHelpOpen, setIsHelpOpen ] = useState( false );

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

    const toggleIsHelpOpen = () => {
        setIsHelpOpen( !isHelpOpen );
    }

    function requestDeleteConfirmation(fontFamilyIndex, fontFaceIndex)  {
        setFontToDelete( { fontFamilyIndex, fontFaceIndex },  setShowConfirmDialog(true));
    }

    function confirmDelete() {
        setShowConfirmDialog(false);
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
        const updatedFonts = newThemeFonts.map((family, index) => {
            if ( index === fontFamilyIndex ) {
                return {
                    ...family,
                    shouldBeRemoved: true
                }
            }
            return family;
        });
        console.log(updatedFonts);
        setNewThemeFonts(updatedFonts);
    }

    function deleteFontFace () {
        const { fontFamilyIndex, fontFaceIndex } = fontToDelete;
        const updatedFonts = newThemeFonts.reduce((acc, fontFamily, index) => {
                const {fontFace=[], ...updatedFontFamily} = fontFamily;

                if ( fontFace.filter( face => !face.shouldBeRemoved ).length === 1 ) {
                    updatedFontFamily.shouldBeRemoved = true;
                }

                updatedFontFamily.fontFace = fontFace.map(
                    (face, i) => {
                        if ( fontFamilyIndex == index && fontFaceIndex === i ) {
                            return {
                                ...face,
                                shouldBeRemoved: true
                            }
                        }
                        return face;
                    }
                );
                return [
                    ...acc,
                    updatedFontFamily
                ];

        }, []);
        setNewThemeFonts(updatedFonts);
    }

    const fontFamilyToDelete = newThemeFonts[fontToDelete.fontFamilyIndex];
    const fontFaceToDelete = newThemeFonts[fontToDelete.fontFamilyIndex]?.fontFace?.[fontToDelete.fontFaceIndex];

    return (
        <>
            { isHelpOpen && (
                <Modal 
                    title={<><Icon icon={"info"}/> {__("Info", "create-block-theme")}</>}
                    onRequestClose={toggleIsHelpOpen}
                >
                    <p>
                        {__("This is a list of your font families listed in the theme.json file of your theme.", "create-block-theme")}
                    </p>
                    <p>
                        {__("If your theme.json makes reference to fonts providers other than local they may not be displayed correctly.", "create-block-theme")}
                    </p>
                </Modal>
            ) }
            <p class="help">
                {__("These are the fonts currently embedded in your theme ", "create-block-theme")}
                <Button onClick={toggleIsHelpOpen} style={{padding:"0", height:"1rem"}}>
                    <Icon icon={"info"}/>
                </Button>
            </p>
            <input type="hidden" name="new-theme-fonts-json" value={JSON.stringify(newThemeFonts)} />
            <ConfirmDialog
				isOpen={ showConfirmDialog }
				onConfirm={ confirmDelete }
				onCancel={ cancelDelete }
			>
                {(fontToDelete?.fontFamilyIndex !== undefined && fontToDelete?.fontFaceIndex !== undefined )
                    ? <h3>{__(`Are you sure you want to delete "${fontFaceToDelete?.fontStyle} - ${fontFaceToDelete?.fontWeight}"  variant of "${fontFamilyToDelete?.fontFamily}" from your theme?`, "create-block-theme")}</h3>
                    : <h3>{__(`Are you sure you want to delete "${fontFamilyToDelete?.fontFamily}" from your theme?`, "create-block-theme")}</h3>
                }
                <p>{__('This action will delete the font definition and the font file assets from your theme.', "create-block-theme")}</p>
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

export default () =>  (
    <ManageFontsProvider>
        <ManageFonts />
    </ManageFontsProvider>
);