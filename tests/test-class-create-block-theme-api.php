<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Api extends WP_UnitTestCase {

	/**
	 * Helper: invoke a private method on a CBT_Theme_API instance.
	 */
	private function invoke_private( $method ) {
		$ref_class = new ReflectionClass( CBT_Theme_API::class );
		$instance  = $ref_class->newInstanceWithoutConstructor();
		$ref       = new ReflectionMethod( $instance, $method );
		$ref->setAccessible( true );
		return $ref->invoke( $instance );
	}

	public function test_admin_can_modify_theme_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$this->assertTrue( $this->invoke_private( 'can_modify_theme' ) );
	}

	public function test_editor_cannot_modify_theme_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only' );
		}
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$this->assertFalse( $this->invoke_private( 'can_modify_theme' ) );
	}

	public function test_anonymous_cannot_modify_theme() {
		wp_set_current_user( 0 );
		$this->assertFalse( $this->invoke_private( 'can_modify_theme' ) );
	}

	public function test_admin_blocked_when_file_mods_disallowed() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		add_filter( 'cbt_file_mods_allowed', '__return_false' );
		$result = $this->invoke_private( 'can_modify_theme' );
		remove_filter( 'cbt_file_mods_allowed', '__return_false' );

		$this->assertFalse( $result );
	}

	public function test_file_mods_allowed_returns_true_by_default() {
		$this->assertTrue( $this->invoke_private( 'file_mods_allowed' ) );
	}

	public function test_file_mods_allowed_filter_can_disable() {
		add_filter( 'cbt_file_mods_allowed', '__return_false' );
		$result = $this->invoke_private( 'file_mods_allowed' );
		remove_filter( 'cbt_file_mods_allowed', '__return_false' );

		$this->assertFalse( $result );
	}

	public function test_file_mods_allowed_respects_core_file_mod_allowed_filter() {
		// WordPress Core's canonical `file_mod_allowed` filter is the
		// mechanism hosts and security plugins use to disable file mods
		// globally. file_mods_allowed() must honour it.
		add_filter( 'file_mod_allowed', '__return_false' );
		$result = $this->invoke_private( 'file_mods_allowed' );
		remove_filter( 'file_mod_allowed', '__return_false' );

		$this->assertFalse( $result );
	}

	public function test_save_endpoint_rejects_when_file_mods_disallowed() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		add_filter( 'cbt_file_mods_allowed', '__return_false' );
		$request  = new WP_REST_Request( 'POST', '/create-block-theme/v1/save' );
		$response = rest_get_server()->dispatch( $request );
		remove_filter( 'cbt_file_mods_allowed', '__return_false' );

		if ( is_multisite() ) {
			revoke_super_admin( $admin );
		}
		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}

	public function test_save_endpoint_rejects_anonymous() {
		wp_set_current_user( 0 );
		$request  = new WP_REST_Request( 'POST', '/create-block-theme/v1/save' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 401, $response->get_status() );
	}

	public function test_font_families_endpoint_still_accessible_to_admin() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		// /font-families is a GET and is NOT gated by can_modify_theme().
		// Even with file mods disallowed, it should still respond (not 403).
		add_filter( 'cbt_file_mods_allowed', '__return_false' );
		$request  = new WP_REST_Request( 'GET', '/create-block-theme/v1/font-families' );
		$response = rest_get_server()->dispatch( $request );
		remove_filter( 'cbt_file_mods_allowed', '__return_false' );

		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'SUCCESS', $response->get_data()['status'] );
	}

	public function test_super_admin_can_modify_theme_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'requires multisite — set WP_TESTS_MULTISITE=1' );
		}
		$super = $this->factory->user->create( array( 'role' => 'administrator' ) );
		grant_super_admin( $super );
		wp_set_current_user( $super );

		$this->assertTrue( $this->invoke_private( 'can_modify_theme' ) );

		revoke_super_admin( $super );
	}

	public function test_subsite_admin_cannot_modify_theme_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'requires multisite — set WP_TESTS_MULTISITE=1' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		// Explicitly NOT a super-admin.
		wp_set_current_user( $admin );

		$this->assertFalse( $this->invoke_private( 'can_modify_theme' ) );
	}

	public function test_save_endpoint_rejects_subsite_admin_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'requires multisite — set WP_TESTS_MULTISITE=1' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$request  = new WP_REST_Request( 'POST', '/create-block-theme/v1/save' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 403, $response->get_status() );
		$this->assertSame( 'rest_forbidden', $response->get_data()['code'] );
	}
}
