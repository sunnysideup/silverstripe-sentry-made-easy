<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Tasks;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\SentryMadeEasy\Api\ErrorMethods;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;
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

        $output->writeln([
            'Sentry handler: ' . (SentryConfig::isRegistered() ? 'registered' : 'UNREGISTERED'),
            'Environment: ' . SentryConfig::getSentryEnvironment(),
            'Release tag: ' . (SentryConfig::getSentryRelease() ?? '(not set)'),
            'Sensitive data: ' . (SentryConfig::shouldExcludeSensitiveData() ? 'excluded' : 'INCLUDED'),
        ]);
        $output->writeForHtml('<hr>');
        $output->writeForAnsi('', true);

        $methods = ErrorMethods::getMethods();
        $method = $input->getOption('method');

        if ($method !== null) {
            if (!in_array($method, $methods, true)) {
                $output->writeln('<error>Unknown error method. Run the task without a method to see available tests.</error>');

                return Command::INVALID;
            }

            $output->writeln('Running error method: ' . $method);
            ErrorMethods::{$method}();

            return Command::SUCCESS;
        }

        $output->writeln('Choose a test below. Each test intentionally triggers an error for Sentry reporting.');
        $output->writeForHtml('<ul>');
        $taskUrl = Controller::join_links(Director::baseURL(), 'dev/tasks', static::getNameWithoutNamespace());

        foreach ($methods as $method) {
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

        return Command::SUCCESS;
    }
}
