<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Providers;

use SilverStripe\ORM\Connect\DatabaseException;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;
use Throwable;

class DatabaseContextProvider implements ContextProvider
{
    public function getName(): string
    {
        return 'database';
    }

    public function getContext(Throwable $exception): array
    {
        if (!$exception instanceof DatabaseException) {
            return [];
        }

        if (SentryConfig::shouldExcludeSensitiveData()) {
            return [];
        }

        return [
            'sql' => $exception->getSQL(),
            'parameters' => $exception->getParameters(),
        ];
    }
}
