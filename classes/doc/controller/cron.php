<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Generic parent controller for cron jobs. There are actions for time slices from
 * "minutely" to "monthly". Each application will override one or more of the methods
 * here. There is a generic cron job defined on lamppub that executes a set of shell
 * scripts, which should then run one of these tasks as appropriate. The CLI syntax
 * for a given task is as follows:
 * 
 * /path/to/php /path/to/index.php --uri=cron/daily
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

