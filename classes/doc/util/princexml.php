<?php

/**
 * Utility class for generating PDF documents using Brown's PrinceXML PDF generation service.
 */
class DOC_Util_PrinceXML
{
	/**
	 * Given HTML, a filename and other specifications, generate a PDF file and return the file information
	 * so that it can be further processed or downloaded.
	 *
	 * @param       $html
	 * @param string $filename The desired filename.
	 * @param array $css An array of URLs for stylesheets.
	 * @param array $js An array of URLs for javascript code.
	 * @param bool  $run_cleanup
	 * @return array File description array, matching what we get from php's $_FILES
	 */
	public static function create_pdf( $html, $filename, $css = array(), $js = array(), $run_cleanup = TRUE ) {
		if( $run_cleanup === TRUE ) {
			self::cleanup() ;
		}

		if( substr(trim($filename),-4) != '.pdf') {
			$filename = trim($filename).'.pdf' ;
		}

		$prince_config = Kohana::$config->load('pdfgen') ;

		$safe_filename = DOC_Util_File::safe_filename($filename) ;
		$safe_filename = preg_replace( '/\.pdf$/', '', $safe_filename ) ;
		$safe_filename .= '_' . date('YmdHis').Text::random() ;

		$pdfGen = new \BrownUniversity\Pdfgen\Pdfgen($prince_config->as_array(), null) ;

		$pdf_file = $pdfGen->convert($html, $css, $js) ;

		return DOC_Util_File::get_file_specs( $pdf_file, $filename ) ;
	}

	/**
	 * Go through prince temp directory and remove files older than the specified age.
	 *
	 * @param string $older_than String suitable for input to strtotime()
	 */
	public static function cleanup($older_than = '-1 hour') {
		$tmp_dir = Kohana::$config->load('pdfgen')->tmp_path ;
		$file_util = new DOC_Util_File_Local() ;

		if( $handle = opendir( $tmp_dir )) {
			while( FALSE !== ($entry = readdir( $handle ))) {
				if( $entry != '.' && $entry != '..') {
					$file_path = $tmp_dir.$entry ;
					if( filemtime( $file_path ) <= strtotime($older_than)) {
						@unlink($file_path) ;
					}
				}
			}
			closedir( $handle ) ;
		}
	}
}