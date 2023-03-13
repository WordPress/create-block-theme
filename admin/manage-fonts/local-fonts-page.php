<?php

class Local_Fonts {
	public static function local_fonts_admin_page() {
		wp_enqueue_script( 'inflate', plugin_dir_url( dirname( __FILE__ ) ) . 'js/lib/inflate.js', array(), '1.0', false );
		wp_enqueue_script( 'unbrotli', plugin_dir_url( dirname( __FILE__ ) ) . 'js/lib/unbrotli.js', array(), '1.0', false );
		wp_enqueue_script( 'lib-font-browser', plugin_dir_url( dirname( __FILE__ ) ) . 'js/lib/lib-font.browser.js', array(), '1.0', false );
		wp_enqueue_script( 'embed-local-font', plugin_dir_url( dirname( __FILE__ ) ) . 'js/embed-local-font.js', array(), '1.0', false );

		function add_type_attribute( $tag, $handle, $src ) {
			// if not your script, do nothing and return original $tag
			if ( 'embed-local-font' !== $handle && 'lib-font-browser' !== $handle ) {
				return $tag;
			}
			// change the script tag by adding type="module" and return it.
			$tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';
			return $tag;
		}

		add_filter( 'script_loader_tag', 'add_type_attribute', 10, 3 );
		?>
		<div class="wrap local-fonts-page">
			<h2><?php _ex( 'Add local fonts to your theme', 'UI String', 'create-block-theme' ); ?></h2>
			<h3>
			<?php
			printf(
				// translators: %1$s: Theme name
				esc_html__( 'Add local fonts assets and font face definitions to your currently active theme (%1$s)', 'create-block-theme' ),
				esc_html( wp_get_theme()->get( 'Name' ) )
			);
			?>
			</h3>
			<form enctype="multipart/form-data" action="" method="POST">
				<table class="form-table">
					<tbody>
						<tr>
							<th scope="row">
								<label for="font-file"><?php _e( 'Font file', 'create-block-theme' ); ?></label>
								<br>
								<small style="font-weight:normal;"><?php _e( '.otf, .ttf, .woff, .woff2 file extensions supported', 'create-block-theme' ); ?></small>
							</th>
							<td>
								<input type="file" accept=".otf, .ttf, .woff, .woff2"  name="font-file" id="font-file" class="upload" required/>
							</td>
						</tr>
						<tr>
							<th><?php _e( 'Font face definition for this font file:', 'create-block-theme' ); ?></th>
							<td>
								<hr/>
							</td>
						</tr>
						<tr>
							<th>
								<label for="font-name"><?php _e( 'Font name', 'create-block-theme' ); ?></label>
							</th>
							<td>
								<input type="text" name="font-name" id="font-name" placeholder="<?php _e( 'Font name', 'create-block-theme' ); ?>" required>
							</td>
						</tr>
						<tr>
							<th>
								<label for="font-style"><?php _e( 'Font style', 'create-block-theme' ); ?></label>
							</th>
							<td>
								<select name="font-style" id="font-style" required>
									<option value="normal">Normal</option>
									<option value="italic">Italic</option>
								</select>
							</td>
						</tr>
						<tr>
							<th>
								<label for="font-weight"><?php _e( 'Font weight', 'create-block-theme' ); ?></label>
							</th>
							<td>
								<input type="text" name="font-weight" id="font-weight" placeholder="<?php _e( 'Font weight', 'create-block-theme' ); ?>" required>
							</td>
						</tr>
					</tbody>
				</table>
				<input type="submit" value="<?php _e( 'Upload local fonts to your theme', 'create-block-theme' ); ?>" class="button button-primary" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce( 'create_block_theme' ); ?>" />
			</form>
		</div>

		<?php
	}
}
