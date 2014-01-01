<?php

namespace FOS\HttpCacheBundle\Tests\Functional;

/**
 * A PHPUnit test listener that starts and stops the PHP built-in web server
 *
 */
class WebServerListener implements \PHPUnit_Framework_TestListener
{
    protected $suite;
    protected $pid;

    public function __construct($suite)
    {
        $this->suite = $suite;
    }

    /**
     *
     */
    public function startTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->suite != $suite->getName() || null !== $this->pid) {
            return;
        }

        $command = sprintf(
            'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
            WEB_SERVER_HOST,
            WEB_SERVER_PORT,
            WEB_SERVER_DOCROOT
        );

        exec($command, $output);
        $this->pid = $output[0];
    }

    public function endTestSuite(\PHPUnit_Framework_TestSuite $suite)
    {
        if ($this->suite != $suite->getName() || null === $this->pid) {
            return;
        }

        exec('kill ' . $this->pid);
    }

    /**
     *  We don't need these
     */
    public function addError(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}
    public function addFailure(\PHPUnit_Framework_Test $test, \PHPUnit_Framework_AssertionFailedError $e, $time) {}
    public function addIncompleteTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}
    public function addSkippedTest(\PHPUnit_Framework_Test $test, \Exception $e, $time) {}
    public function startTest(\PHPUnit_Framework_Test $test) {}
    public function endTest(\PHPUnit_Framework_Test $test, $time) {}
}