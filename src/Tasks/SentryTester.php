<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Tasks;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\PolyExecution\PolyOutput;
use Sunnysideup\SentryMadeEasy\Api\ErrorMethods;
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

        $logger = Injector::inst()->get(LoggerInterface::class);
        $registered = false;

        if ($logger instanceof Logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof SentryHandler) {
                    $registered = true;
                    break;
                }
            }
        }

        $output->writeln(
            $registered
                ? 'SentryHandler is registered.'
                : 'SentryHandler is NOT registered.'
        );
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

        foreach ($methods as $method) {
            $url = Controller::join_links(Director::baseURL(), 'dev/tasks', static::getNameWithoutNamespace())
                . '?method=' . rawurlencode($method);
            $output->writeForHtml(sprintf(
                '<li><a href="%s" target="_blank" rel="noopener noreferrer">%s</a></li>',
                Convert::raw2att($url),
                Convert::raw2xml($method)
            ));
            $output->writeForAnsi(sprintf('  %s: sake %s --method=%s', $method, static::getName(), $method), true);
        }

        $output->writeForHtml('</ul>');

        return Command::SUCCESS;
    }
}
