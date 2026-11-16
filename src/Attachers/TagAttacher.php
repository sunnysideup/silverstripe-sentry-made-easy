<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\EventHint;

class TagAttacher implements Attacher
{
    public function attach(Event $event, EventHint $eventHint): void
    {
        // nothing to do
    }

    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void
    {
        if (is_array($context['tags'] ?? null)) {
            foreach ($context['tags'] as $tag => $value) {
                if (is_string($tag) && is_string($value)) {
                    $event->setTag($tag, $value);
                }
            }
            unset($context['tags']);
        }
    }
}
