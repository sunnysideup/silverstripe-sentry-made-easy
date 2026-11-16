<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\EventHint;

interface Attacher
{
    public function attach(Event $event, EventHint $eventHint): void;
    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void;
}
