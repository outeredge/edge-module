<?php

namespace Edge\Service;

class PhantomJsService
{
    protected $bin = 'phantomjs';

    public function __construct($bin = null)
    {
        if (null !== $bin) {
            $this->bin = realpath($bin);;
        }
    }

    public function runCommand()
    {
        $args = array();
        foreach (func_get_args() as $arg) {
            $args[] = escapeshellarg($arg);
        }

        $cmd = escapeshellcmd("{$this->bin} --ignore-ssl-errors=true " . implode(' ', $args));

        $output = array();
        $code = 1;

        $result = exec($cmd, $output, $code);

        if ($code > 0) {
            throw new Exception\RuntimeException(sprintf('Unable to run command. %s', $result));
        }

        return $result;
    }
}
