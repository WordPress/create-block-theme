<?php

require_once __DIR__ . '/git-constants.php';

class Git_Wrapper {
	private static $obj;
	private $last_error = '';
	private $repo_dir;

	function __construct( $repo_dir ) {
		$this -> repo_dir = $repo_dir;
	}

	// singleton
	public static function get_git($repo_dir) {
		if (empty($repo_dir)) {
			return null;
		}

        if ( !isset(self::$obj) ) {
            self::$obj = new Git_Wrapper($repo_dir);
        }
        return self::$obj;
    }

	public function set_git_directory($repo_dir) {
		$this -> repo_dir = $repo_dir;
	}

	protected function _execute(...$args) {
		$args     = join( ' ', array_map( 'escapeshellarg', $args ) );
		$return   = -1;
		$response = array();
		$env      = array();

		$cmd = "git $args 2>&1";

		$proc = proc_open(
			$cmd,
			array(
				0 => array( 'pipe', 'r' ),  // stdin
				1 => array( 'pipe', 'w' ),  // stdout
			),
			$pipes,
			$this->repo_dir,
			$env
		);
		if ( is_resource( $proc ) ) {
			fclose( $pipes[0] );
			while ( $line = fgets( $pipes[1] ) ) {
				$response[] = rtrim( $line, "\n\r" );
			}
			$return = (int)proc_close( $proc );
		}
		
		if ( 0 != $return ) {
			$this->last_error = join( "\n", $response );
		} else {
			$this->last_error = null;
		}
		return array( $return, $response );
	}

	function get_last_error() {
		return $this->last_error;
	}

	function get_version() {
		list( $return, $version ) = $this->_execute( 'version' );
		if ( 0 != $return ) { return ''; }
		if ( ! empty( $version[0] ) ) {
			return substr( $version[0], 12 );
		}
		return '';
	}

	function init($name='Create Block Theme', $email='') {
		list( $return, ) = $this->_execute( 'init' );
		$this->_execute( 'config', 'user.name', $name );
		$this->_execute( 'config', 'user.email', $email );
		$this->_execute( 'config', 'push.default', 'nothing' );
		return ( 0 == $return );
	}

	function add_remote_url( $url ) {
		list( $return, ) = $this->_execute( 'remote', 'add', 'origin', $url );
		return ( 0 == $return );
	}

	function get_remote_url() {
		list( , $response ) = $this->_execute( 'config', '--get', 'remote.origin.url' );
		if ( isset( $response[0] ) ) {
			return $response[0];
		}
		return '';
	}

	function remove_remote() {
		list( $return, ) = $this->_execute( 'remote', 'rm', 'origin');
		return ( 0 == $return );
	}

	function get_local_branch() {
		// https://stackoverflow.com/questions/6245570/how-do-i-get-the-current-branch-name-in-git
		list( $return, $response ) = $this->_execute( 'rev-parse', '--abbrev-ref', 'HEAD' );
		if ( 0 == $return ) {
			return $response[0];
		}
		return false;
	}

	function create_branch_from_remote($new_branch) {
		$this->_execute( 'fetch', 'origin', "HEAD:$new_branch" );
		$this->_execute( 'checkout', $new_branch );
	}

	function fetch_ref() {
		list( $return, ) = $this->_execute( 'fetch', 'origin' );
		return ( 0 == $return );
	}

	function add(...$args) {
		if ( 1 == count($args) && is_array( $args[0] ) ) {
			$args = $args[0];
		}
		$params = array_merge( array( 'add', '-n', '--all' ), $args );
		list ( , $response ) = call_user_func_array( array( $this, '_execute' ), $params );
		$count = count( $response );

		$params = array_merge( array( 'add', '--all' ), $args );
		list ( , $response ) = call_user_func_array( array( $this, '_execute' ), $params );

		return $count;
	}

	// TODO: rebase and commit
	function commit( $message ) {
		list( $return, $response ) = $this->_execute( 'commit', '-m', $message );
		if ( $return !== 0 ) { return false; }

		list( $return, $response ) = $this->_execute( 'rev-parse', 'HEAD' );
		return ( $return === 0 ) ? $response[0] : false;
	}

	function push( $branch ) {
		if ( ! empty( $branch ) ) {
			// TODO: works only for fast-forward push.
			// need to take care of force push (but it should be rebased first to not loose changes)
			list( $return, ) = $this->_execute( 'push', '--porcelain', '-u', 'origin', $branch );
		}
		return ( $return == 0 );
	}

	function status() {
		list( $branch_status, $new_response ) = $this->local_status();
		return array( $branch_status, $new_response );
		// TODO: check the status with remote also
	}

	function local_status() {
		list( $return, $response ) = $this->_execute( 'status', '-s', '-b', '-u' );
		if ( 0 !== $return ) {
			return array( '', array() );
		}

		$new_response = array();
		if ( ! empty( $response ) ) {
			$branch_status = array_shift( $response );
			foreach ( $response as $idx => $line ) :
				unset( $index_status, $work_tree_status, $path, $new_path, $old_path );

				if ( empty( $line ) ) { continue; } // ignore empty lines like the last item
				if ( '#' == $line[0] ) { continue; } // ignore branch status

				$index_status     = substr( $line, 0, 1 );
				$work_tree_status = substr( $line, 1, 1 );
				$path             = substr( $line, 3 );

				$old_path = '';
				$new_path = explode( '->', $path );
				if ( ( 'R' === $index_status ) && ( ! empty( $new_path[1] ) ) ) {
					$old_path = trim( $new_path[0] );
					$path     = trim( $new_path[1] );
				}
				$new_response[ $path ] = trim( $index_status . $work_tree_status . ' ' . $old_path );
			endforeach;
		}

		return array( $branch_status, $new_response );
	}
}
