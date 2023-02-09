import { __ } from '@wordpress/i18n';
import { useState, useEffect } from "react";

const VersionControl = () => {
	const [ theme, setTheme ] = useState({});
	const [ themes, setThemes ] = useState([]);
	
	useEffect(() =>{
		fetch('/wp-json/create-block-theme/v1/themes')
			.then( response => response.json() )
			.then( json => {
				setThemes( json );
			})
			.catch( err => console.log(err));
	}, []);

	const handleSubmit = async (event) => {
		event.preventDefault();
		console.log(theme);

		const data = await fetch(`/wp-json/create-block-theme/v1/themes-pr?theme_slug=${theme.slug}&isChild=${theme.isChild}`)
			.then( response => response.json() )
			.catch( err => console.log(err));

			console.log(data);
		
		// const response = await fetch(
		//   `https://api.github.com/repos/${repoName}/pulls`,
		//   {
		// 	method: "POST",
		// 	headers: {
		// 	  "Content-Type": "application/json",
		// 	  Authorization: `Token YOUR_ACCESS_TOKEN`,
		// 	},
		// 	body: JSON.stringify({
		// 	  title: "my theme",
		// 	  body: pullRequestBody,
		// 	  head: "branch-name",
		// 	  base: "trunk",
		// 	}),
		//   }
		// );
		// const data = await response.json();
		// console.log(data);
	};

	return (
		<form onSubmit={handleSubmit}>
			{ themes && (
				<select value={theme?.slug} onChange={(e) => {
					setTheme( themes.find( t => t.slug === e.target.value ));
				}}>
					{themes?.map((t, index) => (
						<option key={index} value={t.slug}>
						{ t.name }
						</option>
					))}
				</select>
			)}
			<input
				type="submit"
				value={ __('Submit', 'create-block-theme') }
				className="button button-primary"
				// disabled={ selectedVariants.length === 0 }
			/>
	  </form>
	)
}

export default VersionControl;