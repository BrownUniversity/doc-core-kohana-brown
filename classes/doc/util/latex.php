<?php

/**
 * Collection of utility methods for generating LaTeX documents.
 *
 * @author jorrill
 */
class DOC_Util_LaTeX {
		
	const LATEX_LINE_END = '\\\\\\\\ ' ;
	
	/**
	 * Intended for use with preg_replace_callback to remove HTML code that would
	 * render as whitespace only. 
	 * 
	 * @param array $input
	 * @return string
	 */
	public static function strip_empty($input) {
		if( DOC_Valid::not_empty_html($input[1])) {
			return $input[0] ;
		}
		return '' ;
	}
	
	/**
	 * Parses HTML code and returns valid LaTeX to generate the equivalent.
	 * 
	 * @return string LaTeX code
	 * @todo Add support for hyperlinks
	 */
	public static function parse_html($html, $supported_tags = DOC_Util_WordHTML::ALLOWABLE_TAGS_DEFAULT) {
		$_output = $html ;
		
		$replacements = array(
			'/<div.*?>(.*?)<\/div>/s' => '$1'.self::LATEX_LINE_END,
			'/<p.*?>(.*?)<\/p>/s' => "$1\n\n",
			// LaTeX doesn't like seeing a line break with no other content on the line,
			// which this might otherwise allow, so we'll stick a non-breaking space in front of it
			'/<br.*?>/' => '~'.self::LATEX_LINE_END, 
			'/<strong>(.*?)<\/strong>/s' => '\textbf{$1}',
			'/<b>(.*?)<\/b>/s' => '\textbf{$1}',
			'/<em>(.*?)<\/em>/s' => '\textit{$1}',
			'/<i>(.*?)<\/i>/s' => '\textit{$1}',
			'/<u>(.*?)<\/u>/s' => '\underline{$1}',
			'/<del>(.*?)<\/del>/s' => '\sout{$1}',
			'/<strike>(.*?)<\/strike>/s' => '\sout{$1}',
			'/<blockquote>(.*?)<\/blockquote>/s' => '\begin{quote}$1\end{quote}',
			'/<ol.*?>(.*?)<\/ol>/s' => '\begin{enumerate}$1\end{enumerate}',
			'/<ul.*?>(.*?)<\/ul>/s' => '\begin{itemize}$1\end{itemize}',
			'/<li.*?>(.*?)<\/li>/s' => '\item $1'
		) ;		
			
		// strip out any tags we don't support
		
		$_output = DOC_Util_WordHTML::clean($_output) ;
		
		// deal with the usual smart quote headache and cousins
		
		$_output = DOC_Util_WordHTML::convert_problem_chars($_output) ;
		
		// tidy the document
		
		$_output = DOC_Util_WordHTML::domdocument_tidy($_output) ;
		$_output = str_replace('&nbsp;', ' ', $_output) ;

		// run through html_entity_decode

		$_output = html_entity_decode($_output) ;
		
		// tighten up any extra whitespace
		
		$_output = preg_replace('/(\s|\n){2,}/s',' ',$_output) ;
		$_output = preg_replace("~>\n*\s*\n*<~", '><', $_output) ;

		// deal with most characters LaTeX needs modified
		
		$_output = self::latex_special_chars($_output) ;

		// remove any empty blockquotes, since they run the risk of making LaTeX crabby
		$_output = preg_replace_callback('/<blockquote>(.*?)<\/blockquote>/','DOC_Util_LaTeX::strip_empty',$_output) ;

		// the rich text editor sometimes leaves breaks at the end of a list item, which is redundant
		$_output = preg_replace('/<li>(.*?)<br.*?>\s*?<\/li>/s','<li>$1</li>',$_output) ; 
		
		// parse the html
		$pre_replace = $_output ;
		$_output = preg_replace( array_keys( $replacements ), array_values( $replacements ), $pre_replace ) ;
		while( $pre_replace != $_output ) {
			$pre_replace = $_output ;
			$_output = preg_replace( array_keys( $replacements ), array_values( $replacements ), $pre_replace ) ;
		}
		
		// deal with the < and > characters
		
		$_output = str_replace( array('<','>'), array('{\textless}','{\textgreater}'), $_output ) ;
		
		// Whitespace inside text property commands such as \textbf{} causes barfage, 
		// so replace standard paragraph breaks with alternative line ends.
		preg_match_all('/\{(.+?)\}/s', $_output, $matches) ;
		if( count( $matches ) > 0 ) {
			if( count( $matches[1] ) > 0 ) {
				foreach( $matches[1] as $match ) {
					$_output = str_replace("\n\n", "\n\\\\\n", $_output) ;
				}
			}
		}
		
		// Having a line break at the very end can cause problems if it's part 
		// of a \begin{x}\end{x} block, so we'll get rid of those.
		$_output = preg_replace('/\n\\\\\\\\\s+$/','',$_output) ;
		
		return $_output ;
	}
	
	/**
	 * Parse a string for any characters that need special handling in LaTeX. Note
	 * that we skip the greater than/less than characters here, because those need 
	 * to happen after some other processing. We also deal with backslashes in two
	 * passes to keep their curly braces from getting escaped.
	 * 
	 * @param string $str
	 */
	public static function latex_special_chars($str) {
		$replacements = array(
			'\\' => '\textbackslash', 
			'{' => '\{',
			'}' => '\}',
			'&' => '\&',
			'$' => '\$',
			'%' => '\%',
			'_' => '\_',
			'^' => '\^{}',
			'~' => '\~{}',
			'|' => '{\textbar}',
			'#' => '\#',
			'\'' => '{\textquotesingle}',
			'"' => '{\textquotedbl}',
			'\textbackslash' => '{\textbackslash}'
//			'<' => '\textless',
//			'>' => '\textgreater'
		) ;
		
		return str_replace(array_keys( $replacements ), array_values( $replacements ), $str) ;
	}
	
	/**
	 * Given a LaTeX string, render a pdf file and return the file information
	 * so that it can be further processed or downloaded.
	 * 
     * @throws Kohana_Exception
	 * @param string $latex_str LaTeX string ready to be rendered.
	 * @param string $filename Desired pdf filename. Extension is not required.
	 * @return array File description array, matching what we get from php's $_FILES
	 */
	public static function create_pdf($latex_str, $filename, $run_cleanup = TRUE) {
		if( $run_cleanup === TRUE ) {
			self::cleanup(TRUE) ;
		}
		
//		print("<pre>{$latex_str}</pre>") ;
//		die() ;
		
		$latex_config = Kohana::$config->load('latex') ;
		
		$safe_filename = DOC_Util_File::safe_filename($filename) ;
		$safe_filename = preg_replace( '/\.pdf$/', '', $safe_filename ) ;
		$safe_filename .= '_' . date('YmdHis').Text::random() ;

		// write out the LaTeX
		$latex_file = "{$latex_config->tmp_path}{$safe_filename}.tex" ;
		$success = file_put_contents($latex_file,$latex_str) ;

		if ($success === FALSE) {
			throw new Kohana_Exception('Failed generating temporary TeX file.');
		}
                
		// render the pdf and return the appropriate file info. 
		$pdf_file = "{$latex_config->tmp_path}{$safe_filename}.pdf" ;
		$command = "{$latex_config->bin_path}pdflatex -jobname {$safe_filename} -output-directory {$latex_config->tmp_path} {$latex_file}" ;
		$result = exec( $command, $full_result ) ;
		
		$_output = DOC_Util_File::get_file_specs( $pdf_file, $filename ) ;

		if (count($_output) == 0) {
			throw new Kohana_Exception('TeX to PDF conversion failed');
		}
		return $_output ;
		
	}
	
	/**
	 * Go through the latex tmp directory and remove any old files.
	 * 
	 * @param boolean $remove_old_pdf Set to TRUE to also remove old pdf files. By default they're skipped.
	 * @param string $older_than String suitable as input to strtotime.
	 */
	public static function cleanup( $remove_old_pdf = FALSE, $older_than = '-1 hour' ) {
		$tmp_dir = Kohana::$config->load('latex')->tmp_path ;
		$file_util = new DOC_Util_File_Local() ;
		
		if( $handle = opendir( $tmp_dir )) {
			while( FALSE !== ($entry = readdir( $handle ))) {
				if( $entry != '.' && $entry != '..') {
					$file_path = $tmp_dir.$entry ;
					if( filemtime( $file_path ) <= strtotime($older_than)) {
						if( $remove_old_pdf || $file_util->get_mime_type($file_path) != 'application/pdf') {
							@unlink($file_path) ;
						}
					}
				}
			}
			closedir( $handle ) ;
		}
	}
}
