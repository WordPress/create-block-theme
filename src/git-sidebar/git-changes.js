import {
    // eslint-disable-next-line
	__experimentalSpacer as Spacer,
    TextareaControl,
    Button,
} from '@wordpress/components';
import { useState } from "@wordpress/element";
import apiFetch from '@wordpress/api-fetch';

export function GitChanges({changes, onCommit}) {
    const [message, setMessage] = useState('');

    if (!Array.isArray(changes) || !changes.length) {
        return <div>No changes to commit.</div>
    }

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

    return <div>
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
    </div>;
}