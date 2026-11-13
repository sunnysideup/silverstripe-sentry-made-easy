<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Logging;

use Psr\Log\LoggerInterface;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Console catches command exceptions before PHP's exception handler can log them.
 */
class ConsoleErrorListener
{
    private const COMMAND_PREFIXES = ['dev/', 'tasks'];

    public function __construct(private LoggerInterface $logger)
    {
    }

    public function __invoke(ConsoleErrorEvent $event): void
    {
        $error = $event->getError();
        $command = $this->getSanitisedCommand($event->getInput());
        $context = ['exception' => $error];

        if ($command !== null) {
            $context['tags']['command'] = $command;
        }

        $this->logger->error($error->getMessage(), $context);
    }

    protected function getSanitisedCommand(InputInterface $input): ?string
    {
        if (!SentryConfig::shouldExcludeSensitiveData()) {
            return (string) $input;
        }

        $command = $input->getFirstArgument();

        if ($command !== null) {
            foreach (self::COMMAND_PREFIXES as $prefix) {
                if (str_starts_with($command, $prefix)) {
                    return $command;
                }
            }
        }

        return null;
    }
}
