<?php

require_once( dirname(__DIR__) . '/lib/git-wrapper.php' );

class Git_Themes_API {
    private $git;

    public function __construct() {
        $this -> git = Git_Wrapper::get_git(CREATE_BLOCK_THEME_GIT_DIR);
    }

    public function get_git_config() {
        return array(
            'version' => $this -> git -> get_version(),
            'git_configured' => false,
            'remote_url' => ''
        );
    }
}
