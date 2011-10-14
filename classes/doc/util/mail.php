<?php

require_once Kohana::find_file('classes', 'Swift-4.0.6/lib/swift_required') ;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of mail
 *
 * @author jorrill
 */
class DOC_Util_Mail {
	
	/**
	 *
	 * @param string $subject
	 * @param string $body
	 * @param string $recipients
	 * @param string $cc
	 * @return int 
	 */
	public static function send( $subject, $body, $recipients, $cc = NULL ) {
		$_output = FALSE ;
		
		$mail_config = Kohana::$config->load('mail') ;
		$transport = Swift_MailTransport::newInstance() ;
		$mailer = Swift_Mailer::newInstance($transport) ;

		if( $mail_config[ 'test_mode' ] == TRUE ) {
			$body .= "\n\nTEST MODE: This message would normally have gone to: " . implode( ', ', $recipients ) ;
			$recipients = unserialize( $mail_config[ 'test_mode_recipients' ] ) ;
		}
		
		$message = Swift_Message::newInstance($subject, $body) ;
		$message->setContentType('text/html') ;
		$message->setTo( $recipients ) ;
		$message->setFrom( $mail_config[ 'from' ] ) ;
		$message->setReplyTo( $mail_config[ 'reply-to' ]) ;
		
		if( !empty( $cc )) {
			$message->setCc($cc) ;
		}
		
		$_output = $mailer->send($message) ;
		
		return $_output ;
		
	}
	
	/**
	 * Validates a list of email addresses and converts them to an array. If any
	 * address fail validation, throws an exception.
	 * 
	 * @param mixed $address_list String or array containing email addresses
	 * @return array An array of email addresses.
	 */
	public static function validate_addresses( $address_list ) {
		
		if( $address_list != NULL ) {
			if( !is_array( $address_list)) {
				$address_list = explode( ',', $address_list ) ;
			}

			foreach( $address_list as $key => $address ) {
				$address_list[ $key ] = trim( $address ) ;
				if( !Valid::email($address_list[ $key ])) {
					// throw an exception
					throw new Kohana_Exception("There appears to be a problem with the address(es) in the CC field. Be sure if you have multiple addresses that they are separated with a comma, and that each address is correct.") ;
				}
			}
		}
		return $address_list ;
	}
	
	/**
	 * Mail merge-- this is highly dependent the individual app's data structure,
	 * so not actually implemented here. The code is commented out as a reference
	 * for local implementation.
	 * 
	 * @param string $message_key
	 * @param mixed $object
	 * @return array 
	 */
//	public static function merge( $message_key, $object ) {
//		$_output = array('subject' => '', 'body' => '') ;
//		$mail_config = Kohana::$config->load('mail') ;
//		
//		$_output[ 'subject' ] = $mail_config[ 'templates' ][ $message_key ][ 'subject' ] ;
//		$_output[ 'body' ] = $mail_config[ 'templates' ][ $message_key ][ 'body' ] ;
//		
//		switch ($message_key) {
//			case 'lesson_plans_under_review':
//				$replacements = array(
//					'[[LESSON_PLANS_TITLE]]' => $object->lesson_title,
//					'[[LESSON_PLANS_EDIT_URL]]' => 'http://' . $_SERVER['SERVER_NAME'] . Kohana::$base_url . 'lessonplans/edit/' . $object->pk()
//				) ;
//
//				break;
//			
//			default:
//				break;
//		}
//		
//		
//		$_output = str_replace(array_keys( $replacements ), array_values( $replacements ), $_output) ;
//		
//		return $_output ;
//		
//	}
	
	
}

?>
