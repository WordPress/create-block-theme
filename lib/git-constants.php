<?php

if ( !defined('CREATE_BLOCK_THEME_GIT_DIR') ) {
    $plugin_dir = WP_PLUGIN_DIR.'/create-block-theme';
    $theme_repos_dir = $plugin_dir.'/.git-repo';
    
    define('CREATE_BLOCK_THEME_GIT_DIR', $theme_repos_dir);
}
