<?php

/**
 * Collection of utility methods for manipulating images
 * beyond what Kohana provides with it's image classes.
 *
 * @author jorrill
 */
class DOC_Util_Image {
	public static function scale_framed( $src_image_path, $target_w, $target_h, $allow_upscale = FALSE ) {
		list($src_w, $src_h, $src_type, $attr) = getimagesize($src_image_path) ;

		$src_image = NULL ;

		switch( $src_type ) {
			case IMAGETYPE_GIF:
				$src_image = imagecreatefromgif($src_image_path) ;
				break ;
			case IMAGETYPE_JPEG:
				$src_image = imagecreatefromjpeg($src_image_path) ;
				break ;
			case IMAGETYPE_PNG:
				$src_image = imagecreatefrompng($src_image_path) ;
				break ;
			default:
				// nothing
		}

		$dst_w = $src_w ;
		$dst_h = $src_h ;

		// if source dimensions are larger than target dimensions, scale down
		if( ($src_w > $target_w || $src_h > $target_h) || $allow_upscale === TRUE ) {
			$scale = min( $target_w/$src_w, $target_h/$src_h ) ;

			$dst_w = $dst_w * $scale ;
			$dst_h = $dst_h * $scale ;			
		} 
		
		
		$dst_x = ($target_w - $dst_w)/2 ;
		$dst_y = ($target_h - $dst_h)/2 ;

		$new_image = imagecreatetruecolor($target_w, $target_h);
		imagealphablending($new_image, FALSE ) ;
		$bg_color = imagecolorallocatealpha($new_image, 0, 0, 0, 127) ;
		imagefilledrectangle($new_image, 0, 0, $target_w - 1, $target_h - 1, $bg_color) ;
		imagealphablending($new_image, TRUE ) ;

		imagecopyresampled($new_image, $src_image, 
				$dst_x, $dst_y, 
				0, 0, 
				$dst_w, $dst_h, 
				$src_w, $src_h
		) ;
		imagealphablending($new_image, TRUE ) ;

		imagealphablending($new_image, FALSE) ;
		imagesavealpha($new_image, TRUE) ;

		imagepng( $new_image, $src_image_path ) ;
	}
}