<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Exception;
use Sentry\Event;
use Sentry\EventHint;
use Sunnysideup\SentryMadeEasy\Api\GitTool;

class GitInfoAttacher implements Attacher
{
    public function attach(Event $event, EventHint $eventHint): void
    {
        try {
            $sha = GitTool::getCommitSha(BASE_PATH, GitTool::SHORT);
        } catch (Exception) {
            // swallow exception
            return;
        }

        $event->setTag('git.commit', $sha);
    }

    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void
    {
        // nothing to do
    }
}
