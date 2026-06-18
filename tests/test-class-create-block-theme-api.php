<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Api extends WP_UnitTestCase {

	public function test_admin_can_modify_theme_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$this->assertTrue( CBT_Theme_API::can_modify_theme() );
	}

	public function test_editor_cannot_modify_theme_on_single_site() {
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only' );
		}
		$editor = $this->factory->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );

		$this->assertFalse( CBT_Theme_API::can_modify_theme() );
	}

	public function test_anonymous_cannot_modify_theme() {
		wp_set_current_user( 0 );
		$this->assertFalse( CBT_Theme_API::can_modify_theme() );
	}

	public function test_admin_blocked_by_core_file_mod_allowed_filter() {
		// WordPress Core's canonical `file_mod_allowed` filter is the
		// mechanism hosts and security plugins use to disable file
		// modifications globally. can_modify_theme() must honour it.
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		add_filter( 'file_mod_allowed', '__return_false' );
		$result = CBT_Theme_API::can_modify_theme();
		remove_filter( 'file_mod_allowed', '__return_false' );

		$this->assertFalse( $result );
	}

	public function test_save_endpoint_rejects_when_file_mod_allowed_filter_returns_false() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );
		if ( is_multisite() ) {
			grant_super_admin( $admin );
		}

		add_filter( 'file_mod_allowed', '__return_false' );
		$request  = new WP_REST_Request( 'POST', '/create-block-theme/v1/save' );
		$response = rest_get_server()->dispatch( $request );
		remove_filter( 'file_mod_allowed', '__return_false' );

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
		// Even with file mods disallowed by the canonical filter, it should
		// still respond (not 403).
		add_filter( 'file_mod_allowed', '__return_false' );
		$request  = new WP_REST_Request( 'GET', '/create-block-theme/v1/font-families' );
		$response = rest_get_server()->dispatch( $request );
		remove_filter( 'file_mod_allowed', '__return_false' );

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

		$this->assertTrue( CBT_Theme_API::can_modify_theme() );

		revoke_super_admin( $super );
	}

	public function test_subsite_admin_cannot_modify_theme_on_multisite() {
		if ( ! is_multisite() ) {
			$this->markTestSkipped( 'requires multisite — set WP_TESTS_MULTISITE=1' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		// Explicitly NOT a super-admin — `edit_themes` is super-admin-only on multisite.
		wp_set_current_user( $admin );

		$this->assertFalse( CBT_Theme_API::can_modify_theme() );
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

	public function test_admin_landing_menu_not_registered_when_cannot_modify_theme() {
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		add_filter( 'file_mod_allowed', '__return_false' );

		$landing = new CBT_Admin_Landing();
		set_current_screen( 'themes.php' );
		global $submenu;
		$submenu = array();
		$landing->create_admin_menu();
		$menu_after_deny = isset( $submenu['themes.php'] ) ? $submenu['themes.php'] : array();

		remove_filter( 'file_mod_allowed', '__return_false' );

		$slugs = array_column( $menu_after_deny, 2 );
		$this->assertNotContains(
			'create-block-theme-landing',
			$slugs,
			'Landing-page menu must NOT be registered when the user cannot modify the theme.'
		);
	}

	public function test_editor_sidebar_enqueue_drops_when_cannot_modify_theme() {
		// Sub-site admin on multisite or DISALLOW_FILE_MODS site: the sidebar
		// JS should not enqueue. Simulate by gating via file_mod_allowed.
		global $pagenow;
		$saved_pagenow = $pagenow;
		$pagenow       = 'site-editor.php';

		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		add_filter( 'file_mod_allowed', '__return_false' );

		$tools = new CBT_Editor_Tools();
		// Reset any prior enqueue state.
		wp_dequeue_script( 'create-block-theme-slot-fill' );
		$tools->create_block_theme_sidebar_enqueue();
		$enqueued = wp_script_is( 'create-block-theme-slot-fill', 'enqueued' );

		remove_filter( 'file_mod_allowed', '__return_false' );
		$pagenow = $saved_pagenow;

		$this->assertFalse(
			$enqueued,
			'Sidebar script must NOT be enqueued when the user cannot modify the theme.'
		);
	}
}
