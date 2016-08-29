<?php

/**
 * Collection of utility methods for generating LaTeX documents.
 *
 * @author jorrill
 */
class DOC_Util_LaTeX {
		
	const LATEX_LINE_END = '\\\\\\\\ ' ;
	
    public static $line_end = "~\\\\\n";
    
	/**
	 * HTML Entity translation table
	 
	 * @var array
	 */
	public static $html_entities = array(
        
        // Reserved Characters in HTML
		'&quot;'      => '"', // quotation mark
		'&apos;'      => ' ', // apostrophe
		'&amp;'       => ' ', // ampersand
		'&lt;'        => ' ', // less-than
		'&gt;'        => ' ', // greater-than
	
		// ISO 8859-1 Symbols
		'&nbsp;'      => ' ', // non-breaking space
		'&iexcl;'     => '\textexclamdown{}', // inverted exclamation mark
		'&cent;'      => ' ', // cent
		'&pound;'     => ' ', // pound
		'&curren;'    => ' ', // currency
		'&yen;'       => ' ', // yen,
		'&brvbar;'    => ' ', // broken vertical bar
		'&sect;'      => ' ', // section
		'&uml;'       => ' ', // spacing diaeresis
		'&copy;'      => '\copyright{}', // copyright
		'&ordf;'      => ' ', // feminine ordinal indicator
		'&laquo;'     => ' ', // angle quotation mark (left)
		'&not;'       => ' ', // negation
		'&shy;'       => ' ', // soft hyphen
		'&reg;'       => ' ', // registered trademark
		'&macr;'      => '\={}', // spacing macron
		'&deg;'       => '$^{\circ}$', // degree
		'&plusmn;'    => '${\pm}$', // plus-or-minus
		'&sup2;'      => '$^{2}$', // superscript 2
		'&sup3;'      => '$^{3}$', // superscript 3
		'&acute;'     => "\'{}", // spacing acute
		'&micro;'     => ' ', // micro
		'&para;'      => ' ', // paragraph
		'&middot;'    => '\textbullet{}', // middle dot
		'&cedil;'     => ' ', // spacing cedilla
		'&sup1;'      => '$^{1}$', // superscript 1
		'&ordm;'      => ' ', // masculine ordinal indicator
		'&raquo;'     => ' ', // angle quotaion mark (right)
		'&frac14;'    => '$\frac{1}{4}$', // fraction 1/4
		'&frac12;'    => '$\frac{1}{2}$', // fraction 1/2
		'&frac34;'    => '$\frac{3}{4}$', // fraction 3/4
		'&iquest;'    => ' ', // inverted question mark
		'&times;'     => ' ', // multiplication
		'&divide;'    => ' ', // division
	
		// ISO 8859-1 Characters
		'&Agrave;'    => '\`{A}', // capital a, grave accent
		'&Aacute;'    => "\'{A}", // capital a, acute accent
		'&Acirc;'     => '\^{A}', // capital a, circumflex accent
		'&Atilde;'    => '\~{A}', // capital a, tilde
		'&Auml;'      => '\"{A}', // capital a, umlaut mark
		'&Aring;'     => '\r{A}', // capital a, ring
		'&AElig;'     => '\AE', // capital ae
		'&Ccedil;'    => '\c{C}', // capital c, cedilla
		'&Egrave;'    => '\`{E}', // capital e, grave accent
		'&Eacute;'    => "\'{E}", // capital e, acute accent
		'&Ecirc;'     => '\^{E}', // capital e, circumflex accent
		'&Euml;'      => '\"{E}', // capital e, umlaut mark
		'&Igrave;'    => '\`{I}', // capital i, grave accent
		'&Iacute;'    => "\'{I}", // capital i, accuate accent
		'&Icirc;'     => '\^{I}', // capital i, circumflex accent
		'&Iuml;'      => '\"{I}', // capital i, umlaut mark
		'&ETH;'       => ' ', // capital eth, Icelandic
		'&Ntilde;'    => '\~{N}', // capital n, tilde
		'&Ograve;'    => '\`{O}', // capital o, grave accent
		'&Oacute;'    => "\'{O}", // capital o, acute accent
		'&Ocirc;'     => '\^{O}', // capital o, circumflex accent
		'&Otilde;'    => '\~{O}', // capital o, tilde
		'&Ouml;'      => '\"{O}', // capital o, umlaut mark
		'&Oslash;'    => '\O', // capital o, slash
		'&Ugrave;'    => '\`{U}', // capital u, grave accent
		'&Uacute;'    => "\'{U}", // capital u, acute accent
		'&Ucirc;'     => '\^{U}', // capital u, circumflex accent
		'&Uuml;'      => '\"{U}', // capital u, umlaut mark
		'&Yacute;'    => '\'{Y}', // capital y, acute accent
		'&THORN;'     => ' ', // capital THORN, Icelandic
		'&szlig;'     => ' ', // small sharp s, German
		'&agrave;'    => '\`{a}', // small a, grave accent
		'&aacute;'    => "\'{a}", // small a, accute accent
		'&acirc;'     => '\^{a}', // small a, circumflex accent
		'&atilde;'    => '\~{a}', // small a, tilde
		'&auml;'      => '\"{a}', // small a, umlaut mark
		'&aring;'     => '\r{a}', // small a, ring
		'&aelig;'     => '\ae', // small ae
		'&ccedil;'    => '\c{c}', // small c, cedilla
		'&egrave;'    => '\`{e}', // small e, grave accent
		'&eacute;'    => "\'{e}", // small e, acute accent
		'&ecirc;'     => '\^{e}', // small e, circumflex accent
		'&euml;'      => '\"{e}', // small e, umlaut mark
		'&igrave;'    => '\`{i}', // small i, grave accent
		'&iacute;'    => "\'{i}", // small i, acute accent
		'&icirc;'     => '\^{I}', // small i, circumflex accent
		'&iuml;'      => '\"{I}', // small i, umlaut mark
		'&eth;'       => ' ', // small eth, Icelandic
		'&ntilde;'    => '\~{n}', // small n, tilde
		'&ograve;'    => '\`{o}', // small o, grave accent
		'&oacute;'    => "\'{o}", // small o, acute accent
		'&ocirc;'     => '\^{o}', // small o, circumflex accent
		'&otilde;'    => '\~{o}', // small o, tilde
		'&ouml;'      => '\"{o}', // small o, umlaut mark
		'&oslash;'    => '\o', // small o, slash
		'&ugrave;'    => '\`{u}', // small u, grave accent
		'&uacute;'    => "\'{u}", // small u, acute accent
		'&ucirc;'     => '\^{u}', // small u, circumflex accent
		'&uuml;'      => '\"{u}', // small u, umlaut mark
		'&yacute;'    => '\'{y}', // small y, acute accent
		'&thorn;'     => ' ', // small thorn, Icelandic
		'&yuml;'      => '\"{y}', // small y, umlaut mark
	
		// Math Symbols Supported by HTML
		'&forall;'    => ' ', // for all
		'&part;'      => ' ', // part
		'&exist;'     => ' ', // exists
		'&empty;'     => ' ', // empty
		'&nabla;'     => ' ', // nabla
		'&isin;'      => ' ', // isin
		'&notin;'     => ' ', // notin
		'&ni;'        => ' ', // ni
		'&prod;'      => ' ', // prod
		'&sum;'       => ' ', // sum
		'&minus;'     => ' ', // minus
		'&lowast;'    => ' ', // lowast
		'&radic;'     => ' ', // square root
		'&prop;'      => ' ', // proportional to
		'&infin;'     => ' ', // infinity
		'&ang;'       => ' ', // angle
		'&and;'       => ' ', // and
		'&or;'        => ' ', // or
		'&cap;'       => ' ', // cap
		'&cup;'       => ' ', // cup
		'&int;'       => ' ', // integral
		'&there4;'    => ' ', // therefore
		'&sim;'       => ' ', // similar to
		'&cong;'      => ' ', // congruent to
		'&asymp;'     => ' ', // almost equal
		'&ne;'        => ' ', // not equal
		'&equiv;'     => ' ', // equivalent
		'&le;'        => ' ', // less or equal
		'&ge;'        => ' ', // greater or equal
		'&sub;'       => ' ', // subset of
		'&sup;'       => ' ', // superset of
		'&nsub;'      => ' ', // not subset of
		'&sube;'      => ' ', // subset or equal
		'&supe;'      => ' ', // superset or equal
		'&oplus;'     => ' ', // circled plus
		'&otimes;'    => ' ', // circled times
		'&perp;'      => ' ', // perpendicular
		'&sdot;'      => ' ', // dot operator
	
		// Greek Letters Supported by HTML
		'&Alpha;'     => ' ', // Alpha
		'&Beta;'      => ' ', // Beta
		'&Gamma;'     => ' ', // Gamma
		'&Delta;'     => ' ', // Delta
		'&Epsilon;'   => ' ', // Epsilon
		'&Zeta;'      => ' ', // Zeta
		'&Eta;'       => ' ', // Eta
		'&Theta;'     => ' ', // Theta
		'&Iota;'      => ' ', // Iota
		'&Kappa;'     => ' ', // Kappa
		'&Lambda;'    => ' ', // Lambda
		'&Mu;'        => ' ', // Mu
		'&Nu;'        => ' ', // Nu
		'&Xi;'        => ' ', // Xi
		'&Omnicron;'  => ' ', // Omnicron
		'&Pi;'        => ' ', // Pi
		'&Rho;'       => ' ', // Rho
		'&Sigma;'     => ' ', // Sigma
		'&Tau;'       => ' ', // Tau
		'&Upsilon;'   => ' ', // Upsilon
		'&Phi;'       => ' ', // Phi
		'&Chil;'      => ' ', // Chi
		'&Psi;'       => ' ', // Psi
		'&Omega;'     => ' ', // Omega
		'&alpha;'     => ' ', // alpha
		'&beta;'      => ' ', // beta
		'&gamma;'     => ' ', // gamma
		'&delta;'     => ' ', // delta
		'&epsilon;'   => ' ', // epsilon
		'&zeta;'      => ' ', // zeta
		'&eta;'       => ' ', // eta
		'&theta;'     => ' ', // theta
		'&iota;'      => ' ', // iota
		'&kappa;'     => ' ', // kappa
		'&lambda;'    => ' ', // lambda
		'&mu;'        => ' ', // mu;
		'&nu;'        => ' ', // nu;
		'&xi;'        => ' ', // xi;
		'&omnicron;'  => ' ', // omnicron
		'&pi;'        => ' ', // pi
		'&rho;'       => ' ', // rho
		'&sigmaf;'    => ' ', // sigmaf
		'&sigma;'     => ' ', // sigma
		'&tau;'       => ' ', // tau
		'&upsilon;'   => ' ', // upsilon
		'&phi;'       => ' ', // phi
		'&chi;'       => ' ', // chi
		'&psi;'       => ' ', // psi
		'&omega;'     => ' ', // omega
		'&thetasym;'  => ' ', // theta symbol
		'&upsih;'     => ' ', // upsilon symbol
		'&piv;'       => ' ', // pi symbol
	
		// Other Entities Supported by HTML
		'&OElig;'     => ' ', // capital ligature OE
		'&oelig;'     => ' ', // small igature oe
		'&Scaron;'    => ' ', // capital s with caron
		'&scaron;'    => ' ', // small s with caron
		'&Yuml;'      => ' ', // capital y with diaeres
		'&fnof;'      => ' ', // f with hook
		'&circ;'      => ' ', // modifier letter circumflex accent
		'&tilde;'     => ' ', // small tilde
		'&ensp;'      => ' ', // en space
		'&emsp;'      => ' ', // em space
		'&thinsp;'    => ' ', // thin space
		'&zwnj;'      => ' ', // zero width non-joiner
		'&zwj;'       => ' ', // zero width joiner
		'&lrm;'       => ' ', // left-to-right mark
		'&rlm;'       => ' ', // right-to-left mark
		'&ndash;'     => '\textemdash{}', // en dash
		'&mdash;'     => '\textemdash{}', // em dash
		'&lsquo;'     => "'", // left single quotation mark
		'&rsquo;'     => "'", // right single quotation mark
		'&sbquo;'     => ' ', // single low-9 quotation mark
		'&ldquo;'     => '"', // left double quotation mark
		'&rdquo;'     => '"', // right double quotation mark
		'&bdquo;'     => ' ', // double low-9 quotation mark
		'&dagger;'    => ' ', // dagger
		'&Dagger;'    => ' ', // double dagger
		'&bull;'      => '\textbullet{}', // bullet
		'&hellip;'    => ' ', // horizontal ellipsis
		'&permil;'    => ' ', // per mile
		'&prime;'     => ' ', // minutes
		'&Prime;'     => ' ', // seconds
		'&lsaquo;'    => ' ', // single left angle quotation
		'&rsaquo;'    => ' ', // singlre right angle quotation
		'&oline;'     => ' ', // overline
		'&euro;'      => ' ', // euro
		'&trade;'     => ' ', // trademark
		'&larr;'      => ' ', // left arrow
		'&uarr;'      => ' ', // up arrow
		'&rarr;'      => ' ', // right arrow
		'&darr;'      => ' ', // down arrow
		'&harr;'      => ' ', // left right arrow
		'&crarr;'     => ' ', // carriage return arrow
		'&lceil;'     => ' ', // left ceiling
		'&rceil;'     => ' ', // right ceiling
		'&lfloor;'    => ' ', // left floor
		'&rfloor;'    => ' ', // right floor
		'&loz;'       => ' ', // lozenge
		'&spades;'    => ' ', // spade
		'&clubs;'     => ' ', // club
		'&hearts;'    => ' ', // heart
		'&diams;'     => ' ', // diamond
	
		// Other
		
		
	);
	
	/**
	 * Removed character references that cannot be translated to latex
	 *
	 * @param input string
	 * @return string
	 */
	public static function fix_bad_utf8($input) {
		$replacements = array(
			'&#128;',
            '&#129;',
            '&#130;',
            '&#131;',
            '&#132;',
            '&#133;',
            '&#134;',
            '&#135;',
			'&#136;',
			'&#137;',
			'&#138;',
            '&#139;',
            '&#140;',
			'&#141;',
			'&#142;',
			'&#143;', 
			'&#144;',
			'&#145;',
            '&#146;',
            '&#147;',
            '&#148;',
            '&#149;',
            '&#150;',
            '&#151;',
            '&#152;',
            '&#153;', 
            '&#154;',
            '&#155;',
            '&#156;',
            '&#157;',
            '&#158;',
            '&#159;',
		);
		
		return str_replace($replacements, ' ', $input);
	}
	
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
	public static function parse_html($html, $supported_tags = DOC_Util_WordHTML::ALLOWABLE_TAGS_DEFAULT, $plain_text_input = FALSE) {
		$_output = $html ;

		$_output = utf8_encode( $_output ) ;

		$replacements = array(
            '/\t/s' => '',
			'/<div.*?>(.*?)<\/div>/s' => '$1'.self::LATEX_LINE_END . PHP_EOL,
			'/<p.*?>(.*?)<\/p>/s' => "$1\n\n",
			// LaTeX doesn't like seeing a line break with no other content on the line,
			// which this might otherwise allow, so we'll stick a non-breaking space in front of it
			'/<blockquote>(.*?)<\/blockquote>(\s|<br.*?>)*/s' => '\begin{quotation}$1\end{quotation}' . PHP_EOL,
			'/<ol.*?>(\s|<br.*?>)*(.*?)<\/ol>(\s|<br.*?>)*/s' => '\begin{enumerate}' . PHP_EOL . '$2\end{enumerate}' . PHP_EOL,
			'/<ul.*?>(\s|<br.*?>)*(.*?)<\/ul>(\s|<br.*?>)*/s' => '\begin{itemize}' . PHP_EOL . '$2\end{itemize}' . PHP_EOL,
			'/<br.*?>/' => self::LATEX_LINE_END . PHP_EOL,
			'/<strong>(.*?)<\/strong>/s' => '\textbf{$1}',
			'/<b>(.*?)<\/b>/s' => '\textbf{$1}',
			'/<em>(.*?)<\/em>/s' => '\textit{$1}',
			'/<i>(.*?)<\/i>/s' => '\textit{$1}',
			'/<u>(.*?)<\/u>/s' => '\underline{$1}',
			'/<del>(.*?)<\/del>/s' => '\sout{$1}',
			'/<strike>(.*?)<\/strike>/s' => '\sout{$1}',
			'/<li.*?>(.*?)<\/li>/s' => '\item $1' . PHP_EOL,
		) ;		
			
		// strip out any tags we don't support
		$_output = DOC_Util_WordHTML::clean($_output) ;

		// deal with the usual smart quote headache and cousins
		$_output = DOC_Util_WordHTML::convert_problem_chars($_output) ;

		// tidy the document
		$_output = DOC_Util_WordHTML::domdocument_tidy($_output) ;

        // run through html_entity_decode
		$_output = html_entity_decode($_output, ENT_NOQUOTES, 'UTF-8') ;

        $_output = self::fix_bad_utf8($_output) ;

		// tighten up any extra whitespace
		
		if ($plain_text_input == FALSE) {
			$_output = preg_replace('/(\s|\n){2,}/s',' ',$_output) ;
		}
		
		$_output = preg_replace("~>\n*\s*\n*<~", '><', $_output) ;
		$_output = trim( $_output ) ;

		// deal with most characters LaTeX needs modified
		
		$_output = self::latex_special_chars($_output) ;
//		print("<pre>{$_output}</pre>") ; die() ;

        // remove any empty blockquotes, since they run the risk of making LaTeX crabby
		$_output = preg_replace_callback('/<blockquote>(.*?)<\/blockquote>/','DOC_Util_LaTeX::strip_empty',$_output) ;

        // remove any empty paragraphs, these definitely make LaTeX crabby
        $_output = preg_replace_callback('/<p>(.*?)<\/p>/', 'DOC_Util_LaTeX::strip_empty', $_output);
		$_output = preg_replace_callback('/<div>(.*?)<\/div>/', 'DOC_Util_LaTeX::strip_empty', $_output);


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
					if ($plain_text_input) {
						$replacement = str_replace("\n\n", '\newline' . PHP_EOL . '\newline' . PHP_EOL, $match) ;
					} else {
						$replacement = str_replace("\n\n", '\\\\' . PHP_EOL . '\\\\' . PHP_EOL, $match) ;
					}
					$_output = str_replace($match, $replacement, $_output) ;
				}
			}
		}
		
		// Having a line break at the very end can cause problems if it's part 
		// of a \begin{x}\end{x} block, so we'll get rid of those.
		$_output = preg_replace('/\n\\\\\\\\\s+$/','',$_output) ;

		// We get best results by specifying the UTF-8 character set, but sometimes
		// get errors. Catch those and try again with default options.
		try {
			$_output = htmlentities($_output, ENT_COMPAT | ENT_IGNORE, 'UTF-8');
		} catch( ErrorException $e ) {
			$_output = htmlentities($_output);
		}
//      print("<pre>{$_output}</pre>") ; die() ;
        $_output = self::latex_html_entities($_output) ;
//		print("<pre>{$_output}</pre>") ; die() ;

		// remove multiple line breaks (again?)
		$_output = preg_replace( "/\n{3,}/", "\n\n", $_output ) ;
		
        // remove LaTeX line breaks after a curly brace
		$_output = preg_replace('/\}\n(\\\\\\\\\s*\n)+/m', '}'.PHP_EOL, $_output) ;
		
		// Blank lines followed by backslashy line ends are also a problem.
		// Turn those into just backslashy line ends.
		$_output = preg_replace('/^\s*\n(\\\\\\\\\s*\n){2,}/m', '$1$1', $_output) ;
		
        return $_output ;
	}
	
    /**
     * Convert HTML Entities to appropriate plaintext replacements that we can 
     * get LaTeX to support via UTF-8
     * 
     * @param string $str
     * @return string
     */
    public static function latex_html_entities($str) {
        
        return str_replace(array_keys( self::$html_entities ), array_values( self::$html_entities ), $str) ;
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
			'\textbackslash' => '{\textbackslash}',
            '[' => '{[}',
            ']' => '{]}',
            
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
		
		if( substr(trim($filename),-4) != '.pdf') {
			$filename = trim($filename).'.pdf' ;
		}
		
		
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
		Kohana::$log->add(Log::DEBUG, "LaTeX command: {$command}") ;
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
    
    /**
     * Remove line breaks from a string
     * 
     * @param string $input
     * @return string
     */
    public static function remove_breaks($input) {
        return str_replace(array("\n","<br>","<br >","<br />", "<br/>"), '', $input);
    }
}
