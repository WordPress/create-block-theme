import { __ } from '@wordpress/i18n';
import { useState, useEffect } from "react";
import './version-control.css';

const VersionControl = () => {
	const [ theme, setTheme ] = useState('');
	const [ status, setStatus ] = useState( false );
	const [ commitMessage, setCommitMessage ] = useState('');
	
	useEffect(() =>{
		// fetch('/wp-json/create-block-theme/v1/themes')
		// 	.then( response => response.json() )
		// 	.then( json => {
		// 		setThemes( json );
		// 	})
		// 	.catch( err => console.log(err));
		fetch('/wp-json/create-block-theme/v1/theme-status')
			.then( response => response.json() )
			.then( json => {
				setTheme( json['current_theme'] );
				if ( json['status'].includes('nothing to commit, working tree clean')) {
					setStatus( false );
				} else {
					setStatus( true );
				}
			})
			.catch( err => console.log(err));
	}, []);

	const handleSubmit = async (event) => {
		event.preventDefault();

		const data = {
			'theme_slug': theme,
			'commit_message': commitMessage
		};
		const res = await fetch(`/wp-json/create-block-theme/v1/pullrequest`, {
				method: 'POST',
				body: JSON.stringify(data)
			})
			.then( response => {
				return response.json()
			})
			.catch( err => console.log(err));

			console.log(res);
	};

	return (
		<>
			<h2>Active Theme: { theme }</h2>
			<p>{ status ? 'Found changes to submit.' : 'No changes found.' }</p>
			<form onSubmit={handleSubmit}>
				{ status ? ( <input 
					type="text" 
					value={commitMessage} 
					onChange={e => setCommitMessage(e.target.value)}
					placeholder='Briefly describe the changes'/> ) : null }
				<input
					type="submit"
					value={ __('Open Pull Request', 'create-block-theme') }
					className="button button-primary"
					disabled={ ! status }
				/>
			</form>
		</>
	)
}

export default VersionControl;