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
    
	/**
	 * Slight modification to daily cron in that this happens once a day,
	 * but in the evenings rather than the mornings.
	 */
	public function action_nightly() {
		Kohana::$log->add(Log::INFO, 'Executing nightly cron script');
	}
    
	public function action_index() {}
    
	/**
	 * Return whether or not the day (relative to $now) is the provided $day.
	 * 
	 * @param string $day Day for which to test.
	 * @param integer $now Date for generating day string.
	 * @return boolean
	 */
	protected function is_day($day, $now = NULL) {
		return $this->check_date_component('l', $day, $now)
		    || $this->check_date_component('D', $day, $now);
	}
	
	/**
	 * Return whether or not the hour (relative to $now) is the provided $hour.
	 * 
	 * @param string $hour Hour for which to test.
	 * @param integer $now Date for generating hour string.
	 * @return boolean
	 */
	protected function is_hour($hour, $now = NULL) {
		return $this->check_date_component('H', $hour, $now)
		    || $this->check_date_component('ha', strtolower($hour), $now)
		    || $this->check_date_component('h a', strtolower($hour), $now)
		    || $this->check_date_component('ga', strtolower($hour), $now)
		    || $this->check_date_component('g a', strtolower($hour), $now);
	}
	
	/**
	 * Test a date $format (relative to $now) against the provided $test string.
	 * 
	 * @param string $format Date format.
	 * @param string $test Test string for comparison.
	 * @param integer $now Date for generating format string.
	 * @return boolean
	 */
	protected function check_date_component($format, $test, $now = NULL) {
		$now = $now ? $now : strtotime('now');
		return date($format, $now) === $test;
	}
	
	/**
	 * Class Kohana psuedo-constructor
	 */
	public function before() {

		/**
		 * Ensure the request is actually coming from the command line
		 */
		if ( ! Kohana::$is_cli) {
			throw new Kohana_Exception('Attempting to execute CRON view a web-browser.');
		}
	}

}

