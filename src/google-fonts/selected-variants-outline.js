import { __, _n } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import { bytesToSize } from './utils';
import './selected-variants-outline.css';
import { Button } from '@wordpress/components';
import { trash } from '@wordpress/icons';

function SelectedVariantsOutline( { selectionData, removeVariant } ) {
	const [ fileSizes, setFileSizes ] = useState( {} );

	const flatSelectionData = Object.keys( selectionData )
		.map( ( family ) => {
			return selectionData[ family ].faces.map( ( face ) => {
				return {
					family,
					weight: face.weight,
					style: face.style,
					src: face.src,
				};
			} );
		} )
		.flat();

	useEffect( () => {
		const promises = flatSelectionData.map( ( face ) => {
			return fetch( face.src, { method: 'HEAD' } );
		} );

		Promise.all( promises ).then( ( responses ) => {
			const sizes = {};
			responses.forEach( ( response ) => {
				sizes[ response.url ] =
					response.headers.get( 'content-length' );
			} );
			setFileSizes( sizes );
		} );
	}, [ selectionData ] );

	const variantsCount = Object.keys( selectionData ).reduce(
		( acc, family ) => {
			return acc + selectionData[ family ].faces.length;
		},
		0
	);

	const getFileSize = ( url ) => {
		return fileSizes[ url ] ? bytesToSize( fileSizes[ url ] ) : null;
	};

	const totalSize = bytesToSize(
		flatSelectionData.reduce( ( acc, face ) => {
			return acc + parseInt( fileSizes[ face.src ] );
		}, 0 )
	);

	return (
		<div className="variants-outline">
			<h2>{ __( 'Selected Variants', 'create-block-theme' ) }</h2>
			<h3>{ selectionData.family }</h3>

			{ !! selectionData && (
				<>
					<div className="variant-row">
						<div>{ __( 'Variant', 'create-block-theme' ) }</div>
						<div>{ __( 'File Size', 'create-block-theme' ) }</div>
						<div></div>
					</div>
					<div className="variants-list">
						{ Object.keys( selectionData ).map( ( key, i ) => (
							<div
								className="variants-family"
								key={ `variants-family-${ i }` }
							>
								<p>{ selectionData[ key ].family }</p>
								{ selectionData[ key ].faces.map(
									( face, ii ) => (
										<div
											className="variant-row"
											key={ `selected-variant-${ ii }` }
										>
											<div>
												{ face.weight } { face.style }
											</div>
											<div>
												{ getFileSize( face.src ) }
											</div>
											<div>
												<Button
													onClick={ () =>
														removeVariant(
															selectionData[ key ]
																.family,
															face.weight,
															face.style
														)
													}
													icon={ trash }
													iconSize={ 15 }
													isSmall
												/>
											</div>
										</div>
									)
								) }
							</div>
						) ) }
					</div>
				</>
			) }

			<div className="variants-total">
				<div>
					{ variantsCount }{ ' ' }
					{ _n(
						'Variant',
						'Variants',
						variantsCount,
						'create-block-theme'
					) }
				</div>
				<div>{ totalSize }</div>
			</div>
		</div>
	);
}

export default SelectedVariantsOutline;
