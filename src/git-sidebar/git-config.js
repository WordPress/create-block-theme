import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	TextControl,
} from '@wordpress/components';
import { useState } from "@wordpress/element";

export const GitIntegrationForm = function() {
    const [ repository, setRepository ] = useState( {
		url: '',
		author_name: '',
		author_email: '',
	} );

    return <>
        <Text>
            { __(
                'Enter a repository Url to connect with current theme.',
                'create-block-theme'
            ) }
        </Text>
        <Spacer />
        <TextControl
            label={ __( 'Repository URL', 'create-block-theme' ) }
            value={ repository.url }
            onChange={ ( value ) =>
                setRepository( { ...repository, url: value } )
            }
        />
        <TextControl
            label={ __( 'Author Name', 'create-block-theme' ) }
            value={ repository.author_name }
            onChange={ ( value ) =>
                setRepository( { ...repository, author_name: value } )
            }
        />
        <TextControl
            label={ __( 'Author Email', 'create-block-theme' ) }
            value={ repository.author_email }
            onChange={ ( value ) =>
                setRepository( { ...repository, author_email: value } )
            }
        />
    </>
}
