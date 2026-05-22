/**
 * WordPress dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
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

// Local-only stable ID for React keys on palette rows. Entries that come
// from the server (initial load, save response) get a `__cbtRowId` added
// when we seed local state; entries the user adds get one at creation.
// Stripped from the payload before sending to the server.
let nextRowIdCounter = 0;
const newRowId = () => `cbt-row-${ ++nextRowIdCounter }`;

const augmentPaletteWithIds = ( entries ) =>
	entries.map( ( entry ) => ( {
		...entry,
		__cbtRowId: entry.__cbtRowId || newRowId(),
	} ) );

const stripPaletteIds = ( entries ) =>
	entries.map( ( { __cbtRowId: _id, ...rest } ) => rest );

const palettesEqual = ( a, b ) => {
	if ( a.length !== b.length ) {
		return false;
	}
	for ( let i = 0; i < a.length; i++ ) {
		if (
			a[ i ].slug !== b[ i ].slug ||
			a[ i ].name !== b[ i ].name ||
			a[ i ].color !== b[ i ].color
		) {
			return false;
		}
	}
	return true;
};

// Find the next free index for an auto-generated `new-color-N` slug so
// Add → Remove → Add doesn't produce duplicate slugs.
const nextNewColorIndex = ( entries ) => {
	let max = 0;
	for ( const entry of entries ) {
		const match = /^new-color-(\d+)$/.exec( entry.slug || '' );
		if ( match ) {
			const n = parseInt( match[ 1 ], 10 );
			if ( n > max ) {
				max = n;
			}
		}
	}
	return max + 1;
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
				iconSize={ 20 }
				label={ __( 'Remove color', 'create-block-theme' ) }
				onClick={ onRemove }
				className="cbt-palette-swatch-button"
			/>
		</HStack>
	</Item>
);

const PalettePanel = ( { value, onChange } ) => {
	const lastRowRef = useRef( null );
	const prevLengthRef = useRef( value.length );

	// After a row is appended, slide the new row into view. Compare against
	// the previous length so edits/removes don't trigger a scroll.
	useEffect( () => {
		if ( value.length > prevLengthRef.current && lastRowRef.current ) {
			lastRowRef.current.scrollIntoView( {
				behavior: 'smooth',
				block: 'center',
			} );
		}
		prevLengthRef.current = value.length;
	}, [ value.length ] );

	const updateEntry = ( index, updated ) =>
		onChange( value.map( ( e, i ) => ( i === index ? updated : e ) ) );

	const removeEntry = ( index ) =>
		onChange( value.filter( ( _, i ) => i !== index ) );

	const addColor = () =>
		onChange( [
			...value,
			{
				__cbtRowId: newRowId(),
				slug: `new-color-${ nextNewColorIndex( value ) }`,
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
							<div
								key={ entry.__cbtRowId }
								ref={
									index === value.length - 1
										? lastRowRef
										: null
								}
							>
								<PaletteRow
									entry={ entry }
									onUpdate={ ( updated ) =>
										updateEntry( index, updated )
									}
									onRemove={ () => removeEntry( index ) }
								/>
							</div>
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
		justify="flex-start"
		spacing={ 3 }
		expanded={ false }
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
	const prevPaletteLengthRef = useRef( palette.length );

	// When the palette transitions from empty (force-open) to having entries,
	// the force-open condition stops applying. Without this, adding the very
	// first color via the empty-state button would collapse the accordion and
	// hide the row that was just added.
	useEffect( () => {
		if ( prevPaletteLengthRef.current === 0 && palette.length > 0 ) {
			setIsPaletteOpen( true );
		}
		prevPaletteLengthRef.current = palette.length;
	}, [ palette.length ] );

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
					// When the palette is empty the summary card is hidden,
					// so force the accordion open to keep the "Add a color"
					// button discoverable.
					opened={ isPaletteOpen || palette.length === 0 }
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

// Returns the set of color-settings keys whose current value differs from
// the last-saved snapshot. Used to drive both the Update-button counter
// and the minimal-patch payload sent to the server.
const getDirtyColorKeys = ( current, snapshot ) => {
	const keys = [];
	for ( const key of COLOR_SETTINGS_KEYS ) {
		if ( current[ key ] !== snapshot[ key ] ) {
			keys.push( key );
		}
	}
	return keys;
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

	// `snapshot` is the server-canonical state — palette entries here have
	// NO `__cbtRowId` since the server never sees that key. Working state
	// (`palette`) is augmented with row IDs for stable React keys.
	const initialState = useMemo(
		() => ( {
			colorSettings: pickColorSettings( themeColor ),
			palette: Array.isArray( themeColor?.palette )
				? themeColor.palette
				: [],
		} ),
		[ themeColor ]
	);

	const [ colorSettings, setColorSettings ] = useState(
		initialState.colorSettings
	);
	const [ palette, setPalette ] = useState( () =>
		augmentPaletteWithIds( initialState.palette )
	);
	const [ snapshot, setSnapshot ] = useState( initialState );
	const [ isSaving, setIsSaving ] = useState( false );

	const dirtyColorKeys = useMemo(
		() => getDirtyColorKeys( colorSettings, snapshot.colorSettings ),
		[ colorSettings, snapshot.colorSettings ]
	);
	const strippedPalette = useMemo(
		() => stripPaletteIds( palette ),
		[ palette ]
	);
	const paletteDirty = useMemo(
		() => ! palettesEqual( strippedPalette, snapshot.palette ),
		[ strippedPalette, snapshot.palette ]
	);
	const changeCount = dirtyColorKeys.length + ( paletteDirty ? 1 : 0 );
	const isDirty = changeCount > 0;

	const hasEmptySlug = palette.some(
		( entry ) => ! entry.slug || ! entry.slug.trim()
	);

	// Reseed local state from refreshed server data — but only if the user
	// hasn't started editing in this session. Otherwise a mid-flight cache
	// invalidation (e.g. another resolver triggers it) would silently wipe
	// pending edits.
	useEffect( () => {
		if ( isDirty ) {
			return;
		}
		setColorSettings( initialState.colorSettings );
		setPalette( augmentPaletteWithIds( initialState.palette ) );
		setSnapshot( initialState );
		// `isDirty` is intentionally excluded from deps: we only want to
		// reseed when the server-side data changes, not when the user's
		// edits transition the dirty flag.
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ initialState ] );

	const handleUpdateClick = async () => {
		// Build a minimal patch: only the keys the user touched in this
		// session. Avoids overwriting fields another tab/CLI may have
		// edited concurrently (server-side merge is RFC 7396 last-writes-
		// wins on lists, so sending an unchanged palette would still
		// clobber concurrent palette edits).
		const colorPayload = {};
		for ( const key of dirtyColorKeys ) {
			colorPayload[ key ] = colorSettings[ key ];
		}
		if ( paletteDirty ) {
			colorPayload.palette = strippedPalette;
		}
		if ( Object.keys( colorPayload ).length === 0 ) {
			return;
		}

		setIsSaving( true );
		try {
			// The endpoint returns `{ status, theme_json: <merged> }` on
			// success. Reseed snapshot from the merged theme.json directly
			// instead of relying on a refetch: `getCurrentTheme` is
			// entity-record-backed and `invalidateResolution` doesn't
			// reliably re-fetch it before the user sees the dirty count.
			const response = await postUpdateThemeSettings( {
				settings: { color: colorPayload },
			} );
			const savedColor = response?.theme_json?.settings?.color || {};
			const nextColorSettings = pickColorSettings( savedColor );
			const nextPaletteRaw = Array.isArray( savedColor.palette )
				? savedColor.palette
				: [];
			setColorSettings( nextColorSettings );
			setPalette( augmentPaletteWithIds( nextPaletteRaw ) );
			setSnapshot( {
				colorSettings: nextColorSettings,
				palette: nextPaletteRaw,
			} );
			createSuccessNotice(
				__( 'Theme settings saved.', 'create-block-theme' ),
				{ type: 'snackbar' }
			);
			// Also invalidate the entity-record cache so the rest of the UI
			// (e.g. View theme.json, future modal opens) sees fresh data.
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
				_n(
					'Update (%d change)',
					'Update (%d changes)',
					changeCount,
					'create-block-theme'
				),
				changeCount
		  )
		: __( 'Update', 'create-block-theme' );

	// Use Intl.ListFormat so the separator (and final "and") match the
	// user's locale instead of being hard-coded English punctuation.
	// Falls back to a plain ", " join in environments without it.
	const sectionList =
		typeof Intl !== 'undefined' && Intl.ListFormat
			? new Intl.ListFormat( undefined, {
					style: 'long',
					type: 'conjunction',
			  } ).format( customizedSections )
			: customizedSections.join( ', ' );

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
									sectionList
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
					disabled={ ! isDirty || isSaving || hasEmptySlug }
					isBusy={ isSaving }
					label={
						hasEmptySlug
							? __(
									'Every palette entry needs a slug.',
									'create-block-theme'
							  )
							: undefined
					}
					showTooltip={ hasEmptySlug }
				>
					{ updateLabel }
				</Button>
			</HStack>
		</Modal>
	);
};
