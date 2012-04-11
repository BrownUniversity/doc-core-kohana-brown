<?php

require Kohana::find_file('classes', 'PHPExcel');

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * A utility class to ease working with spreadsheets.
 *
 * @author jorrill
 */
class DOC_Util_Spreadsheet {

	const FILETYPE_PDF = 'PDF' ;
	const FILETYPE_EXCEL = 'Excel5' ;
	const FILETYPE_EXCEL_2007 = 'Excel2007' ;
	const FILETYPE_HTML = 'HTML' ;

	/**
	 * Given a set of data, returns a spreadsheet object. Note that this makes
	 * use of the Table class, and uses the same type of formatting data that we
	 * would use when sending data to the browser.
	 *
	 * @param Database_Result $data
	 * @param array $format
	 * @return PHPExcel
	 * @todo Move the Util_Table code elsewhere so that this takes just rendered HTML.
	 */
	public static function spreadsheet_via_table( $table_html ) {

		$obj_phpexcel = new PHPExcel() ;
		$obj_phpexcel->setActiveSheetIndex(0) ;
		$active_sheet = $obj_phpexcel->getActiveSheet() ;
		$row_index = 1 ;

		$dom = new DomDocument() ;
		@$dom->loadHTML($table_html) ;
		$ths = $dom->getElementsByTagName('thead')->item(0)->getElementsByTagName('th') ;
		for( $i = 0; $i < $ths->length; $i++ ) {
			$active_sheet->setCellValueByColumnAndRow( $i, $row_index, $ths->item($i)->nodeValue ) ;
		}

		$trs = $dom->getElementsByTagName('tbody')->item(0)->getElementsByTagName('tr') ;
		foreach( $trs as $tr ) {
			$row_index++ ;
			$tds = $tr->getElementsByTagName('td') ;
			for( $i = 0; $i < $tds->length; $i++ ) {
				$active_sheet->setCellValueByColumnAndRow( $i, $row_index, $tds->item($i)->nodeValue ) ;


				if( $tds->item($i)->hasAttribute( 'class' )) {

					switch( $tds->item($i)->getAttribute('class')) {
						case 'datetime':
							$active_sheet
									->getStyleByColumnAndRow($i, $row_index)
									->getNumberFormat()
									->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_DATETIME) ;
							break ;

						case 'dollars':
							$active_sheet
									->getStyleByColumnAndRow($i, $row_index)
									->getNumberFormat()
									->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD) ;
							break ;

						case 'wrap':
							$active_sheet->getStyleByColumnAndRow($i, $row_index)->getAlignment()->setWrapText(TRUE);
							break ;

						default:
							// do nothing
					}
				}
			}
		}

		return $obj_phpexcel ;

	}

	/**
	 * Download a spreadsheet file to the user. If no filename is specified,
	 * one will be generated based on the current URI and datetime. Defaults to
	 * Excel, but can optionally generate other formats (file types) as well.
	 *
	 * @param PHPExcel $obj_phpexcel
	 * @param string $filename
	 * @param string $file_type Use one of the class constants.
	 */
	public static function download( $obj_phpexcel, $filename = NULL, $file_type = self::FILETYPE_EXCEL ) {
		if( empty( $filename )) {
			$filename = preg_replace('/\W/', '_', Request::detect_uri()) ;
			$filename .= '_'. date('Y-m-d_H:i') ;
		}

		switch( $file_type ) {
			case self::FILETYPE_EXCEL:
				header( 'Content-Type: application/vnd.ms-excel' ) ;
				header( 'Content-Disposition: attachment;filename="'.$filename.'.xls"' ) ;
				header( 'Cache-Control: max-age=0' ) ;

				break ;
			case self::FILETYPE_EXCEL_2007:
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"') ;
				header('Cache-Control: max-age=0');
				break ;

			case self::FILETYPE_PDF:
				header( 'Content-Type: application/pdf' ) ;
				header( 'Content-Disposition: attachment;filename="'.$filename.'.pdf"' ) ;
				header( 'Cache-Control: max-age=0' ) ;

				break ;

			case self::FILETYPE_HTML:
				header( 'Content-Type: text/html' ) ;
				header( 'Content-Disposition: attachment;filename="'.$filename.'.html"' ) ;
				header( 'Cache-Control: max-age=0' ) ;

				break ;
			default:
				die('unsupported file type') ;
		}

		$phpexcel_writer = PHPExcel_IOFactory::createWriter($obj_phpexcel, $file_type) ;
//		$phpexcel_writer->save('/www/vhosts/appscollege.cis-dev.brown.edu/phpexcel_out/'.$filename.'.xls');
		$phpexcel_writer->save('php://output') ;
		exit() ;
	}

}
