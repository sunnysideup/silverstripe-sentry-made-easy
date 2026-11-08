<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\UserDataBag;
use SilverStripe\Security\Security;

class CurrentUserAttacher implements Attacher
{
    public function attach(Event $event): void
    {
        $member = Security::getCurrentUser();

        if ($member !== null && $member->Email) {
            $event->setUser(new UserDataBag(email: (string) $member->Email));
        }
    }
}
