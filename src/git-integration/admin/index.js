import { __ } from '@wordpress/i18n';
import styles from './styles.module.css';
import {
	Button,
	// eslint-disable-next-line
	__experimentalInputControl as InputControl,
	// eslint-disable-next-line
	__experimentalText as Text,
	RadioControl,
	Notice,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { Fragment, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export default function GitIntegrationAdminPage() {
	const [ view, setView ] = useState( 'all-connections' );
	const [ repos, setRepos ] = useState( [] );
	const [ selectedRepo, setSelectedRepo ] = useState();
	const [ allThemesConnected, setAllThemesConnected ] = useState( false );
	const [ activeThemeConnected, setActiveThemeConnected ] = useState( false );
	const [ theme, setTheme ] = useState( {
		name: '',
	} );
	useSelect( ( select ) => {
		const themeData = select( 'core' ).getCurrentTheme();
		if ( ! themeData ) return;
		setTheme( {
			name: themeData.name.raw,
			slug: themeData.textdomain,
		} );
	}, [] );

	useEffect( () => {
		fetchRepos();
	}, [] );

	useEffect( () => {
		( repos || [] )
			.filter(
				( repo ) => ! repo.themeSlug || repo.themeSlug === theme.slug
			)
			.forEach( ( repo ) => {
				if ( ! repo.themeSlug ) {
					setAllThemesConnected( true );
				} else if ( repo.themeSlug === theme.slug ) {
					setActiveThemeConnected( true );
				}
			} );
	}, [ repos, theme.slug ] );

	const fetchRepos = () => {
		apiFetch( {
			path: '/create-block-theme/v1/settings',
			method: 'GET',
			headers: {
				'Content-Type': 'application/json',
			},
		} ).then( ( response ) => {
			// TODO: sort the result
			setRepos( response.settings.connected_repos );
		} );
	};

	const onChange = ( action, data ) => {
		if ( [ 'canceled', 'success' ].includes( action ) ) {
			setView( 'connections' );
			fetchRepos();
		} else if ( action === 'update' ) {
			setSelectedRepo( data );
			setView( 'new-connection' );
		} else {
			setSelectedRepo();
			setView( 'new-connection' );
		}
	};

	return (
		<div className={ styles.pageLayout }>
			<div className={ styles.pageHeader }>
				<h1 className="wp-heading-inline">
					{ __(
						'Create Block Theme: Git Utilities',
						'create-block-theme'
					) }
				</h1>
				<p>
					{ __(
						'Connect your WordPress site themes with Git repositories. Pull theme changes from the connected repository and commit theme changes to the repository.',
						'create-block-theme'
					) }
				</p>
			</div>
			<div className={ styles.pageContainer }>
				{ view === 'new-connection' ? (
					<div className={ styles.repoForm }>
						<GitConfigurationForm
							repository={ selectedRepo }
							theme={ theme }
							nonce={ '' }
							allThemesConnected={ allThemesConnected }
							activeThemeConnected={ activeThemeConnected }
							onChange={ onChange }
						/>
					</div>
				) : (
					<div className={ styles.connectedRepos }>
						<ConnectedRepositories
							repos={ repos }
							onChange={ onChange }
						/>
					</div>
				) }
			</div>
		</div>
	);
}

function GitConfigurationForm( {
	repository,
	theme,
	nonce,
	allThemesConnected,
	activeThemeConnected,
	onChange,
} ) {
	repository = repository || {};
	const editMode = repository.repositoryUrl;
	const [ error, setError ] = useState( '' );
	const [ config, setConfig ] = useState( {
		connectionType: ! allThemesConnected ? 'all-themes' : 'active-theme',
		repositoryUrl: repository.repositoryUrl || '',
		defaultBranch: repository.defaultBranch || '',
		accessToken: '',
		authorName: repository.authorName || '',
		authorEmail: repository.authorEmail || '',
	} );

	const validateForm = () => {
		// TODO: add validations
		if ( ! config.repositoryUrl ) {
			return 'Repository Url is required.';
		}

		if ( allThemesConnected && config.connectionType === 'all-themes' ) {
			return 'A repository is already connected with all themes.';
		} else if (
			activeThemeConnected &&
			config.connectionType === 'active-theme'
		) {
			return `A repository is already connected with the active theme ${ theme.name }`;
		}
	};

	const onSubmit = ( action ) => {
		let postData = {};
		if ( action === 'create' ) {
			const formError = validateForm();
			setError( formError );
			if ( formError ) {
				return;
			}
			postData = {
				action,
				repository: config,
				themeSlug:
					config.connectionType === 'all-themes' ? '' : theme.slug,
				themeName:
					config.connectionType === 'all-themes' ? '' : theme.name,
			};
		} else if ( action === 'update' || action === 'delete' ) {
			postData = {
				action,
				repository: config,
				themeSlug: repository.themeSlug,
			};
		}

		apiFetch( {
			path: '/create-block-theme/v1/update-git-connection',
			method: 'POST',
			data: postData,
			headers: {
				'Content-Type': 'application/json',
			},
		} )
			.then( ( response ) => {
				if ( response.status === 'error' ) {
					setError( 'Failed to connect repository.' );
					return;
				}
				onChange( 'success' );
			} )
			.catch( () => {
				setError( 'Failed to connect repository.' );
			} );
	};

	return (
		<div id="git-integration-form" className="theme-form">
			<h3>
				{ editMode
					? __( 'Edit repository connection', 'create-block-theme' )
					: __( 'Connect a repository', 'create-block-theme' ) }
			</h3>
			<input type="hidden" name="nonce" value={ nonce } />
			{ editMode ? (
				<>
					<div className={ styles.connectionDetails }>
						<strong>Repository Url</strong>
						<span>: { repository.repositoryUrl }</span>
						<strong>Default Branch</strong>
						<span>: { repository.defaultBranch }</span>
						<strong>Connected Theme</strong>
						<span>
							{ ': ' +
								( config.connectionType === 'all-themes'
									? __( 'All Themes', 'create-block-theme' )
									: `${ theme.name }` ) }
						</span>
					</div>
					<br />
					<Button
						variant="secondary"
						onClick={ () => onSubmit( 'delete' ) }
					>
						{ __( 'Disconnect repository', 'create-block-theme' ) }
					</Button>
				</>
			) : (
				<>
					<InputControl
						label={ __( 'Repository Url', 'create-block-theme' ) }
						required
						value={ config.repositoryUrl }
						placeholder="https://github.com/username/repository.git"
						onChange={ ( value ) =>
							setConfig( { ...config, repositoryUrl: value } )
						}
					/>
					<br />
					<InputControl
						label={ __( 'Default Branch', 'create-block-theme' ) }
						required
						value={ config.defaultBranch }
						placeholder="main / master / trunk"
						onChange={ ( value ) =>
							setConfig( { ...config, defaultBranch: value } )
						}
					/>
					<br />
					<RadioControl
						selected={ config.connectionType }
						options={ [
							{
								label: __(
									'Connect with all themes',
									'create-block-theme'
								),
								value: 'all-themes',
							},
							{
								label:
									__(
										'Connect with active theme',
										'create-block-theme'
									) + ` ${ theme.name }`,
								value: 'active-theme',
							},
						] }
						onChange={ ( value ) =>
							setConfig( {
								...config,
								connectionType: value,
							} )
						}
					/>
				</>
			) }
			<p>
				{ __(
					'Following options are required if the repository is private or if you want to commit theme changes to the repository.',
					'create-block-theme'
				) }
			</p>
			<InputControl
				label={ __( 'Access Token', 'create-block-theme' ) }
				required
				value={ config.accessToken }
				placeholder={ repository.accessToken ? '*****' : '' }
				onChange={ ( value ) =>
					setConfig( { ...config, accessToken: value } )
				}
			/>
			<br />
			<InputControl
				label={ __( 'Author Name', 'create-block-theme' ) }
				required
				value={ config.authorName }
				onChange={ ( value ) =>
					setConfig( { ...config, authorName: value } )
				}
			/>
			<br />
			<InputControl
				label={ __( 'Author Email', 'create-block-theme' ) }
				required
				value={ config.authorEmail }
				onChange={ ( value ) =>
					setConfig( { ...config, authorEmail: value } )
				}
			/>
			<br />
			{ error && (
				<>
					<Notice
						isDismissible={ false }
						status="error"
						className={ styles.notice }
					>
						{ error }
					</Notice>
					<br />
				</>
			) }
			<div>
				<Button
					variant="primary"
					onClick={ () => onSubmit( editMode ? 'update' : 'create' ) }
				>
					{ editMode
						? __(
								'Update repository connection',
								'create-block-theme'
						  )
						: __( 'Connect Repository', 'create-block-theme' ) }
				</Button>
				<Button
					variant="secondary"
					onClick={ () => onChange( 'canceled' ) }
					style={ { marginLeft: '0.5rem' } }
				>
					Cancel
				</Button>
			</div>
			<br />
		</div>
	);
}

function ConnectedRepositories( { repos, onChange } ) {
	return (
		<div>
			<div className={ styles.sectionHeader }>
				<h3>
					{ __( 'Connected repositories', 'create-block-theme' ) }
				</h3>
				<Button variant="primary" onClick={ () => onChange( 'new' ) }>
					{ ! repos.length
						? __( 'Connect a Repository', 'create-block-theme' )
						: __(
								'Connect Another Repository',
								'create-block-theme'
						  ) }
				</Button>
			</div>
			{ ! Array.isArray( repos ) || ! repos.length ? (
				<>
					<p>
						{ __(
							'Site is not connected to any repository.',
							'create-block-theme'
						) }
					</p>
				</>
			) : (
				<>
					<div className={ styles.repoTable }>
						<div className={ styles.connectedRepoHeader }></div>
						<div className={ styles.connectedRepoHeader }>
							Repository Url
						</div>
						<div className={ styles.connectedRepoHeader }>
							Default Branch
						</div>
						<div className={ styles.connectedRepoHeader }>
							Access Token
						</div>
						<div className={ styles.connectedRepoHeader }>
							Author
						</div>
						<div className={ styles.connectedRepoHeader }>
							Connected Theme
						</div>
						{ repos.map( ( repo, index ) => (
							<Fragment key={ index }>
								<div className={ styles.connectedRepoItem }>
									<div
										className={ styles.editLink }
										onClick={ () =>
											onChange( 'update', repo )
										}
										onKeyUp={ () =>
											onChange( 'update', repo )
										}
										tabIndex="0"
										role="link"
									>
										Edit
									</div>
								</div>
								<div className={ styles.connectedRepoItem }>
									{ repo.repositoryUrl }
								</div>
								<div className={ styles.connectedRepoItem }>
									{ repo.defaultBranch }
								</div>
								<div className={ styles.connectedRepoItem }>
									{ repo.accessToken ? '✅' : '' }
								</div>
								<div className={ styles.connectedRepoItem }>
									{ repo.authorName }
									{ repo.authorEmail
										? ` <${ repo.authorEmail }>`
										: '' }
								</div>
								<div className={ styles.connectedRepoItem }>
									{ repo.themeName || 'All Themes' }
								</div>
							</Fragment>
						) ) }
					</div>
				</>
			) }
		</div>
	);
}
