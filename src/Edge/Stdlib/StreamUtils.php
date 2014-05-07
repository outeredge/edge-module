<?php

namespace Edge\Stdlib;

/**
 * Utility class for accessing file streams
 *
 * Declared abstract, as we have no need for instantiation.
 */
abstract class StreamUtils
{
    /**
     * Get contents of file, uses CURL to fetch a remote path
     *
     * @return string
     */
    public static function file_get_contents($filename)
    {
        if (filter_var($filename, FILTER_VALIDATE_URL) && in_array('curl', get_loaded_extensions())) {
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $filename);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            if (func_num_args() > 1 && is_array(func_get_arg(1))) {
                curl_setopt_array($ch, func_get_arg(1));
            }

            return curl_exec($ch);
        } else {
            return call_user_func_array('file_get_contents', func_get_args());
        }
    }
}