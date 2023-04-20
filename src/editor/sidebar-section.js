import {
	Button,
	// eslint-disable-next-line
	__experimentalHStack as HStack,
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
import { chevronLeft } from '@wordpress/icons';

function SidebarSection( { children, title } ) {
	return (
		<PanelBody>
			<HStack justify="flex-start" alignment="center">
				<NavigatorBackButton
					isSmall
					icon={chevronLeft}
					aria-label={__( 'Return to previous screen', 'create-block-theme' )}
				/>
				<Heading level={ 2 } style={{margin:0}}>{ title }</Heading>
			</HStack>
			<Spacer margin={8} />
			{ children }
			<Spacer />

		</PanelBody>
	);
}

export default SidebarSection;