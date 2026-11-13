<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Api;

use Monolog\Logger;
use Psr\Log\LoggerInterface;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\SentryMadeEasy\SentryHandler;

class SentryConfig
{
    public static function isRegistered(): bool
    {
        $logger = Injector::inst()->get(LoggerInterface::class);

        if ($logger instanceof Logger) {
            foreach ($logger->getHandlers() as $handler) {
                if ($handler instanceof SentryHandler) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function getSentryDSN(): ?string
    {
        return Environment::getEnv('SS_SENTRY_DSN') ?: null;
    }

    public static function getSentryEnvironment(): string
    {
        return Environment::getEnv('SS_SENTRY_ENVIRONMENT')
            ?: (Environment::getEnv('SS_ENVIRONMENT_TYPE') ?: 'dev');
    }

    public static function getSentryRelease(): ?string
    {
        return Environment::getEnv('SS_SENTRY_RELEASE') ?: null;
    }

    public static function shouldExcludeSensitiveData(): bool
    {
        return filter_var(Environment::getEnv('SS_SENTRY_EXCLUDE_SENSITIVE_DATA'), FILTER_VALIDATE_BOOLEAN);
    }
}
