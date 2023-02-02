import {
    Button,
    RangeControl,
    SelectControl,
    __experimentalInputControl as InputControl
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useContext } from '@wordpress/element';
import { ManageFontsContext } from '../fonts-context';
import { update } from '@wordpress/icons';
import "./demo-text-input.css";

function DemoTextInput () {
    
    const { demoText, handleDemoTextChange, demoType, handleDemoTypeChange, demoFontSize, handleDemoFontSizeChange, resetDefaults } = useContext( ManageFontsContext );

    return (
        <div className="demo-text-input">
            <div className="container">

                <SelectControl
                    label={ __( "Preview type", "create-block-theme" ) }
                    onChange={ handleDemoTypeChange }
                    value={ demoType }
                >
                    <option value="heading">{ __( "Heading", "create-block-theme" ) }</option>
                    <option value="sentence">{ __( "Sentence", "create-block-theme" ) }</option>
                    <option value="paragraph">{ __( "Paragraph", "create-block-theme" ) }</option>
                </SelectControl>

                <InputControl
                    label="Demo text"
                    value={ demoText }
                    onChange={ handleDemoTextChange }
                />

                <div>
                    <RangeControl
                        label={ __( "Font size", "create-block-theme" ) }
                        initialPosition={ demoFontSize }
                        value={ demoFontSize }
                        onChange={ handleDemoFontSizeChange }
                        min={ 8 }
                        max={ 100 }
                        withInputField={ false }
                    />
                </div>

                <div>
                    <Button variant="secondary" icon={ update } onClick={ () => {
                        resetDefaults( "sentence" );
                        handleDemoTypeChange( "sentence" );
                    }}>
                        { __( "Reset", "create-block-theme" ) }
                    </Button>
                </div>

            </div>
        </div>
    )
}

export default DemoTextInput;
