<?php
namespace BrownUniversity\DOC\Util ;
/**
 * @module Advising Sidekick (ASK)
 * @version 2.0
 * @author Christopher Keith <Christopher_Keith@brown.edu>
 */
defined('SYSPATH') or die('No direct script access.');

/**
 * Utility functions for Amazon S3 functionality
 * @deprecated Use DOC_Util_File_S3 instead.
 */
class S3 {

    private static $include_path = NULL;

    /**
     * Remove a file from a particular bucket
     *
     * @param string $bucket
     * @param string $filename
     */
    public static function delete($bucket, $filename)
    {
       $s3 = self::init();
       $response = $s3->delete_object($bucket, $filename);

       return $response->isOK();
    }

    /**
     * Download a file from a bucket from AWS S3
     *
     * @param string $bucket
     * @param string $filename
     */
    public static function get($bucket, $filename, $new_name = NULL)
    {
        if ($new_name == NULL) $new_name = $filename;

        $s3 = self::init();

        $headers = $s3->get_object_headers($bucket, $filename);
        $info = $headers->header['_info'];

        $file = $s3->get_object(
            $bucket,
            $filename,
            array(
                'returnCurlHandle' => TRUE
            )
        );


        //@todo: add error trapping
        header('Content-Type: ' . $info['content_type']);
        header('Content-Disposition: attachment; filename="'. $new_name .'"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.sprintf('%d', $info['download_content_length']));

        curl_setopt($file, CURLOPT_HEADER, FALSE);
        curl_setopt($file, CURLOPT_RETURNTRANSFER, FALSE);
        curl_exec($file);
    }

    /**
     * Create an instance of an Cloudfusion S3 object
     */
    public static function init()
    {
        /**
         * Determine path to Amazon helper classes
         */
        if (self::$include_path == NULL) {
            self::$include_path = \Kohana::find_file('classes', 'sdk-1.4.7/sdk.class');
        }
        require_once(self::$include_path);
		$aws_config = \Kohana::$config->load('aws') ;
        return new AmazonS3($aws_config[ 'key' ], $aws_config[ 'secret' ]);
    }

    /**
     * Create a new s3 asset
     *
     * @param string $bucket
     * @param string $filename
     * @param string $path
     * @param array $attributes
     */
    public static function put($bucket, $filename, $path, $attributes = NULL)
    {
        $s3 = self::init();

        /**
         * Process attributes
         */
        if (is_array($attributes)) {
            $attributes['fileUpload'] = $path;
        } else {
            $attributes = array('fileUpload' => $path);
        }
        $response = $s3->create_object($bucket, $filename, $attributes);

        return $response->isOK();
    }

} // End S3 Helper