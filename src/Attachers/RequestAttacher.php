<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\Integration\RequestFetcher;

class RequestAttacher implements Attacher
{
    public function attach(Event $event): void
    {
        $request = (new RequestFetcher())->fetchRequest();

        if ($request !== null) {
            $uri = $request->getUri();
            $event->setRequest([
                'url' => (string) $uri->withQuery('')->withFragment(''),
                'method' => $request->getMethod(),
                'query_string' => $uri->getQuery(),
            ]);
        }
    }
}
