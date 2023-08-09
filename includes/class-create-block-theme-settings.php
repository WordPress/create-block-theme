<?php

class Create_Block_Theme_Settings {
	public function init_default_settings() {
		if ( ! get_option( 'repository_url' ) ) {
			update_option( 'repository_url', '' );
		}
		if ( ! get_option( 'default_branch' ) ) {
			update_option( 'default_branch', '' );
		}
		if ( ! get_option( 'access_token' ) ) {
			update_option( 'access_token', '' );
		}
		if ( ! get_option( 'author_name' ) ) {
			update_option( 'author_name', '' );
		}
		if ( ! get_option( 'author_email' ) ) {
			update_option( 'author_email', '' );
		}
	}

	function get_settings() {
		return array(
			'repository_url' => get_option( 'repository_url', '' ),
			'default_branch' => get_option( 'default_branch', '' ),
			'access_token'   => get_option( 'access_token', '' ),
			'author_name'    => get_option( 'author_name', '' ),
			'author_email'   => get_option( 'author_email', '' ),
		);
	}

	function update_settings( $settings ) {
		if ( ! empty( $settings['repository_url'] ) ) {
			update_option( 'repository_url', $settings['repository_url'] );
		}
		if ( ! empty( $settings['default_branch'] ) ) {
			update_option( 'default_branch', $settings['default_branch'] );
		}
		if ( ! empty( $settings['access_token'] ) ) {
			update_option( 'access_token', $settings['access_token'] );
		}
		if ( ! empty( $settings['author_name'] ) ) {
			update_option( 'author_name', $settings['author_name'] );
		}
		if ( ! empty( $settings['author_email'] ) ) {
			update_option( 'author_email', $settings['author_email'] );
		}
	}

	function delete_settings() {
		delete_option( 'repository_url' );
		delete_option( 'default_branch' );
		delete_option( 'access_token' );
		delete_option( 'author_name' );
		delete_option( 'author_email' );
	}
}
