<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Attachers;

use Composer\InstalledVersions;
use Sentry\Event;
use Sentry\EventHint;
use SilverStripe\Core\Environment;

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

    public function attach(Event $event, EventHint $eventHint): void
    {
        $event->setTag('php.version', PHP_VERSION);
        $event->setTag('php.sapi', PHP_SAPI);

        $this->attachSilverStripeInfo($event);

        foreach (self::PACKAGES as $tagKey => $package) {
            if (InstalledVersions::isInstalled($package)) {
                $version = InstalledVersions::getPrettyVersion($package);

                if ($version !== null) {
                    $event->setTag("{$tagKey}.version", $version);
                }
            }
        }
    }

    public function attachWithContext(Event $event, EventHint $eventHint, array &$context): void
    {
        // nothing to do
    }

    protected function attachSilverStripeInfo(Event $event): void
    {
        $baseUrl = (string) Environment::getEnv('SS_BASE_URL');
        if ($baseUrl !== '') {
            $event->setTag('silverstripe.base_url', $baseUrl);
        }

        $environmentType = (string) Environment::getEnv('SS_ENVIRONMENT_TYPE');
        if ($environmentType !== '') {
            $event->setTag('silverstripe.environment_type', $environmentType);
        }
    }
}
