<?php

class Font_Form_Messages {
	public static function admin_notice_embed_font_success() {
		$theme_name  = wp_get_theme()->get( 'Name' );
		$font_family = '';
		if ( isset( $_POST['selection-data'] ) ) {
			$data       = json_decode( stripslashes( $_POST['selection-data'] ), true );
			$font_names = array();
			foreach ( $data as $font_family ) {
				$font_names[] = $font_family['family'];
			}
			$font_family = implode( ', ', $font_names );
		}
		if ( isset( $_POST['font-name'] ) ) {
			$font_family = $_POST['font-name'];
		}
		?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					// translators: %1$s: Font family, %2$s: Theme name
					printf( esc_html__( '%1$s font added to %2$s theme.', 'create-block-theme' ), esc_html( $font_family ), esc_html( $theme_name ) );
					?>
					<a href="themes.php?page=manage-fonts"><?php printf( esc_html__( 'Manage Fonts', 'create-block-theme' ) ); ?></a>
				</p>
			</div>
		<?php
	}

	public static function admin_notice_embed_font_permission_error() {
		$theme_name  = wp_get_theme()->get( 'Name' );
		$font_family = '';
		if ( isset( $_POST['selection-data'] ) ) {
			$data        = json_decode( stripslashes( $_POST['selection-data'] ), true );
			$font_family = $data['family'];
		}
		if ( isset( $_POST['font-name'] ) ) {
			$font_family = $_POST['font-name'];
		}
		?>
			<div class="notice notice-error is-dismissible">
				<p>
				<?php
				// translators: %1$s: Font family, %2$s: Theme name
				printf( esc_html__( 'Error adding %1$s font to %2$s theme. WordPress lack permissions to write the font assets.', 'create-block-theme' ), esc_html( $font_family ), esc_html( $theme_name ) );
				?>
				</p>
			</div>
		<?php
	}

	public static function admin_notice_embed_font_file_error() {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-error is-dismissible">
				<p>
				<?php
				// translators: %1$s: Font name, %2$s: Theme name
				printf( esc_html__( 'Error adding %1$s font to %2$s theme. The uploaded file is not valid.', 'create-block-theme' ), esc_html( $_POST['font-name'] ), esc_html( $theme_name ) );
				?>
				</p>
			</div>
		<?php
	}

	public static function admin_notice_font_asset_removal_error() {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error removing font asset. WordPress lacks permissions to remove these font assets.', 'create-block-theme' ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

	public static function admin_notice_manage_fonts_permission_error() {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error handling font changes. WordPress lack permissions to manage the theme font assets.', 'create-block-theme' ), esc_html( $theme_name ) ); ?></p>
			</div>
		<?php
	}

	public static function admin_notice_delete_font_success() {
		$theme_name = wp_get_theme()->get( 'Name' );
		?>
			<div class="notice notice-success is-dismissible">
				<p>
				<?php
				// translators: %1$s: Theme name
				printf( esc_html__( 'Font definition removed from your theme (%1$s) theme.json file.', 'create-block-theme' ), esc_html( $theme_name ) );
				?>
				</p>
			</div>
		<?php
	}

	public static function admin_notice_file_edit_error() {
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error: `DISALLOW_FILE_EDIT` cannot be enabled in wp-config.php to make modifications to the theme using this plugin.', 'create-block-theme' ) ); ?></p>
			</div>
		<?php
	}

	public static function admin_notice_user_cant_edit_theme() {
		?>
			<div class="notice notice-error is-dismissible">
				<p><?php printf( esc_html__( 'Error: You do not have sufficient permission to edit the theme.', 'create-block-theme' ) ); ?></p>
			</div>
		<?php
	}
}
