<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Composer\InstalledVersions;
use Sentry\Event;

/**
 * Attaches server information (PHP version, SilverStripe version and Sentry
 * SDK version) to the event as a "server" context.
 */
class ServerInfoAttacher implements Attacher
{
    /**
     * Context key mapped to the composer package whose version should be reported.
     *
     * @var array<string, string>
     */
    private const PACKAGES = [
        'silverstripe_version' => 'silverstripe/framework',
        'sentry_version' => 'sentry/sentry',
    ];

    public function attach(Event $event): void
    {
        $context = [
            'php_version' => PHP_VERSION,
        ];

        foreach (self::PACKAGES as $contextKey => $package) {
            if (InstalledVersions::isInstalled($package)) {
                $context[$contextKey] = InstalledVersions::getPrettyVersion($package);
            }
        }

        $event->setContext('server', $context);
    }
}
