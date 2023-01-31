import { useState, useEffect } from "react";

const VersionControl = () => {
	const [ theme, setTheme ] = useState();
	const [ themes, setThemes ] = useState([]);
	
	useEffect(() =>{
		fetch('/wp-json/create-block-theme/v1/themes')
			.then( response => response.json() )
			.then( json => setThemes( json ) )
			.catch( err => console.log(err));
	}, []);

	// const handleSubmit = async () => {
	// 	const response = await fetch(
	// 	  `https://api.github.com/repos/${repoName}/pulls`,
	// 	  {
	// 		method: "POST",
	// 		headers: {
	// 		  "Content-Type": "application/json",
	// 		  Authorization: `Token YOUR_ACCESS_TOKEN`,
	// 		},
	// 		body: JSON.stringify({
	// 		  title: "my theme",
	// 		  body: pullRequestBody,
	// 		  head: "branch-name",
	// 		  base: "trunk",
	// 		}),
	// 	  }
	// 	);
	// 	const data = await response.json();
	// 	console.log(data);
	// };

	return (
		<div>
			<select value={theme} onChange={(t) => setTheme(t.name) }>
				{themes?.map((theme, index) => (
					<option key={index} value={theme.slug}>
					{theme.name}
					</option>
				))}
			</select>
	  </div>
	)
}

export default VersionControl;