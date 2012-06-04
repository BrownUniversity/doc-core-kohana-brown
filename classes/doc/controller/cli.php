<?php

/**
 * Description of DOC_Controller_CLI
 *
 * @author jorrill
 */
class DOC_Controller_CLI extends Controller {

	protected $task_name ;
	protected $task_data = NULL ;
	/**
	 * Defines the task(s) that may be performed. The structure should be as follows:
	 * array( 'task name' => array('method' => '[protected method in local CLI class]', 'help' => '[user help content]')
	 */
	protected $task_map = array() ;

	const HELP = 'help' ;

	public function before() {
		parent::before() ;

		ob_implicit_flush(TRUE) ;
		ob_end_flush() ;

		$cli_config = Kohana::$config->load('cli') ;
		if( $cli_config[ 'cli_enabled' ] === TRUE ) {
			$auth = CLI::options('username', 'password') ;
			$task_args = CLI::options('task','data') ;

			if( isset( $auth[ 'username' ] ) && isset( $auth[ 'password' ] )) {

				if( $cli_config[ 'cli_users' ][ $auth[ 'username' ]] == crypt( $auth[ 'password' ], $cli_config['cli_salt'] )) {

					if( isset( $task_args[ 'task' ] ) && !empty( $task_args[ 'task' ] )) {
						$this->task_name = $task_args[ 'task' ] ;
						if( $this->task_name == self::HELP ) {
							$this->show_help() ;
						}
						if( isset( $task_args[ 'data' ] ) && !empty( $task_args[ 'data' ] )) {
							$this->task_data = json_decode( $task_args[ 'data' ]) ;
						}

					} else {
						print("\nNo task specified.\n") ;
						$this->show_help() ;
					}
				} else {
					die("\nInvalid username/password.\n") ;
				}
			} else {
				die("\nBoth username and password are required\n") ;
			}
		} else {
			die("\nCLI is not enabled for this application\n") ;
		}
	}

	/**
	 * Use the action_index() method to contain all processing required.
	 */
	public function action_index() {
		if(array_key_exists($this->task_name, $this->task_map)) {
			$this->{$this->task_name}() ;
		} else {
			print( "\nUnrecognized CLI task.\n") ;
			$this->show_help() ;
		}
	}

	protected function show_progress($count, $total, $break_at = 50 ) {
		print('.') ;
		if( $count % $break_at == 0 ) {
			print( " ({$count}/{$total})\n") ;
		}
	}

	protected function show_help() {
		if( count( $this->task_map ) > 0 ) {
			print( "\nAvailable tasks:\n\n" ) ;
			foreach( $this->task_map as $name => $task_info ) {
				print( "{$name}:\n" ) ;
				print( "\t{$task_info['help']}\n\n" ) ;
			}
		}
		exit() ;
	}
}