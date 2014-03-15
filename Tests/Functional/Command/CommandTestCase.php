<?php

namespace FOS\HttpCacheBundle\Tests\Functional\Command;

use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;

abstract class CommandTestCase extends WebTestCase
{
    /**
     * Runs a command and returns it output.
     *
     * @param Client $client
     * @param string $command
     * @param int    $verbosity Verbosity level to use.
     * @param int    $exitCode  Expected command exit code.
     *
     * @return string
     */
    protected function runCommand(Client $client, $command, $verbosity = OutputInterface::VERBOSITY_NORMAL, $exitCode = 0)
    {
        $application = new Application($client->getKernel());
        $application->setAutoExit(false);

        $fp = tmpfile();
        $input = new StringInput($command);
        $output = new StreamOutput($fp);
        $output->setVerbosity($verbosity);

        $realCode = $application->run($input, $output);

        fseek($fp, 0);
        $output = '';
        while (!feof($fp)) {
            $output = fread($fp, 4096);
        }
        fclose($fp);

        $this->assertEquals($exitCode, $realCode, $output);

        return $output;
    }
}
