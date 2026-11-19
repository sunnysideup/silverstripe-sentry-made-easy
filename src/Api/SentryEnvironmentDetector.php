<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Api;

use SilverStripe\Core\Environment;

class SentryEnvironmentDetector
{
    public static function detect(): ?string
    {
        $baseUrl = Environment::getEnv('SS_BASE_URL');
        if ($baseUrl && ($detectedEnvironment = self::detectFromUrl($baseUrl)) !== null) {
            return $detectedEnvironment;
        }
        $releaseBranch = Environment::getEnv('SS_RELEASE_BRANCH');
        if ($releaseBranch && ($detectedEnvironment = self::detectFromValue($releaseBranch)) !== null) {
            return $detectedEnvironment;
        }
        return null;
    }

    private static function detectFromUrl(string $url): ?string
    {
        if (preg_match('/^https?:\/\/([^\.]+)\.[^\.]+\./', $url, $matches)) {
            return self::detectFromValue($matches[1]);
        }
        return null;
    }

    private static function detectFromValue(string $value): ?string
    {
        return match ($value) {
            'dev', 'develop', 'development', 'devel' => 'dev',
            'test', 'testing', 'tst' => 'test',
            'stage', 'staging', 'stg' => 'stage',
            default => null,
        };
    }
}
