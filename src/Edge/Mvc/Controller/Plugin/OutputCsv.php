<?php

namespace Edge\Mvc\Controller\Plugin;

use Edge\Exception;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class OutputCsv extends AbstractPlugin
{
    /**
     * Convert an array to a CSV and output directly to the browser
     *
     * NB: This function is terminal
     *
     * @param array  $data
     * @param string $filename
     * @param array $headerkeys [optional]
     */
    public function __invoke(array $data, $filename, array $headerkeys = null)
    {
        if (empty($data)) {
            throw new Exception\RuntimeException('No data found for CSV output');
        }

        $filename = rawurlencode($filename);

        if ($headerkeys) {
            $headers    = array_fill_keys(array_flip($headerkeys), null);
        } else {
            $headerkeys = array_keys(reset($data));
            $headers    = array_fill_keys($headerkeys, null);
        }

        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Expires: 0");
        header("Pragma: public");

        $fh = fopen('php://output', 'w');

        $header = false;
        foreach ($data as $row) {
            if (!$header) {
                fputcsv($fh, $headerkeys);
                $header = true;
            }
            fputcsv($fh, array_merge($headers, $row));
        }

        fclose($fh);

        exit;
    }
}
