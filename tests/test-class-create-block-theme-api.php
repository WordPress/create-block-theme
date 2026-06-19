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

	public function test_admin_landing_menu_gated_by_can_modify_theme() {
		// CBT_Admin_Landing::create_admin_menu() also returns early when
		// wp_is_block_theme() is false. To prove the cap gate (and not the
		// block-theme guard) is what hides the menu, activate a block theme
		// first, then assert the menu appears when can_modify_theme() passes
		// and disappears once file_mod_allowed denies.
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only — multisite gate is covered by edit_themes super-admin check' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$saved_stylesheet = get_stylesheet();
		$test_theme_slug  = $this->create_blank_theme();
		set_current_screen( 'themes.php' );
		$landing = new CBT_Admin_Landing();

		global $submenu;
		try {
			// Gate open: menu IS registered (proves we cleared wp_is_block_theme()).
			$submenu = array();
			$landing->create_admin_menu();
			$open_slugs = array_column( isset( $submenu['themes.php'] ) ? $submenu['themes.php'] : array(), 2 );
			$this->assertContains(
				'create-block-theme-landing',
				$open_slugs,
				'Landing-page menu should register on a block theme when the user can modify the theme.'
			);

			// Gate denied: menu is NOT registered.
			add_filter( 'file_mod_allowed', '__return_false' );
			$submenu = array();
			$landing->create_admin_menu();
			$denied_slugs = array_column( isset( $submenu['themes.php'] ) ? $submenu['themes.php'] : array(), 2 );
			remove_filter( 'file_mod_allowed', '__return_false' );

			$this->assertNotContains(
				'create-block-theme-landing',
				$denied_slugs,
				'Landing-page menu must NOT register when the user cannot modify the theme.'
			);
		} finally {
			$this->uninstall_theme( $test_theme_slug, $saved_stylesheet );
		}
	}

	public function test_editor_sidebar_enqueue_gated_by_can_modify_theme() {
		// As above, CBT_Editor_Tools::create_block_theme_sidebar_enqueue()
		// returns early when wp_is_block_theme() is false (or when $pagenow
		// is not site-editor.php). Set up a block theme + the right pagenow,
		// then assert the enqueue happens with the gate open and drops out
		// once file_mod_allowed denies.
		if ( is_multisite() ) {
			$this->markTestSkipped( 'single-site only — multisite gate is covered by edit_themes super-admin check' );
		}
		$admin = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin );

		$saved_stylesheet = get_stylesheet();
		$test_theme_slug  = $this->create_blank_theme();

		global $pagenow;
		$saved_pagenow = $pagenow;
		$pagenow       = 'site-editor.php';

		$tools = new CBT_Editor_Tools();
		try {
			// Gate open: script IS enqueued (proves we cleared wp_is_block_theme()).
			wp_dequeue_script( 'create-block-theme-slot-fill' );
			wp_deregister_script( 'create-block-theme-slot-fill' );
			$tools->create_block_theme_sidebar_enqueue();
			$this->assertTrue(
				wp_script_is( 'create-block-theme-slot-fill', 'enqueued' ),
				'Sidebar script should enqueue on a block theme when the user can modify the theme.'
			);

			// Gate denied: script is NOT enqueued.
			wp_dequeue_script( 'create-block-theme-slot-fill' );
			wp_deregister_script( 'create-block-theme-slot-fill' );
			add_filter( 'file_mod_allowed', '__return_false' );
			$tools->create_block_theme_sidebar_enqueue();
			$enqueued_after_deny = wp_script_is( 'create-block-theme-slot-fill', 'enqueued' );
			remove_filter( 'file_mod_allowed', '__return_false' );

			$this->assertFalse(
				$enqueued_after_deny,
				'Sidebar script must NOT enqueue when the user cannot modify the theme.'
			);
		} finally {
			$pagenow = $saved_pagenow;
			wp_dequeue_script( 'create-block-theme-slot-fill' );
			wp_deregister_script( 'create-block-theme-slot-fill' );
			$this->uninstall_theme( $test_theme_slug, $saved_stylesheet );
		}
	}

	/**
	 * Create + activate a blank block theme using the plugin's own
	 * /create-blank endpoint, so wp_is_block_theme() returns true. Mirrors
	 * the helper in tests/test-theme-fonts.php.
	 */
	private function create_blank_theme() {
		$test_theme_slug = 'cbt-api-test-theme';
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

	private function uninstall_theme( $theme_slug, $restore_stylesheet = null ) {
		CBT_Theme_JSON_Resolver::write_user_settings( array() );
		if ( $restore_stylesheet ) {
			switch_theme( $restore_stylesheet );
		}
		delete_theme( $theme_slug );
	}
}
