<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Api extends WP_UnitTestCase {

	/**
	 * Helper: invoke a private method on a CBT_Theme_API instance.
	 */
	private function invoke_private( $method ) {
		$instance = new CBT_Theme_API();
		$ref      = new ReflectionMethod( $instance, $method );
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
}
