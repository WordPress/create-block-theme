<?php

require_once( dirname(__DIR__) . '/lib/git-wrapper.php' );

class Git_Themes_API {
    private $git;

    public function __construct() {
        $this -> git = Git_Wrapper::get_git(CREATE_BLOCK_THEME_GIT_DIR);
        WP_Filesystem();
    }

    public function get_git_config() {
        $git_version = $this -> git -> get_version();
        if (empty($git_version)) {
            return array( 'version' => $git_version );
        }

        list($git_configured, $remote_url) = $this -> is_git_initialized();
        return array(
            'version' => $git_version,
            'git_configured' => $git_configured,
            'remote_url' => $remote_url // TODO: mask access token
        );
    }

    public function connect_git_repo($request) {
        $repository = $request->get_params();

        try {
            wp_mkdir_p(CREATE_BLOCK_THEME_GIT_DIR);
            $remote_url = $repository['remote_url'];
            $author_name = $repository['author_name'];
            $author_email = $repository['author_email'];

            $this -> git -> init($author_name, $author_email);
            $this -> git -> add_remote_url( $remote_url );
            // TODO: it is taking long time sometimes resulting timeouts.
            // May be make it a background process??
            $this -> git -> fetch_ref();

            return array("status" => "success");
        } catch (\Throwable $th) {
            return array("status" => "fail");
        }
    }

    public function disconnect_git_repo($request) {

    }

    public function get_git_changes() {
        // TODO: capture target path from user and save it to database.
        $theme = wp_get_theme();
        $target_path = $theme->get('TextDomain'); // using theme slug as target directory for testing
        $source_path = get_template_directory(); // current theme directory

        // copy theme into target path.
        wp_mkdir_p(CREATE_BLOCK_THEME_GIT_DIR."/$target_path");
        copy_dir($source_path, CREATE_BLOCK_THEME_GIT_DIR."/$target_path");

        // add the changes to staging
        $this -> git -> add(".");
        list( , $changes ) = $this -> git -> status();

        $result = array();

        foreach ($changes as $file => $modifier) {
            array_push($result, array(
                "file" => $file,
                "modifier" => $modifier,
            ));
        }

        return $result;
    }

    public function commit_changes($request) {
        try {
            $commit = $request->get_params();

            $this->git->commit($commit['message']);
            $local_branch = $this->git->get_local_branch();
            $this->git->push($local_branch);

            return array("status" => "success");
        } catch (\Throwable $th) {
            return array("status" => "fail");
        }
    }

    private function is_git_initialized() {
        global $wp_filesystem;
        $git_initialized = $wp_filesystem -> exists(CREATE_BLOCK_THEME_GIT_DIR.'/.git');
        $remote_url = $this -> git -> get_remote_url();
        if (empty($remote_url)) {
            $git_initialized = false;
        }
        return array($git_initialized, $remote_url);
    }
}
