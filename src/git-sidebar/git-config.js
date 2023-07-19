import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	TextControl,
    Button,
} from '@wordpress/components';
import { useState } from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';

export const GitIntegrationForm = function({onChange}) {
    // const { createErrorNotice } = useDispatch( noticesStore );
    const [ repository, setRepository ] = useState( {
		remote_url: '',
		author_name: '',
		author_email: '',
	} );

    function handleConnectClick() {
        apiFetch( {
			path: '/create-block-theme/v1/connect-git',
			method: 'POST',
			data: repository,
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( (response) => {
                console.log({response})
                onChange(true);
			} )
			.catch( ( error ) => {
				// console.log({error})
			} );
    }

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
            value={ repository.remote_url }
            onChange={ ( value ) =>
                setRepository( { ...repository, remote_url: value } )
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
        <Spacer />
        <Button variant="secondary" onClick={ handleConnectClick }>
            { __( 'Connect', 'create-block-theme' ) }
        </Button>
    </>
}
