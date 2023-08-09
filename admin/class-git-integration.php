<?php

require_once( dirname( __DIR__ ) . '/includes/class-create-block-theme-settings.php' );

class Git_Integration_Admin {
	public $plugin_settings;

	public function __construct() {
		$this->plugin_settings = new Create_Block_Theme_Settings();
		add_action( 'admin_menu', array( $this, 'create_admin_menu' ) );
	}

	public function create_admin_menu() {
		if ( ! wp_is_block_theme() ) {
			return;
		}
		$menu_title = _x( 'Create Block Theme: Git Utilities', 'UI String', 'create-block-theme' );
		$page_title = $menu_title;
		add_menu_page( $page_title, $menu_title, 'edit_theme_options', 'themes-git-integration', array( $this, 'git_settings_page' ) );
	}

	public function git_settings_page() {
		// Add your settings page content here
		$git_url_format = 'https://github.com/username/repository';

		if ( isset( $_POST['delete_git_config'] ) ) {
			$this->delete_settings();
		} elseif ( isset( $_POST['save_git_config'] ) ) {
			$this->save_settings( $_POST );
		}
		$settings       = $this->get_settings();
		$repository_url = $settings['repository_url'];
		$default_branch = $settings['default_branch'];
		$access_token   = $settings['access_token'];
		$author_name    = $settings['author_name'];
		$author_email   = $settings['author_email'];
		?>

		<div class="wrap">
		<h2><?php echo __( 'Create Block Theme: Git Utilities', 'create-block-theme' ); ?></h2>
		<p style="max-width: 400px;">
			<?php echo __( 'Connect your WordPress site themes with a Git repository. You can pull and commit theme changes to the repository.', 'create-block-theme' ); ?>
		</p>
		<div class="theme-form">
			<form action="" method="POST">
				<input type="hidden" name="git_config_form" value="<?php echo wp_create_nonce( 'git_config_form' ); ?>" />

				<?php
				if ( ! empty( $repository_url ) ) {
					echo '<p><h3>' . __( 'Your WordPress site is connected to the git repository.', 'create-block-theme' ) . ' ✅</h3></p>';
					echo "<p style='display:grid;grid-template-columns:minmax(100px,120px) auto'>
                        <strong>" . __( 'Repository URL', 'create-block-theme' ) . ": </strong>
                        <span>$repository_url</span>
                        <strong>" . __( 'Default Branch', 'create-block-theme' ) . ": </strong>
                        <span>$default_branch</span>
                        </p>";

					echo "<input type='submit' name='delete_git_config' class='button-secondary' value='" . __( 'Disconnect Repository', 'create-block-theme' ) . "'/><br/><br/>";

					if ( ! empty( $access_token ) ) {
						echo '<p><strong>' . __( 'Access token configured', 'create-block-theme' ) . ' ✅</strong> <br/>' . __( 'You can still update with a new access token.', 'create-block-theme' ) . '</p>';
					}
				} else {
					?>
				<p><?php echo __( 'Configure the options below to get started.', 'create-block-theme' ); ?></p>
				<div>
					<label for="repository_url"><?php echo __( 'Repository URL', 'create-block-theme' ); ?> (*): </label><br/>
					<input type="text" class="regular-text" name="repository_url" id="repository_url" value="<?php echo sanitize_text_field( $_POST['repository_url'] ); ?>" placeholder="<?php echo $git_url_format; ?>">
				</div>

				<div>
					<label for="default_branch"><?php echo __( 'Default branch', 'create-block-theme' ); ?>: </label><br/>
					<input type="text" class="regular-text" name="default_branch" id="default_branch" placeholder="master / main / trunk">
				</div>
					<?php
				}

				if ( empty( $repository_url ) ) {
					?>
				<p style="max-width: 400px;">
					<?php echo __( 'Following options are required if the repository is private or to commit the theme changes to git repository.', 'create-block-theme' ); ?>
				<br/>
				</p>
				<?php } ?>

				<div>
					<label for="access_token"><?php echo __( 'Access token', 'create-block-theme' ); ?>: </label><br/>
					<input type="text" class="regular-text" name="access_token" id="access_token" placeholder="<?php echo $access_token ? '********' : ''; ?>">
				</div>

				<div>
					<label for="author_name"><?php echo __( 'Author name', 'create-block-theme' ); ?>: </label><br/>
					<input type="text" class="regular-text" name="author_name" id="author_name" value="<?php echo $author_name; ?>">
				</div>

				<div>
					<label for="author_email"><?php echo __( 'Author email', 'create-block-theme' ); ?>: </label><br/>
					<input type="text" class="regular-text" name="author_email" id="author_email" value="<?php echo $author_email; ?>">
				</div>
				<br/>

				<input type="submit" name="save_git_config" class="button-primary" value="<?php echo empty( $repository_url ) ? __( 'Connect Repository', 'create-block-theme' ) : __( 'Update Settings', 'create-block-theme' ); ?>" />
			</form>
		</div>
		</div>
		<?php
	}

	private function get_settings() {
		return $this->plugin_settings->get_settings();
	}

	private function save_settings( $settings ) {
		$repository_url = sanitize_text_field( $settings['repository_url'] );
		$default_branch = sanitize_text_field( $settings['default_branch'] );
		$access_token   = sanitize_text_field( $settings['access_token'] );
		$author_name    = sanitize_text_field( $settings['author_name'] );
		$author_email   = sanitize_text_field( $settings['author_email'] );

		// TODO: try cloning the git repository here.
		// Show error if it fails
		// save the options only if git clone is successful.

		$this->plugin_settings->update_settings(
			array(
				'repository_url' => $repository_url,
				'default_branch' => $default_branch,
				'access_token'   => $access_token,
				'author_name'    => $author_name,
				'author_email'   => $author_email,
			)
		);

		echo '<div class="updated"><p>Settings saved!</p></div>';
	}

	public function delete_settings() {
		$this->plugin_settings->delete_settings();
		// TODO: delete the local clone of the repository
	}
}
