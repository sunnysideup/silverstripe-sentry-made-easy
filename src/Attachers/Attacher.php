<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;

interface Attacher
{
    public function attach(Event $event): void;
}
