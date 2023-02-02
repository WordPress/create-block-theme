import { useContext } from '@wordpress/element';
import { ManageFontsContext, DEMO_DEFAULTS } from '../fonts-context';

function Demo ( { style } ) {

    const { demoText, demoType, demoFontSize } = useContext( ManageFontsContext );
    const Component = DEMO_DEFAULTS[ demoType ][ "component" ];
    const demoStyles = {
        ...style,
        fontSize: `${demoFontSize}px`,
        lineHeight: DEMO_DEFAULTS[ demoType ][ "lineHeight" ],
        margin: DEMO_DEFAULTS[ demoType ][ "margin" ],
    };
    return (
        <div>
            <Component style={ demoStyles }>{ demoText }</Component>
        </div>
    )
}

export default Demo;