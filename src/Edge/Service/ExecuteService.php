<?php

namespace Edge\Service;

class ExecuteService
{
    protected $command;

    public function __construct($command)
    {
        $realpath = realpath($command);
        $this->command = $realpath ? $realpath : $command;
    }

    /**
     * Exec command and return the last output
     *
     * @return string
     * @throws Exception\RuntimeException
     */
    public function runCommand()
    {
        $output = array();
        $code = 1;

        $result = exec($this->getCommand(func_get_args()), $output, $code);

        if ($code > 0) {
            throw new Exception\RuntimeException(sprintf('Unable to run command. %s', $result));
        }

        return $result;
    }

    /**
     * Pipe first given argument to the command and append the rest as arguments
     *
     * @return string Resulting output
     * @throws Exception\RuntimeException
     */
    public function pipeCommand()
    {
        $input = null;
        $args  = [];
        $pipes = [];

        foreach (func_get_args() as $key => $arg) {
            if ($key == 0) {
                $input  = $arg;
            } else {
                $args[] = $arg;
            }
        }

        $process = proc_open(
            $this->getCommand($args),
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new Exception\RuntimeException('Unable to get resource');
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);

        $result = stream_get_contents($pipes[1]);
        $error  = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($process);

        if ($code > 0 || !empty($error)) {
            throw new Exception\RuntimeException(sprintf('Unable to run command. %s', $error));
        }

        return $result;
    }

    protected function getCommand(array $args)
    {
        foreach ($args as &$arg) {
            $arg = escapeshellarg($arg);
        }

        return escapeshellcmd("{$this->command} " . implode(' ', $args));
    }
}
