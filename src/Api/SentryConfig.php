<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Api;

use InvalidArgumentException;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Sentry\Dsn;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use Sunnysideup\SentryMadeEasy\SentryHandler;

class SentryConfig
{
    private static string|bool|null $dsnUrl = false;

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
        if (self::$dsnUrl === false) {
            self::$dsnUrl = null;

            $dsn = (string) Environment::getEnv('SS_SENTRY_DSN');
            try {
                Dsn::createFromString($dsn); // validate dsn
                self::$dsnUrl = $dsn;
            } catch (InvalidArgumentException) {
                // swallow exception
            }
        }
        return self::$dsnUrl;
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
