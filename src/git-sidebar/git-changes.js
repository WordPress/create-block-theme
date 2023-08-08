import {
    // eslint-disable-next-line
	__experimentalSpacer as Spacer,
    TextareaControl,
    Button,
    PanelBody,
} from '@wordpress/components';
import { useState } from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';

export function GitChanges({config, changes, onCommit}) {
    const [message, setMessage] = useState('');

    function handleCommitClick() {
        apiFetch( {
			path: '/create-block-theme/v1/commit-changes',
			method: 'POST',
			data: {message},
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( (response) => {
                console.log({response})
                onCommit(true);
			} )
			.catch( ( error ) => {
				// console.log({error})
			} );
    }

    return <PanelBody title={ __( 'Theme changes' ) }>
        { !Array.isArray(changes) || !changes.length ?
        <div>No changes to commit.</div> :
        <div>
            {
                changes.map((change, i) => <div key={i}>
                    {change.modifier} - {change.file}
                </div>)
            }
            <Spacer />
            <TextareaControl
                label={ __( 'Commit message', 'create-block-theme' ) }
                value={ message }
                onChange={ ( value ) =>
                    setMessage(value)
                }
                placeholder={ __(
                    'A short description of the changes',
                    'create-block-theme'
                ) }
            />
            <Spacer />
            <Button variant="secondary" onClick={ handleCommitClick }>
                { __( 'Commit', 'create-block-theme' ) }
            </Button>
        </div>
    }
    </PanelBody>;
}