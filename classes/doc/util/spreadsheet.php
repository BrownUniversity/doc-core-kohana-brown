<?php

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
     * Create a representation of an XLSX Cognos report
     *
     * @param type $path location of file to ingest
     * @return array
     */
    public static function read_cognos( $path ) {
        $reader = new PHPExcel_Reader_Excel2007();
        $excel = $reader->load($path);

        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();

        $values = array();
        foreach ($sheet->getRowIterator() as $row) {
            set_time_limit(10);
            $inner = array();
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(FALSE);
            foreach ($cells as $cell) {
                $inner[] = $cell->getValue();
            }
            $values[] = $inner;
        }

        return array_slice($values, 2, count($values) -3);
    }

    /**
     * Read an arbitrary excel 2007 document into memory
     * 
     * @param string $path
     * @param int $ignore_rows
     * @param int $column_count
     * @return array
     */
    public static function read_spreadsheet( $path, $ignore_rows = 0, $column_count = NULL ) {
        $reader = new PHPExcel_Reader_Excel2007();
        $excel = $reader->load($path);
        
        $excel->setActiveSheetIndex(0);
        $sheet = $excel->getActiveSheet();
        
        $values = array();
        
        $row_count = 0;
        
        foreach ($sheet->getRowIterator() as $row) {
            set_time_limit(0);
            $row_count++;
            $inner = array();
            $cells = $row->getCellIterator();
            $cells->setIterateOnlyExistingCells(FALSE);
            $cell_count = 0;
            foreach($cells as $cell) {
                $cell_count++;
                if (($column_count != NULL) && ($cell_count > $column_count)) {
                    continue;
                } else {
                    $inner[] = $cell->getValue();
                }
            }
            
            if ($row_count > $ignore_rows) {
                $values[] = $inner;
            }
        }
        
        return $values;
    }
    
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

		$thead = $dom->getElementsByTagName('thead') ;
		if( $thead->length > 0 ) {
			$ths = $thead->item(0)->getElementsByTagName('th') ;
			if( $ths->length > 0 ) {
				for( $i = 0; $i < $ths->length; $i++ ) {
					$active_sheet->setCellValueByColumnAndRow( $i, $row_index, $ths->item($i)->nodeValue ) ;
				}
			} else {
				$active_sheet->setCellValueByColumnAndRow( 0, $row_index, 'No header columns' ) ;
			}
		} else {
			$active_sheet->setCellValueByColumnAndRow( 0, $row_index, 'No header row' ) ;
		}

		$tbody = $dom->getElementsByTagName('tbody') ;
		if( $tbody->length > 0 ) {
			$trs = $tbody->item(0)->getElementsByTagName('tr') ;
			if( $trs->length > 0 ) {
				foreach( $trs as $tr ) {
					set_time_limit(10);
					$row_index++ ;
					$tds = $tr->getElementsByTagName('td') ;
					for( $i = 0; $i < $tds->length; $i++ ) {
						$cell_node = $tds->item($i) ;
						$cell_value = $dom->saveXML($cell_node) ;
						$cell_value = str_replace('&gt;', '>', $cell_value) ;
						$cell_value = str_replace('&lt;', '<', $cell_value) ;
						$cell_value = preg_replace('/<\/?((p)|(br)|(div)).*?\/?>/',"\r", $cell_value ) ; 
						$cell_value = DOC_Util_WordHTML::clean($cell_value,'') ;

  						$active_sheet->setCellValueByColumnAndRow( $i, $row_index, $cell_value ) ;

						if( $tds->item($i)->hasAttribute( 'class' )) {
                            
                            $classes = explode(' ', $tds->item($i)->getAttribute('class'));
                            
                            foreach ($classes as $c) {
                                switch( $c ) {
                                    case 'datetime':
                                        $active_sheet
                                                ->getStyleByColumnAndRow($i, $row_index)
                                                ->getNumberFormat()
                                                ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_XLSX22) ;
                                        break ;

                                    case 'dollars':
                                        $active_sheet
                                                ->getStyleByColumnAndRow($i, $row_index)
                                                ->getNumberFormat()
                                                ->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_CURRENCY_USD) ;
                                        break ;

                                    case 'xls-text':
                                        $active_sheet
                                            ->getCellByColumnAndRow($i, $row_index)
                                            ->setValueExplicit($cell_value, PHPExcel_Cell_DataType::TYPE_STRING);
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
				}
			} else {
				$row_index++ ;
				$active_sheet->setCellValueByColumnAndRow( 0, $row_index, 'No data' ) ;
			}
		} else {
			$row_index++ ;
			$active_sheet->setCellValueByColumnAndRow( 0, $row_index, 'No data') ;
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
		
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); // some day in the past
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header( "Cache-Control: no-store, no-cache, must-revalidate, max-age=0" );
		header( "Cache-Control: post-check=0, pre-check=0", false);
		header( "Pragma: no-cache" );
		
		switch( $file_type ) {
			case self::FILETYPE_EXCEL:
				header( 'Content-Type: application/vnd.ms-excel' ) ;
				header( 'Content-Disposition: attachment;filename="'.$filename.'.xls"' ) ;

				break ;
			case self::FILETYPE_EXCEL_2007:
				header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
				header('Content-Disposition: attachment;filename="'.$filename.'.xlsx"') ;
				break ;

			case self::FILETYPE_PDF:
				header( 'Content-Type: application/pdf' ) ;
				header( 'Content-Disposition: attachment;filename="'.$filename.'.pdf"' ) ;

				break ;

			case self::FILETYPE_HTML:
				header( 'Content-Type: text/html' ) ;
				header( 'Content-Disposition: attachment;filename="'.$filename.'.html"' ) ;

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
// End DOC_Util_Spreadsheet
