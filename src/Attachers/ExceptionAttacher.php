<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\EventHint;
use Sunnysideup\SentryMadeEasy\Providers\ContextProvider;
use Sunnysideup\SentryMadeEasy\Providers\DatabaseContextProvider;
use Throwable;

class ExceptionAttacher implements Attacher
{
    /** @var list<ContextProvider> */
    private array $contextProviders;

    /**
     * @param list<ContextProvider>|null $contextProviders Null uses the default providers.
     */
    public function __construct(?array $contextProviders = null)
    {
        $this->contextProviders = $contextProviders ?? [new DatabaseContextProvider()];
    }

    public function attach(Event $event, EventHint $eventHint): void
    {
        // nothing to do
    }

    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void
    {
        $exception = $context['exception'] ?? null;

        if ($exception instanceof Throwable) {
            $eventHint->exception = $exception;

            foreach ($this->contextProviders as $provider) {
                $exceptionContext = $provider->getContext($exception);

                if ($exceptionContext !== []) {
                    $event->setContext($provider->getName(), $exceptionContext);
                }
            }

            unset($context['exception']);
        }
    }
}
