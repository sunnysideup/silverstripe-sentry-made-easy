<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Sentry\Event;
use Sentry\Integration\RequestFetcher;

class RequestAttacher implements Attacher
{
    private const HEADERS = [
        'Accept',
        'Accept-Encoding',
        'Accept-Language',
        'Cache-Control',
        'Content-Encoding',
        'Content-Length',
        'Content-Type',
        'Pragma',
        'Sec-Fetch-Dest',
        'Sec-Fetch-Mode',
        'Sec-Fetch-Site',
        'User-Agent',
    ];

    public function attach(Event $event): void
    {
        $request = (new RequestFetcher())->fetchRequest();

        if ($request !== null) {
            $headers = [];
            foreach (self::HEADERS as $header) {
                if ($request->hasHeader($header)) {
                    $headers[$header] = $request->getHeaderLine($header);
                }
            }

            $uri = $request->getUri();
            $event->setRequest([
                'url' => (string) $uri->withQuery('')->withFragment(''),
                'method' => $request->getMethod(),
                'query_string' => $uri->getQuery(),
                'headers' => $headers,
            ]);
        }
    }
}
