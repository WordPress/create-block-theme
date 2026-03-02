/**
 * WordPress dependencies
 */
import {
	Navigator,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHeading as Heading,
} from '@wordpress/components';
import { isRTL, __ } from '@wordpress/i18n';
import { chevronRight, chevronLeft } from '@wordpress/icons';

const ScreenHeader = ( { title, onBack, description } ) => {
	return (
		<Spacer marginBottom={ 0 } paddingX={ 4 } paddingY={ 3 }>
			<VStack>
				<HStack spacing={ 2 }>
					<Navigator.BackButton
						style={ { minWidth: 24, padding: 0 } }
						icon={ isRTL() ? chevronRight : chevronLeft }
						size="small"
						label={ __( 'Back', 'create-block-theme' ) }
						onClick={ onBack }
					/>
					<Spacer>
						<Heading
							level={ 2 }
							size={ 13 }
							// Need to override the too specific bottom margin for complementary areas.
							style={ { margin: 0 } }
						>
							{ title }
						</Heading>
					</Spacer>
				</HStack>
				{ description }
			</VStack>
		</Spacer>
	);
};

export default ScreenHeader;
