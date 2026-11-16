<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Providers;

use Throwable;

interface ContextProvider
{
    public function getName(): string;

    /**
     * @return array<string, mixed> Empty when this provider does not apply to the exception.
     */
    public function getContext(Throwable $exception): array;
}
