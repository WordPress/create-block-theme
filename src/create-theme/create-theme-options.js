import { __, sprintf } from '@wordpress/i18n';
import { Dashicon } from '@wordpress/components';
import { useState } from '@wordpress/element';
import styles from './styles.module.css';

export function getThemeExportOptions( themeName ) {
	return {
		export: {
			label: sprintf(
				/* translators: %s: Theme Name. */
				__( 'Export %s', 'create-block-theme' ),
				themeName
			),
			description: __(
				'Export the activated theme with user changes.',
				'create-block-theme'
			),
		},
		clone: {
			label: sprintf(
				/* translators: %s: Theme Name. */
				__( 'Clone %s', 'create-block-theme' ),
				themeName
			),
			description: __(
				'Create a new theme cloning the activated theme. The resulting theme will have all of the assets of the activated theme as well as user changes.',
				'create-block-theme'
			),
		},
		child: {
			label: sprintf(
				/* translators: %s: Theme Name. */
				__( `Create child of %s`, 'create-block-theme' ),
				themeName
			),
			description: __(
				'Create a new child theme. The currently activated theme will be the parent theme.',
				'create-block-theme'
			),
		},
		sibling: {
			label: sprintf(
				/* translators: %s: Theme Name. */
				__( `Create sibling of %s`, 'create-block-theme' ),
				themeName
			),
			description: __(
				'Create a new theme cloning the activated child theme.  The parent theme will be the same as the parent of the currently activated theme. The resulting theme will have all of the assets of the activated theme, none of the assets provided by the parent theme, as well as user changes.',
				'create-block-theme'
			),
		},
		override: {
			label: sprintf(
				/* translators: %s: Theme Name. */
				__( 'Overwrite %s', 'create-block-theme' ),
				themeName
			),
			description: __(
				'Save USER changes as THEME changes and delete the USER changes.  Your changes will be saved in the theme on the folder.',
				'create-block-theme'
			),
		},
		blank: {
			label: __( 'Create blank theme', 'create-block-theme' ),
			description: __(
				`Generate a boilerplate "empty" theme inside of this site's themes directory.`,
				'create-block-theme'
			),
		},
		variation: {
			label: __( 'Create a style variation', 'create-block-theme' ),
			description: sprintf(
				// translators: %1$s: Theme name
				__(
					'Save user changes as a style variation of %1$s.',
					'create-block-theme'
				),
				themeName
			),
		},
	};
}

export function CreateThemeOptions( { themeName, isChildTheme, onChange } ) {
	const [ selected, setSelected ] = useState();
	const op = getThemeExportOptions( themeName );

	const exportCategories = [
		{
			label: 'Start fresh',
			options: [ 'export', 'blank' ].map( ( o ) => ( {
				value: o,
				...op[ o ],
			} ) ),
		},
		{
			label: 'Start from an existing theme',
			options: [
				isChildTheme ? 'sibling' : 'child',
				'override',
				'variation',
			].map( ( o ) => ( { value: o, ...op[ o ] } ) ),
		},
	];

	function handleOptionClick( option ) {
		setSelected( option );
		onChange && onChange( option );
	}

	return (
		<div className={ styles.optionsContainer }>
			<h2>Active theme: { themeName }</h2>

			{ exportCategories.map( ( category, index ) => {
				return (
					<div key={ index }>
						<h3>{ category.label }</h3>
						{ category.options.map( ( option ) => (
							<Option
								key={ option.value }
								{ ...option }
								isSelected={ option.value === selected }
								onClick={ handleOptionClick }
							/>
						) ) }
					</div>
				);
			} ) }
		</div>
	);
}

function Option( { label, value, isSelected, onClick } ) {
	return (
		<div
			className={
				styles.themeOption +
				' ' +
				( isSelected ? styles.selectedOption : '' )
			}
			onClick={ () => onClick && onClick( value ) }
		>
			<span>{ label }</span>
			<span className={ styles.selectedIcon }>
				{ isSelected && <Dashicon icon="yes-alt" /> }
			</span>
		</div>
	);
}
