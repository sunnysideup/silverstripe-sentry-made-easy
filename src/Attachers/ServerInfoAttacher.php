<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Composer\InstalledVersions;
use Sentry\Event;

/**
 * Attaches server information (PHP version, SilverStripe version and Sentry
 * SDK version) to the event as tags.
 */
class ServerInfoAttacher implements Attacher
{
    /**
     * Tag key mapped to the composer package whose version should be reported.
     *
     * @var array<string, string>
     */
    private const PACKAGES = [
        'silverstripe' => 'silverstripe/framework',
        'sentry' => 'sentry/sentry',
    ];

    public function attach(Event $event): void
    {
        $event->setTag('php.version', PHP_VERSION);
        $event->setTag('php.sapi', PHP_SAPI);

        foreach (self::PACKAGES as $tagKey => $package) {
            if (InstalledVersions::isInstalled($package)) {
                $version = InstalledVersions::getPrettyVersion($package);

                if ($version !== null) {
                    $event->setTag("{$tagKey}.version", $version);
                }
            }
        }
    }
}
