import { useState, createContext } from '@wordpress/element';

import { DEMO_DEFAULTS, DEFAULT_DEMO_TYPE } from './constants';
export const ManageFontsContext = createContext();

export function ManageFontsProvider( { children } ) {
	const [ demoType, setDemoType ] = useState(
		localStorage.getItem( 'cbt_default-demo-type' ) || DEFAULT_DEMO_TYPE
	);

	const [ demoText, setDemoText ] = useState(
		localStorage.getItem( 'cbt_default-demo-text' ) ||
			DEMO_DEFAULTS[ demoType ].text
	);

	const [ demoFontSize, setDemoFontSize ] = useState(
		parseInt( localStorage.getItem( 'cbt_default-demo-font-size' ) ) ||
			DEMO_DEFAULTS[ demoType ].size
	);

	const handleDemoTextChange = ( newDemoText ) => {
		setDemoText( newDemoText );
		localStorage.setItem( 'cbt_default-demo-text', newDemoText );
	};

	const handleDemoTypeChange = ( newDemoType ) => {
		setDemoType( newDemoType );
		localStorage.setItem( 'cbt_default-demo-type', newDemoType );
		resetDefaults( newDemoType );
	};

	const handleDemoFontSizeChange = ( newDemoFontSize ) => {
		setDemoFontSize( newDemoFontSize );
		localStorage.setItem( 'cbt_default-demo-font-size', newDemoFontSize );
	};

	const resetDefaults = ( newDemoType ) => {
		handleDemoTextChange( DEMO_DEFAULTS[ newDemoType || demoType ].text );
		handleDemoFontSizeChange(
			DEMO_DEFAULTS[ newDemoType || demoType ].size
		);
	};

	// The list of families that are open (showing the list of font faces) in the font manager.
	const [ familiesOpen, setFamiliesOpen ] = useState(
		JSON.parse( localStorage.getItem( 'cbt_families-open' ) ) || []
	);

	const handleToggleFamily = ( familyName ) => {
		let newFamiliesOpen = [];
		if ( familiesOpen.includes( familyName ) ) {
			newFamiliesOpen = familiesOpen.filter(
				( name ) => name !== familyName
			);
		} else {
			newFamiliesOpen = [ ...familiesOpen, familyName ];
		}
		setFamiliesOpen( newFamiliesOpen );
		localStorage.setItem(
			'cbt_families-open',
			JSON.stringify( newFamiliesOpen )
		);
	};

	return (
		<ManageFontsContext.Provider
			value={ {
				demoText,
				handleDemoTextChange,
				resetDefaults,
				demoType,
				handleDemoTypeChange,
				demoFontSize,
				handleDemoFontSizeChange,
				familiesOpen,
				handleToggleFamily,
			} }
		>
			{ children }
		</ManageFontsContext.Provider>
	);
}
