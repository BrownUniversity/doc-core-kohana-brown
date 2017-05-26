<?php
namespace BrownUniversity\DOC\Util ;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ical
 *
 * @author Jason Orrill <Jason_Orrill@brown.edu>
 */
class Ical {
	private $start_datetime ;
	private $end_datetime ;
	private $subject ;
	private $description ;
	private $location ;
	protected $product = "-//Brown University/DOC PHP Library//EN" ;
	
	public function __construct( $start_datetime, $end_datetime, $subject, $description, $location ) {
		$this->start_datetime = new DateTime("{$start_datetime}") ;
		$this->start_datetime->setTimezone( new DateTimeZone('UTC')) ;
		$this->end_datetime = new DateTime("{$end_datetime}") ;
		$this->end_datetime->setTimezone( new DateTimeZone('UTC')) ;
		$this->subject = strip_tags($subject) ;
		$this->description = strip_tags($description) ;
		$this->location = strip_tags( $location ) ;
	}
	
	public function generate() {
		$_output = '';
		$crlf = "\r\n" ;
		
		$_output .= "BEGIN:VCALENDAR".$crlf ;
		$_output .= "VERSION:2.0".$crlf ;
		$_output .= "PRODID:{$this->product}".$crlf ;
		$_output .= "BEGIN:VEVENT".$crlf ;
		$_output .= "UID:".DOC_Util_UUID::get()."@".getenv('HOSTNAME').$crlf ;
		$_output .= "DTSTAMP:" . gmdate('Ymd').'T'. gmdate('His') . "Z".$crlf ;
		$_output .= "DTSTART:".$this->start_datetime->format('Ymd\THis\Z').$crlf ;
		$_output .= "DTEND:".$this->end_datetime->format('Ymd\THis\Z').$crlf ;
		$_output .= $this->format("SUMMARY:",$this->subject, FALSE, TRUE).$crlf ;
		$_output .= $this->format("DESCRIPTION:",$this->description).$crlf ;
		$_output .= $this->format("LOCATION:",$this->location, FALSE, TRUE).$crlf ;
		$_output .= "END:VEVENT".$crlf ;
		$_output .= "END:VCALENDAR" ;
		
		return $_output ;
	}
	
	
	// nope nope nope...need to revisit this...mb_strcut doesn't really CUT, it copies.
	private function format($label, $str, $quote = FALSE, $escape = FALSE) {
		// remove any double quotes, since they are not allowed
		$str = str_replace('"', '', $str) ;
		
		// wrap in double quotes if any of the following characters are present: : ; ,
		if( $quote && strpbrk( $str, ':;,' )) {
			$str = '"'.$str.'"' ;
		}

		if( $escape ) {
			$str = preg_replace('/([,])/',"\\\\$1",$str) ;
		}

		$_output = $label.$str ;
		
		if( strlen( $_output ) <= 80 ) {
			return $_output ;
		}
		$src = $_output ;
		$_output = '' ;
		
		for($i = 0; $i < strlen( $src ); $i += 80) {
			$_output .= mb_strcut( $src, $i, 80 ) . "\r\n\t" ;
		}
		$_output = trim( $_output ) ;
		return $_output ;
		
	}
}
