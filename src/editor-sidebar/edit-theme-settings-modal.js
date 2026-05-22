/**
 * WordPress dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import {
	useState,
	useEffect,
	useMemo,
	useRef,
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
	Icon,
	Modal,
	Notice,
	PanelBody,
	TabPanel,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { plus, lineSolid, chevronRight } from '@wordpress/icons';

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
		<div className="cbt-color-settings-columns">
			<VStack spacing={ 1 }>
				<BaseControl.VisualLabel>
					{ __( 'Default presets', 'create-block-theme' ) }
				</BaseControl.VisualLabel>
				<VStack spacing={ 3 }>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Default palette', 'create-block-theme' ) }
						help={ __(
							'Show WordPress’s default color palette in the editor.',
							'create-block-theme'
						) }
						checked={ value.defaultPalette }
						onChange={ update( 'defaultPalette' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Default gradients',
							'create-block-theme'
						) }
						help={ __(
							'Show WordPress’s default gradient presets in the editor.',
							'create-block-theme'
						) }
						checked={ value.defaultGradients }
						onChange={ update( 'defaultGradients' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Default duotone filters',
							'create-block-theme'
						) }
						help={ __(
							'Show WordPress’s default duotone filters in the editor.',
							'create-block-theme'
						) }
						checked={ value.defaultDuotone }
						onChange={ update( 'defaultDuotone' ) }
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
						help={ __(
							'Allow custom colors in the editor color picker.',
							'create-block-theme'
						) }
						checked={ value.custom }
						onChange={ update( 'custom' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __( 'Custom gradients', 'create-block-theme' ) }
						help={ __(
							'Let users create custom gradients in the editor.',
							'create-block-theme'
						) }
						checked={ value.customGradient }
						onChange={ update( 'customGradient' ) }
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Custom duotone filters',
							'create-block-theme'
						) }
						help={ __(
							'Let users create custom duotone filters.',
							'create-block-theme'
						) }
						checked={ value.customDuotone }
						onChange={ update( 'customDuotone' ) }
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
		</div>
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
				justify="flex-end"
				alignment="center"
			>
				<Button icon={ plus } variant="tertiary" onClick={ addColor }>
					{ __( 'Add a color', 'create-block-theme' ) }
				</Button>
			</HStack>
			{ value.length > 0 && (
				<>
					<HStack
						className="cbt-palette-column-headers"
						alignment="center"
						spacing={ 3 }
					>
						<span
							className="cbt-palette-column-headers__spacer"
							aria-hidden="true"
						/>
						<FlexBlock>
							<span className="cbt-palette-column-headers__label">
								{ __( 'Name', 'create-block-theme' ) }
							</span>
						</FlexBlock>
						<FlexBlock>
							<span className="cbt-palette-column-headers__label">
								{ __( 'Slug', 'create-block-theme' ) }
							</span>
						</FlexBlock>
						<span
							className="cbt-palette-column-headers__spacer"
							aria-hidden="true"
						/>
					</HStack>
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
				</>
			) }
		</VStack>
	);
};

const SWATCH_PREVIEW_COUNT = 5;

const PaletteSummary = ( { palette, onEdit } ) => (
	<HStack
		className="cbt-palette-summary"
		alignment="center"
		justify="space-between"
	>
		<HStack
			className="cbt-palette-summary__swatches"
			alignment="center"
			spacing={ 0 }
			expanded={ false }
		>
			{ palette
				.slice( 0, SWATCH_PREVIEW_COUNT )
				.map( ( entry, index ) => (
					<ColorIndicator key={ index } colorValue={ entry.color } />
				) ) }
		</HStack>
		<Button
			variant="tertiary"
			onClick={ onEdit }
			className="cbt-palette-summary__edit"
		>
			<HStack alignment="center" spacing={ 1 } expanded={ false }>
				<span>{ __( 'Edit palette', 'create-block-theme' ) }</span>
				<Icon icon={ chevronRight } />
			</HStack>
		</Button>
	</HStack>
);

const ColorTab = ( {
	colorSettings,
	onChangeColorSettings,
	palette,
	onChangePalette,
} ) => {
	const [ isPaletteOpen, setIsPaletteOpen ] = useState( false );
	const paletteRef = useRef( null );

	const focusPalette = () => {
		setIsPaletteOpen( true );
		// Scroll on the next frame so the accordion has expanded (if it was
		// closed) before we measure its target position. Works on subsequent
		// clicks too, because scrollIntoView fires unconditionally.
		window.requestAnimationFrame( () => {
			paletteRef.current?.scrollIntoView( {
				behavior: 'smooth',
				block: 'start',
			} );
		} );
	};

	return (
		<>
			{ palette.length > 0 && (
				<PaletteSummary palette={ palette } onEdit={ focusPalette } />
			) }
			<PanelBody
				title={ __(
					'Default and custom presets',
					'create-block-theme'
				) }
				initialOpen
			>
				<ColorSettingsPanel
					value={ colorSettings }
					onChange={ onChangeColorSettings }
				/>
			</PanelBody>
			<div ref={ paletteRef }>
				<PanelBody
					title={ __( 'Palette', 'create-block-theme' ) }
					opened={ isPaletteOpen }
					onToggle={ setIsPaletteOpen }
				>
					<PalettePanel
						value={ palette }
						onChange={ onChangePalette }
					/>
				</PanelBody>
			</div>
		</>
	);
};

const pickColorSettings = ( themeColor ) => {
	const out = { ...COLOR_SETTINGS_DEFAULTS };
	for ( const key of COLOR_SETTINGS_KEYS ) {
		if ( themeColor && key in themeColor ) {
			out[ key ] = themeColor[ key ];
		}
	}
	return out;
};

// Human-readable label for each top-level slice of `settings` / `styles`
// that the user may have customized in the Site Editor. Keys are taken
// from the canonical theme.json schema; anything not in this map is
// surfaced under "other" as a catch-all so newly-introduced WP keys don't
// silently vanish from the warning.
const USER_CUSTOMIZATION_SECTION_LABELS = {
	color: __( 'color', 'create-block-theme' ),
	typography: __( 'typography', 'create-block-theme' ),
	spacing: __( 'spacing', 'create-block-theme' ),
	layout: __( 'layout', 'create-block-theme' ),
	dimensions: __( 'dimensions', 'create-block-theme' ),
	border: __( 'borders', 'create-block-theme' ),
	shadow: __( 'shadows', 'create-block-theme' ),
	background: __( 'background', 'create-block-theme' ),
	elements: __( 'elements', 'create-block-theme' ),
	blocks: __( 'block styles', 'create-block-theme' ),
	filter: __( 'filters', 'create-block-theme' ),
	css: __( 'additional CSS', 'create-block-theme' ),
	custom: __( 'custom', 'create-block-theme' ),
};

const isNonEmpty = ( value ) => {
	if ( value === null || value === undefined ) {
		return false;
	}
	if ( Array.isArray( value ) ) {
		return value.length > 0;
	}
	if ( typeof value === 'object' ) {
		return Object.keys( value ).length > 0;
	}
	return true;
};

// Crawl the saved user Global Styles record + any in-editor edits and
// return the de-duplicated, human-readable list of top-level slices that
// have user-level customizations diverging from theme.json.
const getCustomizedSections = ( userGlobalStyles, edits ) => {
	const sections = new Set();

	const visit = ( record ) => {
		if ( ! record ) {
			return;
		}
		for ( const top of [ 'settings', 'styles' ] ) {
			const slice = record[ top ];
			if ( ! slice || typeof slice !== 'object' ) {
				continue;
			}
			for ( const [ key, value ] of Object.entries( slice ) ) {
				if ( isNonEmpty( value ) ) {
					sections.add( key );
				}
			}
		}
	};

	visit( userGlobalStyles );
	visit( edits );

	return Array.from( sections ).map(
		( key ) =>
			USER_CUSTOMIZATION_SECTION_LABELS[ key ] ||
			__( 'other', 'create-block-theme' )
	);
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

	const customizedSections = useSelect( ( select ) => {
		const core = select( 'core' );
		const getId = core.__experimentalGetCurrentGlobalStylesId;
		if ( typeof getId !== 'function' ) {
			return [];
		}
		const id = getId();
		if ( ! id ) {
			return [];
		}
		// Saved user-origin record (database).
		const record = core.getEntityRecord( 'root', 'globalStyles', id );
		// In-editor edits not yet persisted to the database.
		const edits = core.getEntityRecordEdits?.( 'root', 'globalStyles', id );
		return getCustomizedSections( record, edits );
	}, [] );
	const hasUserCustomizations = customizedSections.length > 0;

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
				{ hasUserCustomizations && (
					<Notice
						status="warning"
						isDismissible={ false }
						className="create-block-theme__edit-theme-settings-modal__disclaimer"
					>
						<div>
							{ createInterpolateElement(
								sprintf(
									/* translators: %s: comma-separated list of customized sections, wrapped in <list></list> (e.g. "color, typography") */
									__(
										'You have changes in the Site Editor that haven’t been written to theme.json: <list>%s</list>.',
										'create-block-theme'
									),
									customizedSections.join( ', ' )
								),
								{ list: <strong /> }
							) }
						</div>
						<div>
							{ createInterpolateElement(
								__(
									'Click <strong>Save Changes to Theme</strong> first — otherwise the edits you make here may be hidden by those overrides.',
									'create-block-theme'
								),
								{ strong: <strong /> }
							) }
						</div>
					</Notice>
				) }
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
