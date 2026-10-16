<?php
/**
 * Tests for CBT_Theme_JSON and its schema handling.
 *
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_JSON extends WP_UnitTestCase {

	protected static $admin_id;

	public static function wpSetUpBeforeClass( WP_UnitTest_Factory $factory ) {
		self::$admin_id = $factory->user->create(
			array(
				'role' => 'administrator',
			)
		);
	}

	public function test_add_schema_to_data_orders_metadata_first() {
		$data = array(
			'version'  => 3,
			'settings' => array( 'color' => array() ),
			'title'    => 'Test',
		);

		$result = CBT_Theme_JSON_Resolver::add_schema_to_data( $data, 'https://schemas.wp.org/trunk/theme.json' );

		$this->assertSame(
			array( '$schema', 'version', 'title', 'settings' ),
			array_keys( $result )
		);
		$this->assertSame( 'https://schemas.wp.org/trunk/theme.json', $result['$schema'] );
	}

	public function test_add_schema_to_data_without_version_or_title() {
		$result = CBT_Theme_JSON_Resolver::add_schema_to_data(
			array( 'settings' => array() ),
			'https://schemas.wp.org/trunk/theme.json'
		);

		$this->assertSame( array( '$schema', 'settings' ), array_keys( $result ) );
	}

	public function test_style_variation_includes_schema_and_version() {
		wp_set_current_user( self::$admin_id );

		$theme_slug = $this->create_blank_theme();
		$slug       = 'cbt-schema-test';

		$variation = CBT_Theme_JSON::add_theme_json_variation_to_local(
			'variation',
			array(
				'name' => 'Schema Test',
				'slug' => $slug,
			)
		);

		$this->assertNotWPError( $variation );

		$variation_file = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . $slug . '.json';
		$this->assertFileExists( $variation_file );

		$decoded = json_decode( file_get_contents( $variation_file ), true );

		// `$schema` must lead the file, followed by `version` and `title`.
		$this->assertSame( array( '$schema', 'version', 'title' ), array_slice( array_keys( $decoded ), 0, 3 ) );
		$this->assertNotEmpty( $decoded['$schema'] );

		// The variation must declare the same schema as theme.json.
		$theme_json = json_decode( CBT_Theme_JSON_Resolver::export_theme_data( 'current' ), true );
		$this->assertSame( $theme_json['$schema'], $decoded['$schema'] );

		$this->uninstall_theme( $theme_slug );
	}

	private function create_blank_theme() {

		$test_theme_slug = 'cbttesttheme';

		delete_theme( $test_theme_slug );

		$request = new WP_REST_Request( 'POST', '/create-block-theme/v1/create-blank' );
		$request->set_param( 'name', $test_theme_slug );
		$request->set_param( 'description', '' );
		$request->set_param( 'uri', '' );
		$request->set_param( 'author', '' );
		$request->set_param( 'author_uri', '' );
		$request->set_param( 'tags_custom', '' );
		$request->set_param( 'recommended_plugins', '' );

		rest_do_request( $request );

		CBT_Theme_JSON_Resolver::clean_cached_data();

		return $test_theme_slug;
	}

	private function uninstall_theme( $theme_slug ) {
		CBT_Theme_JSON_Resolver::write_user_settings( array() );
		delete_theme( $theme_slug );
	}
}
