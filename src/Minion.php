<?php
namespace BrownUniversity\DOC ;
/**
 * Class Util_Minion
 */
abstract class Minion extends \Minion_Task {

	protected $_options = array('groups' => '') ;

	/**
	 * We override the constructor to get the unix groups for the current user.
	 */
	protected function __construct()
	{
		parent::__construct() ;
		$cli_user = posix_getpwuid(posix_geteuid()) ;

		preg_match('/^.+?: (.+)/',`groups {$cli_user['name']}`,$matches) ;
		$this->_options['groups'] = explode(' ',$matches[1]) ;
	}

	/**
	 * @param \Validation $validation
	 * @return $this
	 */
	public function build_validation(\Validation $validation)
	{
		$cli_groups = \Kohana::$config->load('cli.cli_groups') ;

		return parent::build_validation($validation)
		             ->rule('groups','not_empty')
		             ->rule('groups','exists',array(':value',$cli_groups));
	}
}