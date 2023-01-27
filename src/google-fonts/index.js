import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';

import FontVariant from './font-variant';
import googleFontsData from "../../assets/google-fonts/fallback-fonts-list.json";


function GoogleFonts () {
    const [ selectedFont, setSelectedFont ] = useState( null );
    const [ selectedVariants, setSelectedVariants ] = useState( [] );

    const handleToggleAllVariants = () => {
        if ( !selectedVariants.length ) {
            setSelectedVariants( selectedFont.variants );
        } else {
            setSelectedVariants( [] );
        }
    }

    const handleToggleVariant = ( variant ) => {
        if ( selectedVariants.includes( variant ) ) {
            setSelectedVariants( selectedVariants.filter( ( v ) => v !== variant ) );
        } else {
            setSelectedVariants( [ ...selectedVariants, variant ] );
        }
    }

    useEffect( () => {
        setSelectedVariants( [] );
    }, [ selectedFont ] );

    const theme = useSelect( ( select ) => {
        return select( coreDataStore ).getCurrentTheme();
    }, null );

    const handleSelectChange = ( event ) => {
        setSelectedFont( googleFontsData.items[ event.target.value ] ) ;
    }

    console.log( selectedVariants );

    return (
        <div className="wrap google-fonts-page">
			<h2>{ __('Add Google fonts to your theme', 'create-block-theme') }</h2>
            <h3>{ __('Add Google fonts assets and font face definitions to your currently active theme', 'create-block-theme')} ({ theme?.name.rendered })</h3>


            <form enctype="multipart/form-data" action="" method="POST">
                <label for="google-font-id">{ __('Select Font', 'create-block-theme') }</label>

                <select name="google-font" id="google-font-id" onChange={ handleSelectChange }>
                    <option value={null}>{ __('Select a font...', 'create-block-theme') }</option>
                    { googleFontsData.items.map( ( font, index ) => (
                            <option value={ index }>{ font.family }</option>
                    ))}
                </select>

                <br /><br />
                <p className="hint">{ __('Select the font variants you want to include:', 'create-block-theme') }</p>

                { selectedFont && (
                    <table className="wp-list-table widefat striped table-view-list" id="google-fonts-table">
                        <thead>
                            <tr>
                                <td className="">
                                    <input
                                        type="checkbox"
                                        onClick={ handleToggleAllVariants }
                                        checked={ selectedVariants.length === selectedFont?.variants.length }
                                    />
                                </td>
                                <td className="">{ __('Weight', 'create-block-theme') }</td>
                                <td className="">{ __('Style', 'create-block-theme') }</td>
                                <td className="">{ __('Preview', 'create-block-theme') }</td>
                            </tr>
                        </thead>
                        <tbody id="font-options">
                            {selectedFont.variants.map( ( variant, i ) => (
                                <FontVariant
                                    font={ selectedFont }
                                    variant={ variant }
                                    key={`font-variant-${i}`}
                                    isSelected={ selectedVariants.includes( variant ) }
                                    handleToggle={ () => handleToggleVariant( variant ) }
                                />
                            ))}
                        </tbody>
                    </table>
                ) }

                <br /><br />
                <input type="hidden" name="font-name" id="font-name" value="" />
                <input type="hidden" name="google-font-variants" id="google-font-variants" value="" />
                <input type="submit" value={ __('Add google fonts to your theme', 'create-block-theme') } className="button button-primary" id="google-fonts-submit" disabled={true} />
                <input type="hidden" name="nonce"/>
            </form>
		</div>
    )
}

export default GoogleFonts;
