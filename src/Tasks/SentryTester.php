<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Tasks;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\SentryMadeEasy\Api\ErrorMethods;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;
use Sunnysideup\SentryMadeEasy\SentryHandler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;

class SentryTester extends BuildTask
{
    protected static string $commandName = 'SentryTester';

    protected string $title = 'Sentry Tester';

    protected static string $description = 'Trigger an intentional error to test Sentry reporting.';

    public function getOptions(): array
    {
        return [
            new InputOption('method', null, InputOption::VALUE_REQUIRED, 'The error method to run.'),
        ];
    }

    protected function execute(InputInterface $input, PolyOutput $output): int
    {
        $output->writeForHtml('<title>' . Convert::raw2xml($this->getTitle()) . '</title>');

        $this->writeConfigInformation($output);
        $this->writeDiagnosticInformation($output);

        $method = $input->getOption('method');
        if ($method !== null) {
            return $this->executeMethod($method, $output);
        }

        $this->writeTestLists($output);
        return Command::SUCCESS;
    }

    private function executeMethod(string $method, PolyOutput $output): int
    {
        if (!in_array($method, ErrorMethods::getMethods(), true)) {
            $output->writeln('<error>Unknown error method. Run the task without a method to see available tests.</error>');
            return Command::INVALID;
        }

        $output->writeln('Running error method: ' . $method);
        ErrorMethods::{$method}();

        return Command::SUCCESS;
    }

    private function writeConfigInformation(PolyOutput $output): void
    {
        $output->writeForHtml('<details open><summary style="cursor:pointer">Sentry Configuration</summary>');
        $output->writeln([
            'Sentry DSN: ' . (SentryConfig::getSentryDSN() !== null ? 'configured' : '(missing or invalid)'),
            'Sentry handler: ' . (SentryConfig::isRegistered() ? 'registered' : 'UNREGISTERED'),
            'Environment: ' . SentryConfig::getSentryEnvironment(),
            'Release tag: ' . (SentryConfig::getSentryRelease() ?? '(not set)'),
            'Sensitive data: ' . (SentryConfig::shouldExcludeSensitiveData() ? 'excluded' : 'INCLUDED'),
            'Tip: Run flush=all in both CLI and browser to see the latest information.',
        ]);
        $output->writeForHtml('</details>');
        $output->writeForHtml('<hr>');
        $output->writeForAnsi('', true);
    }

    private function writeDiagnosticInformation(PolyOutput $output): void
    {
        $output->writeForHtml('<details><summary style="cursor:pointer">Diagnostic information</summary>');

        $loggerConfig = Config::inst()->get(Injector::class, LoggerInterface::class);
        $handlerConfig = Config::inst()->get(Injector::class, SentryHandler::class);
        $logger = Injector::inst()->get(LoggerInterface::class);

        $moduleDiscovered = array_key_exists(
            'sunnysideup/sentry-made-easy',
            ModuleLoader::inst()->getManifest()->getModules()
        );

        $output->writeln([
            'Sentry module discovered: ' . ($moduleDiscovered ? 'yes' : 'no'),
            'DSN variable present: ' . (
                Environment::getEnv('SS_SENTRY_DSN') !== false ? 'yes' : 'no'
            ),
            'SentryHandler configuration loaded: ' . (
                $handlerConfig !== null ? 'yes' : 'no'
            ),
            'SentryHandler logger call configured: ' . (
                isset($loggerConfig['calls']['pushSentryHandler']) ? 'yes' : 'no'
            ),
            'Logger is Monolog: ' . ($logger instanceof Logger ? 'yes' : 'no'),
        ]);
        if ($logger instanceof Logger) {
            if (count($logger->getHandlers()) > 0) {
                $output->writeln('Attached handlers:');
                foreach ($logger->getHandlers() as $handler) {
                    $output->writeln('- ' . get_class($handler));
                }
            } else {
                $output->writeln('Attached handlers: (none)');
            }
        }
        $output->writeForHtml('</details>');
        $output->writeForHtml('<hr>');
        $output->writeForAnsi('', true);
    }

    private function writeTestLists(PolyOutput $output): void
    {
        $output->writeln('Choose a test below. Each test intentionally triggers an error for Sentry reporting.');
        $output->writeForHtml('<ul>');
        $taskUrl = Controller::join_links(Director::baseURL(), 'dev/tasks', static::getNameWithoutNamespace());

        foreach (ErrorMethods::getMethods() as $method) {
            $url = $taskUrl . '?method=' . rawurlencode($method);
            $output->writeForHtml(sprintf(
                '<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
                Convert::raw2att($url),
                Convert::raw2xml($method)
            ));
            $output->writeForAnsi(sprintf('  %s: sake %s --method=%s', $method, static::getName(), $method), true);
        }

        $output->writeForHtml('</ul><hr>');
        $output->writeForHtml(sprintf(
            '<h2>POST exception test</h2>'
            . '<form action="%s" method="post" target="_blank" rel="noopener noreferrer">'
            . '<input type="hidden" name="test_hidden" value="yes!">'
            . '<input type="hidden" name="method" value="exception">'
            . '<label style="display:block;margin-bottom:.5em">Test body<br><textarea name="test_body" rows="5" cols="30">Sentry POST request body test</textarea></label>'
            . '<button type="submit">Trigger exception via POST</button>'
            . '</form>',
            Convert::raw2att($taskUrl)
        ));
    }
}
