<?php

// require_once Kohana::find_file('classes', 'Swift-4.1.5/lib/swift_required') ;

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

	public static function send_message($message, $recipients, $cc = NULL, $from = NULL, $reply_to = NULL ) {
		$_output = FALSE ;

		$mail_config = Kohana::$config->load('mail') ;
		$transport = Swift_MailTransport::newInstance() ;
		$mailer = Swift_Mailer::newInstance($transport) ;

		if( $mail_config[ 'test_mode' ] == TRUE ) {
			$message->setBody(
				$message->getBody() .
				"<p>TEST MODE: This message would normally have gone to: " . implode( ', ', $recipients ) . "</p>"
			) ;
			if( !empty( $cc )) {
				$message->setBody(
					$message->getBody() .
					"<p>CC recipients: " . implode( ', ', $cc ) . "</p>"
				) ;
			}
			$recipients = unserialize( $mail_config[ 'test_mode_recipients' ] ) ;
		}
		if( $from == NULL ) {
			$from = $mail_config[ 'from' ] ;
		}
		if( $reply_to == NULL ) {
			$reply_to = $mail_config[ 'reply-to' ] ;
		}

		$message->setTo( $recipients ) ;
		$message->setFrom( $from ) ;
		$message->setReplyTo( $reply_to ) ;

		if( !empty( $cc )) {
			if( $mail_config[ 'test_mode' ] != TRUE ) {
				$message->setCc($cc) ;
			}
		}

		$_output = $mailer->send($message) ;

		return $_output ;

	}

	/**
	 *
	 * @param string $subject
	 * @param string $body
	 * @param string $recipients
	 * @param string $cc
	 * @return int
	 */
	public static function send( $subject, $body, $recipients, $cc = NULL, $from = NULL, $reply_to = NULL ) {
		$message = Swift_Message::newInstance($subject, $body) ;
		$message->setContentType('text/html') ;

		return self::send_message($message, $recipients, $cc, $from, $reply_to) ;
	}

	/**
	 * Validates a list of email addresses and converts them to an array. If any
	 * address fail validation, throws an exception.
	 *
	 * @param mixed $address_list String or array containing email addresses
	 * @return array An array of email addresses.
	 */
	public static function validate_addresses( $address_list ) {
		$_output = array() ;
		if( $address_list != NULL && $address_list != '') {
			if( !is_array( $address_list)) {
				$address_list = explode( ',', $address_list ) ;
			}

			foreach( $address_list as $key => $address ) {
				$address = trim( $address ) ;
				if( !empty( $address )) {
					if( Valid::email( $address )) {
						$_output[ $key ] = $address ;
					} else {
						// throw an exception
						throw new Kohana_Exception("There appears to be a problem with the address(es) in the CC field. Be sure if you have multiple addresses that they are separated with a comma, and that each address is correct.") ;
					}
				}
			}
		}

		return $_output ;
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
