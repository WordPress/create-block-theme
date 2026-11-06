<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Json extends WP_UnitTestCase {

	public function test_create_variation_rejects_empty_name() {
		$this->assert_variation_name( '', false );
	}

	public function test_create_variation_rejects_whitespace_name() {
		$this->assert_variation_name( '   ', false );
	}

	public function test_create_variation_rejects_empty_slug() {
		$this->assert_variation_name( '!!!', false );
	}

	public function test_create_variation_rejects_html_only_name() {
		$this->assert_variation_name( '<b></b>', false );
	}

	public function test_create_variation_accepts_numeric_name() {
		$this->assert_variation_name( '1', true );
	}

	public function test_create_variation_accepts_zero_name() {
		$this->assert_variation_name( '0', true );
	}

	public function test_create_variation_accepts_normal_name() {
		$this->assert_variation_name( 'My Variation', true );
	}

	private function assert_variation_name( $name, $valid ) {
		$theme_directory = get_temp_dir() . 'cbt-variation-' . wp_generate_uuid4();
		$filter          = static function () use ( $theme_directory ) {
			return $theme_directory;
		};
		add_filter( 'stylesheet_directory', $filter );

		try {
			$api     = new CBT_Theme_API();
			$request = new WP_REST_Request( 'POST', '/create-block-theme/v1/create-variation' );
			$request->set_param( 'name', $name );
			$response = $api->rest_create_variation( $request );

			if ( $valid ) {
				$this->assertInstanceOf( WP_REST_Response::class, $response );
				$this->assertSame( 'SUCCESS', $response->get_data()['status'] );
				$path = $theme_directory . '/styles/' . sanitize_title( $name ) . '.json';
				$this->assertFileExists( $path );
				$this->assertSame( $name, json_decode( file_get_contents( $path ), true )['title'] );
			} else {
				$this->assertWPError( $response );
				$this->assertSame( 'invalid_variation_name', $response->get_error_code() );
				$this->assertSame( 400, $response->get_error_data()['status'] );
				$this->assertDirectoryDoesNotExist( $theme_directory );
			}
			$this->assertFileDoesNotExist( $theme_directory . '/styles/.json' );
		} finally {
			remove_filter( 'stylesheet_directory', $filter );
			if ( file_exists( $theme_directory . '/styles/.json' ) ) {
				unlink( $theme_directory . '/styles/.json' );
			}
			foreach ( glob( $theme_directory . '/styles/*.json' ) as $path ) {
				unlink( $path );
			}
			if ( is_dir( $theme_directory . '/styles' ) ) {
				rmdir( $theme_directory . '/styles' );
				rmdir( $theme_directory );
			}
		}
	}
}
