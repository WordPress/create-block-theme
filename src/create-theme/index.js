import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { CreateThemePageLayout } from './page-layout';
import { PageHeader } from './create-theme-header';
import {
	CreateThemeOptions,
	getThemeExportOptions,
} from './create-theme-options';
import { CreateThemeForm } from './create-theme-form';

export default function CreateBlockTheme( { metadata } ) {
	const [ selected, setSelected ] = useState();

	const nonce = document.querySelector( '#nonce' ).value;

	const themeName = metadata.themeName;
	const isChildTheme = metadata.isChildTheme;
	const tags = metadata.tags;

	return (
		<CreateThemePageLayout
			header={ <PageHeader themeName={ themeName } /> }
			sidebar={
				<CreateThemeOptions
					themeName={ themeName }
					isChildTheme={ isChildTheme }
					onChange={ setSelected }
				/>
			}
			main={
				<CreateThemeForm
					themeName={ themeName }
					tags={ tags }
					selectedOption={ selected }
				/>
			}
		/>
	);
}
