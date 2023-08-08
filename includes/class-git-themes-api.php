<?php

require_once( dirname(__DIR__) . '/lib/git-wrapper.php' );

class Git_Themes_API {
    private $git;
    private $active_theme;
    private $theme_slug;
    private $all_themes_repo_prefix = 'all-themes';
    // radio values set in frontend
    private $connection_types = array(
        "ALL_THEMES" => 'all_themes',
        "CURRENT_THEME" => 'current_theme'
    );

    public function __construct() {
        $this -> git = Git_Wrapper::get_git(CREATE_BLOCK_THEME_GIT_DIR);
        $this -> active_theme = wp_get_theme();
        $this -> theme_slug = $this -> active_theme -> get('TextDomain');
        WP_Filesystem();
    }

    public function get_git_config() {
        $git_version = $this -> git -> get_version();
        if (empty($git_version)) {
            return array( 'version' => $git_version );
        }

        $repo_config = $this -> fetch_repo_config();
        $branch = '';
        if ($repo_config['is_git_initialized']) {
            $branch = $this -> git -> get_local_branch();
        }

        // TODO: capture target path from user and save it to database.
        $theme = wp_get_theme();
        $path_prefix = "/".$theme->get('TextDomain'); // using theme slug as target directory for testing

        return array(
            'version' => $git_version,
            'is_git_initialized' => $repo_config['is_git_initialized'],
            'connection_type' => $repo_config['connection_type'],
            'remote_url' => $repo_config['remote_url'], // TODO: mask access token
            'active_theme_name' => $this -> active_theme -> get('Name'),
            'current_branch' => $branch,
            'commit_path_prefix' => $path_prefix,
        );
    }

    public function connect_git_repo($request) {
        $repository = $request->get_params();

        try {
            $remote_url = $repository['remote_url'];
            $author_name = $repository['author_name'];
            $author_email = $repository['author_email'];
            $connection_type = $repository['connection_type'];
            // TODO: escape and validate the above fields

            $repo_dir = $this -> get_repo_dir($connection_type);

            wp_mkdir_p($repo_dir);
            $this -> git -> set_git_directory($repo_dir);

            $this -> git -> init($author_name, $author_email);
            $this -> git -> add_remote_url( $remote_url );
            $this -> git -> fetch_ref();

            return array("status" => "success");
        } catch (\Throwable $th) {
            return array("status" => "failed");
        }
    }

    public function disconnect_git_repo($request) {
        try {
            $repository = $request->get_params();

            $connection_type = $repository['connection_type'];
            $repo_dir = $this -> get_repo_dir($connection_type);
            $this -> delete_directory($repo_dir);
            return array("status" => "success", "repo"=>$repo_dir);
        } catch (\Throwable $th) {
            return array("status" => $th -> __toString());
        }
    }

    public function get_git_changes() {
        $repo_config = $this -> fetch_repo_config();
        if (!$repo_config['is_git_initialized']) {
            return array();
        }

        $source_path = get_template_directory(); // current theme directory
        $destination_path = $repo_config['repo_path'].$repo_config['commit_path_prefix'];

        // copy theme into target path.
        wp_mkdir_p($destination_path);
        copy_dir($source_path, $destination_path);

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

    private function get_repo_dir($connection_type) {
        return $connection_type === 'current_theme' ?
            CREATE_BLOCK_THEME_GIT_DIR."/".$this->theme_slug : 
            CREATE_BLOCK_THEME_GIT_DIR."/".$this->all_themes_repo_prefix;
    }

    private function delete_directory($dir) {
        if (!file_exists($dir)) {
            return;
        }
    
        // Check if $dir is a directory
        if (is_dir($dir)) {
            $files = scandir($dir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..') {
                    $path = $dir . '/' . $file;
    
                    if (is_dir($path)) {
                        // Recursively delete subdirectories and their contents
                        $this -> delete_directory($path);
                    } else {
                        // Delete files inside the directory
                        unlink($path);
                    }
                }
            }
            // Delete the empty directory
            rmdir($dir);
        }
    }

    private function fetch_repo_config() {
        global $wp_filesystem;

        // check if there exists a repo linked to current theme
        $repo_path = CREATE_BLOCK_THEME_GIT_DIR . "/$this->theme_slug";
        $git_initialized = $wp_filesystem -> exists("$repo_path/.git");
        if ($git_initialized) {
            $this -> git -> set_git_directory($repo_path);
            $remote_url = $this -> git -> get_remote_url();
            $connection_type = $this->connection_types['CURRENT_THEME'];
            if (!empty($remote_url)) {
                return array(
                    "is_git_initialized" => $git_initialized, 
                    "remote_url" => $remote_url,
                    "connection_type" => $connection_type,
                    "repo_path" => $repo_path,
                );
            }
        }

        // check if there exists a repo linked to all themes
        $repo_path = CREATE_BLOCK_THEME_GIT_DIR . "/$this->all_themes_repo_prefix";
        $git_initialized = $wp_filesystem -> exists("$repo_path/.git");
        if ($git_initialized) {
            $this -> git -> set_git_directory($repo_path);
            $remote_url = $this -> git -> get_remote_url();
            $connection_type = $this->connection_types['ALL_THEMES'];
            if (!empty($remote_url)) {
                return array(
                    "is_git_initialized" => $git_initialized, 
                    "remote_url" => $remote_url,
                    "connection_type" => $connection_type,
                    "repo_path" => $repo_path,
                );
            }
        }

        return array(
            "is_git_initialized" => false, 
            "remote_url" => '',
            "connection_type" => ''
        );
    }
}
