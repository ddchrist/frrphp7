<?php

/**
 * @file XLS Write Function Library - Version 0.1
 * @author Lourenzo Ferreira
 */

/**
 * The start of a XLS file
 *  
 * @return void
 */
function xlsBOF() { 
    echo pack("ssssss", 0x809, 0x8, 0x0, 0x10, 0x0, 0x0);
} 

/**
 * The end of a XLS file 
 *
 * @return void
 */
function xlsEOF() { 
    echo pack("ss", 0x0A, 0x00);
} 

/**
 * Write a Number
 *
 * @param int $row
 * @param int $col
 * @param float $value
 * 
 * @return void
 */
function xlsWriteNumber($row, $col, $value) { 
    echo pack("sssss", 0x203, 14, $row, $col, 0x0); 
    echo pack("d", $value);
} 

/**
 * Write a text label
 *
 * @param int $row
 * @param int $col
 * @param string $value
 * 
 * @return void
 */
function xlsWriteLabel($row, $col, $value ) { 
    $l = strlen($value); 
    echo pack("ssssss", 0x204, 8 + $l, $row, $col, 0x0, $l); 
    echo $value; 
return; 
}

/**
 * Write a Row
 *
 * @param int $row
 * @param array $values
 * 
 * @return void
 */
function xlsWriteRow($row, $values) {
	$col=0;
	foreach ($values as $value) {
		xlsWriteLabel($row,$col,utf8_decode($value));
		$col++;
	}
}

/**
 * Write and send a XSL to download
 *
 * @param string $filename
 * @param array $header
 * @param array $lines
 */
function xlsDoc($filename, $header, $lines) {
	
	// HTTP HEADER
	header("Pragma: public");
  header("Expires: 0");
  header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
  header("Content-Type: application/force-download");
  header("Content-Type: application/octet-stream");
  header("Content-Type: application/download");;
  header("Content-Disposition: attachment;filename=$filename.xls ");
  header("Content-Transfer-Encoding: binary ");
	
  // BOF
	xlsBOF();
	
	// HEADER
	xlsWriteRow(0,$header);
	
	// LINES
	$row = 1;
	foreach ($lines as $content) {
		xlsWriteRow($row,$content);
		$row++;
	}
	
	// EOF
	xlsEOF();
}