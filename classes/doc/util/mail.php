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
		
		$mail_config = Kohana::config('mail') ;
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
//		$mail_config = Kohana::config('mail') ;
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
