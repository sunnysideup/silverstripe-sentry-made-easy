<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Psr\Http\Message\ServerRequestInterface;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Integration\RequestFetcher;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;

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

    public function attach(Event $event, EventHint $eventHint): void
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
            // little clean-up to avoid urls like https://example.com:80
            if ($uri->getScheme() === 'https' && $uri->getPort() === 80) {
                $uri = $uri->withPort(null);
            }

            $requestData = $event->getRequest() + [
                'url' => (string) $uri->withQuery('')->withFragment(''),
                'method' => $request->getMethod(),
                'query_string' => $uri->getQuery(),
                'headers' => $headers,
            ];

            $event->setRequest($this->attachRequestData($request, $requestData));
        }
    }

    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void
    {
        // nothing to do
    }

    protected function attachRequestData(ServerRequestInterface $request, array $requestData): array
    {
        if (!SentryConfig::canIncludeSensitiveData()) {
            unset($requestData['data']);
            return $requestData;
        }

        if (!array_key_exists('data', $requestData)) {
            $body = $request->getParsedBody() ?? (string) $request->getBody();
            if ($body !== '' && $body !== []) {
                $requestData['data'] = $body;
            }
        }

        return $requestData;
    }
}
