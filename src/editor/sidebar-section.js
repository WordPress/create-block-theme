import {
	Button,
	// eslint-disable-next-line
	__experimentalVStack as VStack,
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	// eslint-disable-next-line
	__experimentalHeading as Heading,
	Panel,
	PanelBody,
	PanelRow,
	TextControl,
	__experimentalNavigatorProvider as NavigatorProvider,
	__experimentalNavigatorScreen as NavigatorScreen,
	__experimentalNavigatorBackButton as NavigatorBackButton,
	__experimentalNavigatorButton as NavigatorButton,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

function SidebarSection( { children, title } ) {
	return (
		<PanelBody>
			<Heading level={ 2 }>{ title }</Heading>
			{ children }
			<Spacer />
			<NavigatorBackButton>
				{ __( 'Return to previous screen', 'create-block-theme' ) }
			</NavigatorBackButton>
		</PanelBody>
	);
}

export default SidebarSection;
