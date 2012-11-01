<?php

namespace Edge\Mail;

class Template {

    const START_TAG = '{{';
    const END_TAG = '}}';

    /**
     * Takes template and replaces fields with variables passed in data array
     *
     * @param string $template text content containing tags to be replaced
     * @param array $data array of tags and replacement content
     * @return string
     */
    public static function parse($template, array $data) {
        foreach ($data as $tag => $value) {
            if (is_string($value)) {
                $template = str_replace(self::START_TAG . $tag . self::END_TAG, $value, $template);
            }
        }

        //clear any unused tag
        $template = preg_replace(sprintf('/%s(\w)+%s/m', preg_quote(self::START_TAG, '/'), preg_quote(self::END_TAG, '/')), '', $template);
        return $template;
    }

}