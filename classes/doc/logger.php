<?php

/**
 * Class DOC_Logger
 * Simple logger wrapper around Kohana's logging methods that can be used with
 * modules that expect a logger object passed to it.
 */
class DOC_Logger
{
	public function __construct(){}

	/**
	 * @param      $message
	 * @param null $context
	 * @return string
	 */
	private function compile($message,$context = null ) {
		if( !empty( $context )) {
			$context = ' (' . print_r($context, true) . ')' ;
		} else {
			$context = '' ;
		}

		return $message . $context ;
	}

	/**
	 * @param      $level
	 * @param      $message
	 * @param null $context
	 */
	private function log($level, $message, $context = null ) {
		Kohana::$log->add($level, $this->compile($message, $context)) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function emergency($message,$context) {
		$this->log(Log::EMERGENCY, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function alert($message,$context) {
		$this->log(Log::ALERT, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function critical($message,$context) {
		$this->log(Log::CRITICAL, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function error($message,$context) {
		$this->log(Log::ERROR, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function warning($message,$context) {
		$this->log(Log::WARNING, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function notice($message,$context) {
		$this->log(Log::NOTICE, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function info($message,$context) {
		$this->log(Log::INFO, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function debug($message,$context) {
		$this->log(Log::DEBUG, $message, $context) ;
	}

	/**
	 * @param $message
	 * @param $context
	 */
	public function strace($message,$context) {
		$this->log(Log::STRACE, $message, $context) ;
	}

}