<?php

namespace Edge\Mvc\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class OutputCsv extends AbstractPlugin
{
    /**
     * Convert an array to a CSV an output directly to the browser
     *
     * NB: This function is terminal
     *
     * @param array  $data
     * @param string $filename
     */
    public function __invoke(array $data, $filename)
    {
        $filename = rawurlencode($filename);

        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header("Expires: 0");
        header("Pragma: public");

        $fh = fopen('php://output', 'w');

        $header = false;

        foreach ($data as $row) {
            if (!$header) {
                fputcsv($fh, array_keys($row));
                $header = true;
            }

            fputcsv($fh, $row);
        }
        fclose($fh);

        exit;
    }
}