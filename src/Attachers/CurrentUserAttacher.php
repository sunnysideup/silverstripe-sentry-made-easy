<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\EventHint;
use Sentry\UserDataBag;
use SilverStripe\Security\Security;

class CurrentUserAttacher implements Attacher
{
    public function attach(Event $event, EventHint $eventHint): void
    {
        $member = Security::getCurrentUser();

        if ($member !== null && $member->ID) {
            $event->setUser(new UserDataBag(
                id: $member->ID,
                email: (string) $member->Email
            ));
        }
    }

    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void
    {
        // nothing to do
    }
}
