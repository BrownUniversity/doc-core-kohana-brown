<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of latex
 *
 * @author jorrill
 */
class DOC_Util_LaTeX {
		
	const LATEX_LINE_END = '\\\\\\\\ ' ;
	
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
	 */
	public static function parse_html($html, $supported_tags = DOC_Util_WordHTML::ALLOWABLE_TAGS_DEFAULT) {
		$_output = $html ;

		// todo: add support for hyperlinks
		
		$replacements = array(
			'/<div.*?>(.*?)<\/div>/s' => '$1'.self::LATEX_LINE_END,
			'/<p.*?>(.*?)<\/p>/s' => "$1\n\n",
			'/<br.*?>/' => self::LATEX_LINE_END,
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
	
	public static function create_pdf($latex_str, $filename) {
		$latex_config = Kohana::$config->load('latex') ;
		
		$safe_filename = DOC_Util_File::safe_filename($filename) ;
		$safe_filename = preg_replace('/\.pdf$/','',$safe_filename) ;
		$safe_filename .= '_' . date_format(new DateTime(), 'YmdHisu').Text::random() ;
		
		// write the latex to a temp file -- note that we use DateTime here so that
		// we can get microseconds to reduce the likelihood of collision even further
		$latex_file = "{$latex_config->tmp_path}/{$safe_filename}.tex" ;
		file_put_contents($latex_file,$latex_str) ;

		$pdf_file = "{$latex_config->tmp_path}/{$safe_filename}.pdf" ;
		

		// render the pdf and return the appropriate file info. Note that we 
		// do NOT want a pdf extension on the destination filename in the -jobname
		// argument. pdflatex will add the extension itself.
		//
		// pdflatex -jobname $filename -output-directory [get from php] source.tex
		
		
		$command = "{$latex_config->bin_path}/pdflatex -jobname {$safe_filename} -output-directory {$latex_config->tmp_path} {$latex_file}" ;
				
		$result = exec( $command, $full_result ) ;
		
		$_output = DOC_Util_File::get_file_specs( $pdf_file, $filename ) ;
		
		return $_output ;
		
	}
	
}
