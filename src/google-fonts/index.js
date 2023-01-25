import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreDataStore } from '@wordpress/core-data';
import googleFontsData from "../../assets/google-fonts/fallback-fonts-list.json";


function GoogleFonts () {
    const [ selectedFont, setSelectedFont ] = useState( null );

    const theme = useSelect( ( select ) => {
        return select( coreDataStore ).getCurrentTheme();
    }, null );

    const handleSelectChange = ( event ) => {
        setSelectedFont( googleFontsData.items[ event.target.value ] ) ;
        console.log(googleFontsData.items[ event.target.value ]);
    }

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
                <p class="hint">{ __('Select the font variants you want to include:', 'create-block-theme') }</p>
                <table class="wp-list-table widefat fixed striped table-view-list" id="google-fonts-table">
                    <thead>
                        <tr>
                            <td class=""><input type="checkbox" id="select-all-variants" /></td>
                            <td class="">{ __('Variant', 'create-block-theme') }</td>
                            <td class="">{ __('Preview', 'create-block-theme') }</td>
                        </tr>
                    </thead>
                    <tbody id="font-options">
                        {selectedFont && selectedFont.variants.map( ( variant, i ) => (
                            <tr key={`variant-${i}`}>
                                <td class=""><input type="checkbox" name="google-font-variant" value={ variant } /></td>
                                <td class="">{ variant }</td>
                                <td class=""><span class="font-preview" style={ { fontFamily: selectedFont.family } }>{ selectedFont.family }</span></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                <br /><br />
                <input type="hidden" name="font-name" id="font-name" value="" />
                <input type="hidden" name="google-font-variants" id="google-font-variants" value="" />
                <input type="submit" value={ __('Add google fonts to your theme', 'create-block-theme') } class="button button-primary" id="google-fonts-submit" disabled={true} />
                <input type="hidden" name="nonce"/>
            </form>
		</div>
    )
}

export default GoogleFonts;
