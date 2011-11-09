<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of cron
 *
 * @author jorrill
 */
class DOC_Controller_Cron extends Controller {
	public function action_minutely() {
		Kohana::$log->add(Log::INFO, 'Executing minutely cron script') ;
	}
	public function action_hourly() {
		Kohana::$log->add(Log::INFO, 'Executing hourly cron script') ;
	}
	public function action_daily() {
		Kohana::$log->add(Log::INFO, 'Executing daily cron script') ;
	}
	public function action_weekly() {
		Kohana::$log->add(Log::INFO, 'Executing weekly cron script') ;
	}
	public function action_monthly() {
		Kohana::$log->add(Log::INFO, 'Executing monthly cron script') ;
	}
	
	public function action_index() {}
	
}

?>
