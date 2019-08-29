<?php
namespace BrownUniversity\DOC\Helper ;

use Kohana\Text as Kohana_Text;

/**
 * Class Text
 *
 * @package BrownUniversity\DOC\Helper
 * @todo Move into Kohana
 */
class Text extends Kohana_Text
{
   /**
	* Truncates text.
	*
	* Cuts a string to the length of $length and replaces the last characters
	* with the ending if the text is longer than length.
	*
	* Stolen from http://www.gsdesign.ro/blog/cut-html-string-without-breaking-the-tags/,
	* which possibly stole it from CakePHP...
	*
	* @param string  $text String to truncate.
	* @param integer $length Length of returned string, including ellipsis.
	* @param string  $ending Ending to be appended to the trimmed string.
	* @param boolean $exact If false, $text will not be cut mid-word
	* @param boolean $considerHtml If true, HTML tags would be handled correctly
	* @return string Trimmed string.
	*/
    public static function truncate($text, $length = 100, $ending = '...', $exact = true, $considerHtml = false) {
        if ($considerHtml) {
            // if the plain text is shorter than the maximum length, return the whole text
            if (strlen(preg_replace('/<.*?>/', '', $text)) <= $length) {
                return $text;
            }

            // splits all html-tags to scanable lines
            preg_match_all('/(<.+?>)?([^<>]*)/s', $text, $lines, PREG_SET_ORDER);

            $total_length = strlen($ending);
            $open_tags = array();
            $truncate = '';

            foreach ($lines as $line_matchings) {
                // if there is any html-tag in this line, handle it and add it (uncounted) to the output
                if (!empty($line_matchings[1])) {
                    // if it's an "empty element" with or without xhtml-conform closing slash (f.e. <br/>)
                    if (preg_match('/^<(\s*.+?\/\s*|\s*(img|br|input|hr|area|base|basefont|col|frame|isindex|link|meta|param)(\s.+?)?)>$/is', $line_matchings[1])) {
                        // do nothing
                    // if tag is a closing tag (f.e. </b>)
                    } else if (preg_match('/^<\s*\/([^\s]+?)\s*>$/s', $line_matchings[1], $tag_matchings)) {
                        // delete tag from $open_tags list
                        $pos = array_search($tag_matchings[1], $open_tags);
                        if ($pos !== false) {
                            unset($open_tags[$pos]);
                        }
                    // if tag is an opening tag (f.e. <b>)
                    } else if (preg_match('/^<\s*([^\s>!]+).*?>$/s', $line_matchings[1], $tag_matchings)) {
                        // add tag to the beginning of $open_tags list
                        array_unshift($open_tags, strtolower($tag_matchings[1]));
                    }
                    // add html-tag to $truncate'd text
                    $truncate .= $line_matchings[1];
                }

                // calculate the length of the plain text part of the line; handle entities as one character
                $content_length = strlen(preg_replace('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', ' ', $line_matchings[2]));
                if ($total_length+$content_length> $length) {
                    // the number of characters which are left
                    $left = $length - $total_length;
                    $entities_length = 0;
                    // search for html entities
                    if (preg_match_all('/&[0-9a-z]{2,8};|&#[0-9]{1,7};|&#x[0-9a-f]{1,6};/i', $line_matchings[2], $entities, PREG_OFFSET_CAPTURE)) {
                        // calculate the real length of all entities in the legal range
                        foreach ($entities[0] as $entity) {
                            if ($entity[1]+1-$entities_length <= $left) {
                                $left--;
                                $entities_length += strlen($entity[0]);
                            } else {
                                // no more characters left
                                break;
                            }
                        }
                    }
                    $truncate .= substr($line_matchings[2], 0, $left+$entities_length);
                    // maximum lenght is reached, so get off the loop
                    break;
                } else {
                    $truncate .= $line_matchings[2];
                    $total_length += $content_length;
                }

                // if the maximum length is reached, get off the loop
                if($total_length>= $length) {
                    break;
                }
            }
        } else {
            if (strlen($text) <= $length) {
                return $text;
            } else {
                $truncate = substr($text, 0, $length - strlen($ending));
            }
        }

        // if the words shouldn't be cut in the middle...
        if (!$exact) {
            // ...search the last occurance of a space...
            $spacepos = strrpos($truncate, ' ');
            if (isset($spacepos)) {
                // ...and cut the text in this position
                $truncate = substr($truncate, 0, $spacepos);
            }
        }

        // add the defined ending to the text
        $truncate .= $ending;

        if($considerHtml) {
            // close all unclosed html-tags
            foreach ($open_tags as $tag) {
                $truncate .= '</' . $tag . '>';
            }
        }

        return $truncate;

    }

	/**
	 * Format a string as a phone number, using a pattern based on the number
	 * of digits in the string.
	 *
	 * @param string $phone
	 * @return string
	 */
	public static function phone( $phone ) {
		if( $phone != NULL && $phone != '' ) {
			$pattern = '' ;
			$replacement = '' ;
			switch (strlen($phone)) {
				case 5:
					$pattern = '/(\d{1})(\d{4})/' ;
					$replacement = '$1-$2' ;
					break;

				case 7:
					$pattern = '/(\d{3})(\d{4})/' ;
					$replacement = '$1-$2' ;
					break;

				case 10:
					$pattern = '/(\d{3})(\d{3})(\d{4})/' ;
					$replacement = '($1) $2-$3' ;
					break;

				case 11:
					$pattern = '/(\d{1})(\d{3})(\d{3})(\d{4})/' ;
					$replacement = '$1 ($2) $3-$4' ;
					break;

				case 12:
					$pattern = '/(\d{3})[\. -](\d{3})[\. -](\d{4})/' ;
					$replacement = '($1) $2-$3' ;
					break;
				
				case 13:
					$pattern = '/\((\d{3})\)(\d{3})[\. -](\d{4})/' ;
					$replacement = '($1) $2-$3' ;
					break;
				
				default:
					break;
			}

			if( !empty( $pattern )) {
				$phone = preg_replace($pattern, $replacement, $phone) ;
			}

		}
		return $phone ;
	}
	
}