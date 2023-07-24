import { __ } from '@wordpress/i18n';
import {
	// eslint-disable-next-line
	__experimentalSpacer as Spacer,
	// eslint-disable-next-line
	__experimentalText as Text,
	TextControl,
    Button,
    RadioControl,
} from '@wordpress/components';
import { useState } from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';
import { store as noticesStore } from '@wordpress/notices';

export const GitIntegrationForm = function({onChange}) {
    const connectOptions = [
        { label: __( 'with all themes' ), value: 'all_themes' },
        {
            label: __( 'only with the current theme' ),
            value: 'current_theme',
        },
    ];
    const [ repository, setRepository ] = useState( {
		remote_url: '',
		author_name: '',
		author_email: '',
        connection_type: {},
	} );
    const [isLoading, setIsLoading] = useState(false);
    const [error, setError] = useState('Failed to connect repository. Unknown error happened.');

    function handleConnectClick() {
        setIsLoading(true);
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
                setIsLoading(false);
			} )
			.catch( ( error ) => {
				console.log({error})
                setError('Failed to connect repository. Unknown error happened.')
                setIsLoading(false);
			} );
    }

    return <>
        {
            error && <><Text color='red'>{ error }</Text><Spacer /></>
        }
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
            help={"https://personal-access-token@github.com/username/reponame.git"}
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
        <RadioControl
            label={ __( 'Connect repository' ) }
            selected={ repository.connection_type }
            options={ connectOptions }
            onChange={ ( value ) => {
                setRepository({...repository, connection_type: value})
            } }
        />
        <Spacer />
        <Button variant="secondary" onClick={ handleConnectClick } disabled={isLoading}>
            { 
                isLoading ? __( 'Connecting repository...', 'create-block-theme' ) :
                __( 'Connect', 'create-block-theme' ) 
            }
        </Button>
    </>
}
