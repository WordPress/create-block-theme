<?php

require_once( __DIR__ . '/git-wrapper.php' );

class Git_Themes {
    private static $git;

	public static function create_admin_page() {
        self::$git = Git_Wrapper::getGit();
        WP_Filesystem();

        ?>
        <div class="wrap">
            <h1>Git integration for themes</h1>

            <?php
            // TODO: also a min git version check??
            list($git_available, $git_version) = self::is_git_available();
            if ( !$git_available ) {
                echo "<p>ERROR: Git not available on the server.</p>";
                echo "<p>Install git to use this feature.</p>";
                return;
            }
            
            echo "<p>Git version on the server: $git_version</p>";
            if (isset($_POST['git_config_form'])) {
                self::save_git_config($_POST);
            }

            list($initialized, $git_url) = self::is_git_initialized();
            if ( !$initialized ) {
                self::show_git_config_form();
                return;
            }

            self::show_git_config(array(
                "git_url" => $git_url,
            ));

            if (isset($_POST['git_commit_form'])) {
                self::commit_and_push($_POST);
            } else {
                self::show_theme_changes();
                self::show_commit_form();
            }

            ?>

        </div>

        <?php
	}

    private static function is_git_available() {
        try {
            $git_version = self::$git->get_version();
            if ( empty( $git_version ) ) {
                return array( false, '0' );
            }

            return array( true, $git_version );
        } catch (\Throwable $th) {
            return array( false, '0' );
        }
    }

    private static function is_git_initialized() {
        global $wp_filesystem;
        $git_initialized = $wp_filesystem->exists(CREATE_BLOCK_THEME_GIT_DIR.'/.git');
        $remote_url = self::$git -> get_remote_url();
        if (!$git_initialized || empty($remote_url)) {
            return array(false, '');
        }
        return array(true, $remote_url);
    }

    private static function show_git_config_form() {
        $git_url_format = 'https://personal-access-token@github.com/username/repo-name.git';
        ?>
        <form action="" method="POST">
            <input type="hidden" name="git_config_form" value="<?php echo wp_create_nonce( 'git_config_form' ); ?>" />
            <div>
                <label for="git_url">Repository URL: </label>
                <input type="text" class="regular-text" name="git_url" id="git_url" placeholder="<?php echo $git_url_format; ?>">
                <p style="font-size:12px;">Url format: <code style="font-size: inherit;"><?php echo $git_url_format; ?></code></p>
            </div>

            <div>
                <label for="author_name">Author name: </label>
                <input type="text" class="regular-text" name="author_name" id="author_name">
            </div>

            <div>
                <label for="author_email">Author email: </label>
                <input type="text" class="regular-text" name="author_email" id="author_email">
            </div>

            <input type="submit" class="button-primary" value="Connect" />
        </form>
        <?php
    }

    private static function save_git_config($post_args) {
        $git_url = $post_args['git_url'];
        $author_name = $post_args['author_name'];
        $author_email = $post_args['author_email'];

        self::$git->init($author_name, $author_email);
		self::$git->add_remote_url( $git_url );
        // TODO: it is taking long time sometimes resulting timeouts.
        // May be make it a background process??
		self::$git->fetch_ref();
    }

    private static function show_git_config($config) {
        echo '<p>Connected git URL: '.$config['git_url'].'</p>';

        $theme = wp_get_theme();
        $theme_slug = $theme->get('TextDomain');

        // TODO: Allow user to create a custom branch using a form
        // for now creating branch with same name as theme slug
        $local_branch = self::$git->get_local_branch();
        if (empty($local_branch)) {
            // TODO: clear un committed changes if any before creating fresh branch
            // this also takes time to fetch from remote.
            self::$git->create_branch_from_remote($theme_slug);
            $local_branch = self::$git->get_local_branch();
        }
        echo "<p>Local branch: $local_branch</p>";

        echo "<p>Active Theme: ".$theme->get('Name').'</p>';
    }

    private static function get_file_status($modifier) {
        return $modifier === 'A' ? 'New' : ($modifier === 'D' ? 'Deleted' : 'Updated');
    }

    private static function show_theme_changes() {
        echo "<h3>Theme changes</h3>";

        // TODO: capture target path from user and save it to database.
        $theme = wp_get_theme();
        $target_path = $theme->get('TextDomain'); // using theme slug as target directory for testing
        $source_path = get_template_directory(); // current theme directory

        // copy theme into target path.
        wp_mkdir_p(CREATE_BLOCK_THEME_GIT_DIR."/$target_path");
        copy_dir($source_path, CREATE_BLOCK_THEME_GIT_DIR."/$target_path");

        // add the changes to staging
        self::$git->add(".");
        list( , $changes ) = self::$git->status();

        echo "<div>";
        if ( count($changes) === 0) {
            echo "No new changes to commit.";
        } else {
            foreach ($changes as $file => $modifier) {
                $status = self::get_file_status($modifier);
                echo "$status: $file <br>";
            }
        }
        echo "</div>";
    }

    private static function show_commit_form() {
        ?>
        <br>
        <form action="" method="POST">
            <input type="hidden" name="git_commit_form" value="<?php echo wp_create_nonce( 'git_commit_form' ); ?>" />
            <div>
                <label>Commit message: </label>
                <br>
                <textarea type="text" class="regular-text" name="git_commit_message" id="git_commit_message"></textarea>
            </div>

            <input type="submit" class="button-primary" value="Commit changes" />
        </form>
        <?php
    }

    private static function commit_and_push($post_params) {
        self::$git->commit($post_params['git_commit_message']);
        $local_branch = self::$git->get_local_branch();
        self::$git->push($local_branch);

        echo "<h3>Changes pushed to git repository<h3>";
        echo "<p>Create a PR from the branch <strong>$local_branch<strong> if not created yet.</p>";
    }
}
