<?php

class Form_Messages {
	public static function admin_notice_error_theme_name() {
		$class   = 'notice notice-error';
		$message = __( 'Please specify a theme name.', 'create-block-theme' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function admin_notice_error_variation_name() {
		$class   = 'notice notice-error';
		$message = __( 'Please specify a variation name.', 'create-block-theme' );

		printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
	}

	public static function admin_notice_export_success() {
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'Block theme exported successfully!', 'create-block-theme' ); ?></p>
			</div>
		<?php
	}

	public static function admin_notice_save_success() {
		?>
			<div class="notice notice-success is-dismissible">
				<p><?php _e( 'Block theme saved and user customizations cleared!', 'create-block-theme' ); ?></p>
			</div>
		<?php
	}

	public static function admin_notice_blank_success() {
		$theme_name = $_POST['theme']['name'];

		?>
			<div class="notice notice-success is-dismissible">
				<p>
				<?php
				printf(
					// translators: %1$s: Theme name
					esc_html__( 'Blank theme created, head over to Appearance > Themes to activate %1$s', 'create-block-theme' ),
					esc_html( stripslashes( $theme_name ) )
				);
				?>
					</p>
			</div>
		<?php
	}

	public static function admin_notice_variation_success() {
		$theme_name     = wp_get_theme()->get( 'Name' );
		$variation_name = get_stylesheet_directory() . DIRECTORY_SEPARATOR . 'styles' . DIRECTORY_SEPARATOR . $_POST['theme']['variation_slug'] . '.json';
		?>
			<div class="notice notice-success is-dismissible">
				<p>
				<?php
				printf(
					// translators: %1$s: Theme name, %2$s: Variation name
					esc_html__( 'Your variation of %1$s has been created successfully. The new variation file is in %2$s', 'create-block-theme' ),
					esc_html( $theme_name ),
					esc_html( $variation_name )
				);
				?>
					</p>
			</div>
		<?php
	}

	public static function admin_notice_error_theme_file_permissions() {
		$theme_name = wp_get_theme()->get( 'Name' );
		$theme_dir  = get_stylesheet_directory();
		?>
			<div class="notice notice-error">
				<p>
				<?php
				printf(
					// translators: %1$s: Theme name, %2$s: Theme directory
					esc_html__( 'Your theme ( %1$s ) directory ( %2$s ) is not writable. Please check your file permissions.', 'create-block-theme' ),
					esc_html( $theme_name ),
					esc_html( $theme_dir )
				);
				?>
					</p>
			</div>
		<?php
	}

	public static function admin_notice_error_themes_file_permissions() {
		$themes_dir = get_theme_root();
		?>
			<div class="notice notice-error">
				<p>
				<?php
				printf(
					// translators: %1$s: Theme directory
					esc_html__( 'Your themes directory ( %1$s ) is not writable. Please check your file permissions.', 'create-block-theme' ),
					esc_html( $themes_dir )
				);
				?>
					</p>
			</div>
		<?php
	}
}
