<?php

/**
 * Generic CLI controller for use with any app that needs such functionality. Tasks
 * and help content should be defined in application-level classes that extend this
 * (see $task_map) note. This also includes basic support for a username/password
 * that is required to execute the function. These are defined in the cli config file.
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
	 * The action_index() method executes the indicated task, or if there is no
	 * recognizable CLI task if will output help content.
	 */
	public function action_index() {
		if(array_key_exists($this->task_name, $this->task_map)) {
			$this->{$this->task_name}() ;
		} else {
			print( "\nUnrecognized CLI task.\n") ;
			$this->show_help() ;
		}
	}

	/**
	 * Text display of current progress. The expectation is that this would get called
	 * once for each iteration of a loop. Outputs a single period ('.') for each 
	 * iteration, with a periodic line break to prevent output from getting useless.
	 * 
	 * @param int $count The current number of iterations
	 * @param int $total The total number of expected iterations
	 * @param int $break_at Number at which to output a line break
	 */
	protected function show_progress($count, $total, $break_at = 50 ) {
		print('.') ;
		if( $count % $break_at == 0 ) {
			print( " ({$count}/{$total})\n") ;
		}
	}

	/**
	 * Prints help output, including task names and help content for each task
	 * defined.
	 */
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