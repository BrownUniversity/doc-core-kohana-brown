<?php

/**
 * Description of DOC_Controller_CLI
 *
 * @author jorrill
 */
class DOC_Controller_CLI extends Controller {

	protected $task_name ;
	protected $task_data = NULL ;

	public function before() {
		parent::before() ;

		$cli_config = Kohana::$config->load('cli') ;
		if( $cli_config[ 'cli_enabled' ] === TRUE ) {
			$auth = CLI::options('username', 'password') ;
			$task_args = CLI::options('task','data') ;

			if( isset( $auth[ 'username' ] ) && isset( $auth[ 'password' ] )) {

				if( $cli_config[ 'cli_users' ][ $auth[ 'username' ]] == crypt( $auth[ 'password' ], $cli_config['cli_salt'] )) {

					if( isset( $task_args[ 'task' ] ) && !empty( $task_args[ 'task' ] )) {
						$this->task_name = $task_args[ 'task' ] ;
						if( isset( $task_args[ 'data' ] ) && !empty( $task_args[ 'data' ] )) {
							$this->task_data = json_decode( $task_args[ 'data' ]) ;
						}

					} else {
						die("\nNo task specified.\n") ;
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
	 * Task names should be processed using an if or switch block.
	 */
	public function action_index() {}
}