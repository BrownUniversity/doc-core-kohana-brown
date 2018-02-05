<?php

/**
 * Methods for sending emails via Swift_Mailer.
 *
 * @author jorrill
 */
class DOC_Util_Mail {

	/**
	 * Send the specified Swift_Message with the from, to, etc. as specified in
	 * the arguments list. Returns the number of successful recipients.
	 * 
	 * @param Swift_Message $message
	 * @param array $recipients
	 * @param array $cc
	 * @param string $from
	 * @param string $reply_to
	 * @param Swift_FileSpool $spool
	 * @return int
	 */
	public static function send_message($message, $recipients, $cc = NULL, $from = NULL, $reply_to = NULL, $spool = FALSE, $attachments = array() ) {
		$_output = FALSE ;

		$mail_config = Kohana::$config->load('mail') ;

		if( $spool ) {
			$spool = new Swift_FileSpool($mail_config['spool_location']) ;
			$mailer = new Swift_Mailer( new Swift_SpoolTransport( $spool )) ;
		} else {
// 			$transport = Swift_MailTransport::newInstance() ;
// 			$transport = Swift_SmtpTransport::newInstance('localhost') ;
			$transport = Swift_SendmailTransport::newInstance() ;
			$mailer = Swift_Mailer::newInstance($transport) ;
		}

		if( $mail_config[ 'test_mode' ] == TRUE ) {
			$message = self::append_content($message, "TEST MODE: This message would normally have gone to: " . implode( ', ', $recipients ) ) ;

			if( !empty( $cc )) {
				$message = self::append_content($message, "CC recipients: " . implode( ', ', $cc )) ;
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

		if( count( $attachments ) > 0) {
			$file_util = new DOC_Util_File_Local() ;
		    foreach($attachments as $attachment) {
				if( is_array( $attachment )) {
					Kohana::$log->add(Log::DEBUG, "Attaching file {$attachment['path']}, with new name {$attachment['new_name']}") ;
					$message->attach(Swift_Attachment::fromPath($attachment['path'], $file_util->get_mime_type($attachment['path']))->setFilename($attachment['new_name']));
				} else {
					Kohana::$log->add(Log::DEBUG, "Attaching file {$attachment}. ");
					$message->attach(Swift_Attachment::fromPath($attachment, $file_util->get_mime_type($attachment)));
				}
			}
		} else {
		    Kohana::$log->add(Log::DEBUG, "It appears that there are no attachments. ");
		}
		
		$_output = $mailer->send($message) ;
		Kohana::$log->add(Log::DEBUG, "Message sent with subject '".$message->getSubject()."' at ".date('Y-m-d H:i:s')) ;
		
		return $_output ;

	}

	/**
	 * Append indicated content to the message, checking the content type to be sure we use HTML or plain text
	 * formatting, as appropriate.
	 *
	 * @param \Swift_Message $message
	 * @param string $content
	 * @return \Swift_Message
	 */
	private static function append_content( $message, $content ) {
		if( $message->getContentType() == 'text/html' ) {
			$content = "<p>{$content}</p>" ;
		} else {
			$content = "\n\n{$content}" ;
		}
		$message->setBody( $message->getBody() . $content ) ;
		return $message ;
	}

	/**
	 * Convenience method to send a message without having to generate a Swift_Message
	 * first. This just creates the Swift_Message and passes it along to DOC_Util_Mail::send_message().
	 * 
	 * @param string $subject
	 * @param string $body
	 * @param string $recipients
	 * @param string $cc
	 * @return int
	 */
	public static function send( $subject, $body, $recipients, $cc = NULL, $from = NULL, $reply_to = NULL, $spool = FALSE, $attachments = array() ) {
		$message = Swift_Message::newInstance($subject, $body) ;
		if( $body == strip_tags($body)) {
			$message->setContentType('text/plain') ;
		} else {
			$message->setContentType('text/html') ;
		}

		return self::send_message($message, $recipients, $cc, $from, $reply_to, $spool, $attachments) ;
	}

	/**
	 * Flush the spool of queued messages.
	 */
	public static function flush_spool() {
		$mail_config = Kohana::$config->load('mail') ;

		$spool = new Swift_FileSpool($mail_config['spool_location']) ;
// 		$transport = Swift_MailTransport::newInstance() ;
// 		$transport = Swift_SmtpTransport::newInstance('localhost') ;
		$transport = Swift_SendmailTransport::newInstance() ;
		$count = $spool->flushQueue($transport) ;
		Kohana::$log->add(Log::INFO, "Flushed {$count} messages from the spool.") ;
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
				$address_list = preg_split('/[^A-Za-z0-9._%+@-]+/i',$address_list) ;
			}	
			
			foreach( $address_list as $key => $address ) {
				$address = trim( $address ) ;

				preg_match('/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b/i', $address, $matches) ;

				if( isset( $matches[0] )) {
					$address = $matches[0] ;
				}


				if( !empty( $address )) {
					if( Valid::email( $address )) {
						$_output[ $key ] = $address ;
					} else {
						// throw an exception
						throw new Kohana_Exception("There appears to be a problem with the address(es) in the CC field. Be sure if you have multiple addresses that they are separated with a comma, and that each address is correct ($address).") ;
					}
				}
			}
		}

		return $_output ;
	}

    /**
	 * Get the appropriate Swift Transport object based on configuration
	 * @param array $config
	 * @return Swift Transport Object
	 */
	private static function getDeliveryTransport($config)
    {
        if ( ! array_key_exists('transport', $config)) {
            $config['transport'] = 'sendmail';
        }

        switch($config['transport']) {
              case 'smtp' :
                  $transport = Swift_SmtpTransport::newInstance(
                      isset($config['host']) ? $config['host'] : '',
                      isset($config['port']) ? $config['port'] : 25,
                      isset($config['security']) ? $config['security'] : null
                  );
                  if (isset($config['username'])) {
                      $transport->setUsername($config['username']);
                  }
                  if (isset($config['password'])) {
                      $transport->setPassword($config['password']);
                  }
                  break;
              default :
                  $transport = Swift_SendmailTransport::newInstance();
        }

        return $transport;
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

