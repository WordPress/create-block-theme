<?php

require_once( __DIR__ . '/class-create-block-theme-settings.php' );

class Themes_Git_API {
	public $plugin_settings;

	public function __construct() {
		$this->plugin_settings = new Create_Block_Theme_Settings();
	}

	public function get_settings() {
		try {
			$settings = $this->plugin_settings->get_settings();
			// TODO: mask accessToken

			return array( 'settings' => $settings );
		} catch ( \Throwable $th ) {
			return array( 'status' => 'error' );
		}
	}

	public function update_git_repo( $request ) {
		$req_params = $request->get_params();
		$action     = $req_params['action'];
		$repository = $req_params['repository'];
		$theme_slug = $req_params['themeSlug'];
		$theme_name = $req_params['themeName'];

		// do not save any other params
		$repository = array(
			'repositoryUrl' => $repository['repositoryUrl'],
			'defaultBranch' => $repository['defaultBranch'],
			'accessToken'   => $repository['accessToken'],
			'authorName'    => $repository['authorName'],
			'authorEmail'   => $repository['authorEmail'],
		);

		try {
			if ( 'create' === $action ) {
				$this->plugin_settings->add_git_connection(
					array_merge(
						$repository,
						array(
							'themeSlug' => $theme_slug,
							'themeName' => $theme_name,
						)
					)
				);
			} elseif ( 'update' === $action ) {
				$this->plugin_settings->update_git_connection( $repository, $theme_slug );
			} elseif ( 'delete' === $action ) {
				$this->plugin_settings->delete_git_connection( $theme_slug );
			} else {
				return array(
					'status'  => 'error',
					'message' => 'Invalid action.',
				);
			}

			return array( 'status' => 'ok' );
		} catch ( \Throwable $th ) {
			return array(
				'status'  => 'error',
				'message' => $th . __toString(),
			);
		}
	}
}
