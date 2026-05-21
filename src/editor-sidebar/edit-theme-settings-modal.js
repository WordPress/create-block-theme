/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useMemo,
	createInterpolateElement,
} from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalHStack as HStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalText as Text,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalItem as Item,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalItemGroup as ItemGroup,
	BaseControl,
	Button,
	ColorIndicator,
	ColorPicker,
	Dropdown,
	FlexBlock,
	Modal,
	Notice,
	PanelBody,
	TabPanel,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { plus, lineSolid } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { postUpdateThemeSettings } from '../resolvers';

const COLOR_SETTINGS_KEYS = [
	'defaultPalette',
	'defaultGradients',
	'defaultDuotone',
	'custom',
	'customGradient',
	'customDuotone',
	'link',
];

const COLOR_SETTINGS_DEFAULTS = {
	defaultPalette: true,
	defaultGradients: true,
	defaultDuotone: true,
	custom: true,
	customGradient: true,
	customDuotone: true,
	link: false,
};

const ColorSettingsPanel = ( { value, onChange } ) => {
	const update = ( key ) => ( next ) =>
		onChange( { ...value, [ key ]: next } );

	return (
		<VStack spacing={ 8 }>
			<VStack spacing={ 1 }>
				<BaseControl.VisualLabel>
					{ __( 'Default presets', 'create-block-theme' ) }
				</BaseControl.VisualLabel>
				<VStack spacing={ 3 }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Default duotone filters',
							'create-block-theme'
						) }
						checked={ value.defaultDuotone }
						onChange={ update( 'defaultDuotone' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Default gradients',
							'create-block-theme'
						) }
						checked={ value.defaultGradients }
						onChange={ update( 'defaultGradients' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Default palette', 'create-block-theme' ) }
						checked={ value.defaultPalette }
						onChange={ update( 'defaultPalette' ) }
					/>
				</VStack>
			</VStack>
			<VStack spacing={ 1 }>
				<BaseControl.VisualLabel>
					{ __( 'Custom presets', 'create-block-theme' ) }
				</BaseControl.VisualLabel>
				<VStack spacing={ 3 }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Custom colors', 'create-block-theme' ) }
						checked={ value.custom }
						onChange={ update( 'custom' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Custom duotone filters',
							'create-block-theme'
						) }
						checked={ value.customDuotone }
						onChange={ update( 'customDuotone' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Custom gradients', 'create-block-theme' ) }
						checked={ value.customGradient }
						onChange={ update( 'customGradient' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Link color', 'create-block-theme' ) }
						help={ __(
							'Enable the link color control.',
							'create-block-theme'
						) }
						checked={ value.link }
						onChange={ update( 'link' ) }
					/>
				</VStack>
			</VStack>
		</VStack>
	);
};

const PaletteRow = ( { entry, onUpdate, onRemove } ) => (
	<Item className="cbt-palette-list-item">
		<HStack alignment="center" spacing={ 3 }>
			<Dropdown
				popoverProps={ { placement: 'bottom-start' } }
				renderToggle={ ( { onToggle, isOpen } ) => (
					<Button
						onClick={ onToggle }
						aria-expanded={ isOpen }
						label={ __( 'Edit color', 'create-block-theme' ) }
						showTooltip
						className="cbt-palette-swatch-button"
					>
						<ColorIndicator colorValue={ entry.color } />
					</Button>
				) }
				renderContent={ () => (
					<ColorPicker
						color={ entry.color }
						onChange={ ( color ) =>
							onUpdate( { ...entry, color } )
						}
					/>
				) }
			/>
			<FlexBlock>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Name', 'create-block-theme' ) }
					hideLabelFromVision
					placeholder={ __( 'Name', 'create-block-theme' ) }
					value={ entry.name }
					onChange={ ( name ) => onUpdate( { ...entry, name } ) }
				/>
			</FlexBlock>
			<FlexBlock>
				<TextControl
					__nextHasNoMarginBottom
					__next40pxDefaultSize
					label={ __( 'Slug', 'create-block-theme' ) }
					hideLabelFromVision
					placeholder={ __( 'Slug', 'create-block-theme' ) }
					value={ entry.slug }
					onChange={ ( slug ) => onUpdate( { ...entry, slug } ) }
				/>
			</FlexBlock>
			<Button
				icon={ lineSolid }
				label={ __( 'Remove color', 'create-block-theme' ) }
				onClick={ onRemove }
				className="cbt-palette-swatch-button"
			/>
		</HStack>
	</Item>
);

const PalettePanel = ( { value, onChange } ) => {
	const updateEntry = ( index, updated ) =>
		onChange( value.map( ( e, i ) => ( i === index ? updated : e ) ) );

	const removeEntry = ( index ) =>
		onChange( value.filter( ( _, i ) => i !== index ) );

	const addColor = () =>
		onChange( [
			...value,
			{
				slug: `new-color-${ value.length + 1 }`,
				name: __( 'New color', 'create-block-theme' ),
				color: '#000000',
			},
		] );

	return (
		<VStack spacing={ 1 }>
			<HStack
				className="cbt-palette-section-header"
				justify="space-between"
				alignment="center"
			>
				<BaseControl.VisualLabel>
					{ __( 'Color presets', 'create-block-theme' ) }
				</BaseControl.VisualLabel>
				<Button
					icon={ plus }
					label={ __( 'Add new color', 'create-block-theme' ) }
					onClick={ addColor }
					showTooltip
				/>
			</HStack>
			{ value.length > 0 && (
				<ItemGroup isBordered isSeparated>
					{ value.map( ( entry, index ) => (
						<PaletteRow
							key={ index }
							entry={ entry }
							onUpdate={ ( updated ) =>
								updateEntry( index, updated )
							}
							onRemove={ () => removeEntry( index ) }
						/>
					) ) }
				</ItemGroup>
			) }
		</VStack>
	);
};

const ColorTab = ( {
	colorSettings,
	onChangeColorSettings,
	palette,
	onChangePalette,
} ) => (
	<>
		<PanelBody
			title={ __( 'Color Settings', 'create-block-theme' ) }
			initialOpen
		>
			<ColorSettingsPanel
				value={ colorSettings }
				onChange={ onChangeColorSettings }
			/>
		</PanelBody>
		<PanelBody title={ __( 'Palette', 'create-block-theme' ) } initialOpen>
			<PalettePanel value={ palette } onChange={ onChangePalette } />
		</PanelBody>
	</>
);

const pickColorSettings = ( themeColor ) => {
	const out = { ...COLOR_SETTINGS_DEFAULTS };
	for ( const key of COLOR_SETTINGS_KEYS ) {
		if ( themeColor && key in themeColor ) {
			out[ key ] = themeColor[ key ];
		}
	}
	return out;
};

// Per-field dirty diff between the modal's working state and the last-saved
// snapshot from the server. Returns the number of fields that differ —
// surfaced in the Update button label.
const countChanges = ( current, snapshot ) => {
	let count = 0;
	for ( const key of COLOR_SETTINGS_KEYS ) {
		if ( current.colorSettings[ key ] !== snapshot.colorSettings[ key ] ) {
			count += 1;
		}
	}
	if (
		JSON.stringify( current.palette ) !== JSON.stringify( snapshot.palette )
	) {
		count += 1;
	}
	return count;
};

export const EditThemeSettingsModal = ( { onRequestClose } ) => {
	const themeData = useSelect(
		( select ) => select( 'core' ).getCurrentTheme(),
		[]
	);
	const { invalidateResolution } = useDispatch( 'core' );
	const { createSuccessNotice, createErrorNotice } =
		useDispatch( noticesStore );

	const themeColor = themeData?.theme_json?.settings?.color;

	const initialState = useMemo(
		() => ( {
			colorSettings: pickColorSettings( themeColor ),
			palette: themeColor?.palette ? [ ...themeColor.palette ] : [],
		} ),
		[ themeColor ]
	);

	const [ colorSettings, setColorSettings ] = useState(
		initialState.colorSettings
	);
	const [ palette, setPalette ] = useState( initialState.palette );
	const [ snapshot, setSnapshot ] = useState( initialState );
	const [ isSaving, setIsSaving ] = useState( false );

	// Reseed local state when the server-side theme data refreshes (initial
	// load, and after a successful save invalidates the resolver).
	useEffect( () => {
		setColorSettings( initialState.colorSettings );
		setPalette( initialState.palette );
		setSnapshot( initialState );
	}, [ initialState ] );

	const changeCount = countChanges( { colorSettings, palette }, snapshot );
	const isDirty = changeCount > 0;

	const handleUpdateClick = async () => {
		setIsSaving( true );
		try {
			await postUpdateThemeSettings( {
				settings: {
					color: {
						...colorSettings,
						palette,
					},
				},
			} );
			createSuccessNotice(
				__( 'Theme settings saved.', 'create-block-theme' ),
				{ type: 'snackbar' }
			);
			// Refresh the theme entity so panels reseed from the new server
			// state via the `initialState` effect above.
			invalidateResolution( 'getCurrentTheme' );
		} catch ( error ) {
			createErrorNotice(
				error?.message ||
					__(
						'An error occurred while saving theme settings.',
						'create-block-theme'
					),
				{ type: 'snackbar' }
			);
		} finally {
			setIsSaving( false );
		}
	};

	const tabs = [
		{ name: 'color', title: __( 'Color', 'create-block-theme' ) },
	];

	const renderTab = ( tab ) => {
		switch ( tab.name ) {
			case 'color':
				return (
					<ColorTab
						colorSettings={ colorSettings }
						onChangeColorSettings={ setColorSettings }
						palette={ palette }
						onChangePalette={ setPalette }
					/>
				);
			default:
				return null;
		}
	};

	const updateLabel = isDirty
		? sprintf(
				/* translators: %d: number of pending changes */
				__( 'Update (%d changes)', 'create-block-theme' ),
				changeCount
		  )
		: __( 'Update', 'create-block-theme' );

	return (
		<Modal
			size="large"
			title={ sprintf(
				// translators: %s: theme name.
				__( 'Theme settings for %s', 'create-block-theme' ),
				themeData?.name?.raw ?? ''
			) }
			onRequestClose={ onRequestClose }
			className="create-block-theme__edit-theme-settings-modal"
		>
			<VStack spacing={ 4 }>
				<Text>
					{ __(
						'Edit the settings of the current theme.',
						'create-block-theme'
					) }
				</Text>
				<Notice
					status="warning"
					isDismissible={ false }
					className="create-block-theme__edit-theme-settings-modal__disclaimer"
				>
					<div>
						{ __(
							'Changes you’ve saved in the Site Editor live in the database, not in your theme files.',
							'create-block-theme'
						) }
					</div>
					<div>
						{ createInterpolateElement(
							__(
								'Click <strong>Save Changes to Theme</strong> first to write them to theme.json — otherwise the edits you make here may conflict with or hide them.',
								'create-block-theme'
							),
							{ strong: <strong /> }
						) }
					</div>
				</Notice>
				<TabPanel
					className="create-block-theme__edit-theme-settings-tabs"
					tabs={ tabs }
				>
					{ renderTab }
				</TabPanel>
			</VStack>
			<HStack
				justify="flex-end"
				className="create-block-theme__edit-theme-settings-modal__footer"
			>
				<Button
					variant="primary"
					onClick={ handleUpdateClick }
					disabled={ ! isDirty || isSaving }
					isBusy={ isSaving }
				>
					{ updateLabel }
				</Button>
			</HStack>
		</Modal>
	);
};
