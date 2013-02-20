<?php

namespace Edge\Service;

class PhantomJsService
{
    protected $bin = 'phantomjs';

    public function __construct($bin = null)
    {
        if (null !== $bin) {
            $this->bin = $bin;
        }
    }

    public function runCommand()
    {
        $args = func_get_args();
        $cmd = escapeshellcmd("{$this->bin} " . implode(' ', $args));
        $result = exec($cmd, $output, $code);

        if ($code > 0) {
            throw new \RuntimeException(sprintf('Unable to run command. %s', $result));
        }

        return $result;
    }
}