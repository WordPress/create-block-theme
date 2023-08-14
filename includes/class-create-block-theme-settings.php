<?php

class Create_Block_Theme_Settings {
	function get_settings() {
		// delete_option( 'connected_repos' );
		return array(
			'connected_repos' => get_option( 'connected_repos', array() ),
		);
	}

	function add_git_connection( $connection ) {
		$repos = get_option( 'connected_repos', array() );
		array_push( $repos, $connection );
		update_option( 'connected_repos', $repos );
	}

	function update_git_connection( $connection, $theme_slug ) {
		// TODO: update only fields with values. keep the old values if value is not set
		$repos = get_option( 'connected_repos', array() );
		$repos = array_map(
			function( $repo ) use ( $connection, $theme_slug ) {
				if ( $repo['themeSlug'] === $theme_slug ) {
					return array_merge( $repo, $connection );
				}
				return $repo;
			},
			$repos
		);
		update_option( 'connected_repos', $repos );
	}

	function delete_git_connection( $theme_slug ) {
		$repos = get_option( 'connected_repos', array() );
		$repos = array_filter(
			$repos,
			function( $repo ) use ( $theme_slug ) {
				return $repo['themeSlug'] !== $theme_slug;
			}
		);
		update_option( 'connected_repos', $repos );
	}

	function delete_settings() {
		delete_option( 'connected_repos' );
	}
}
