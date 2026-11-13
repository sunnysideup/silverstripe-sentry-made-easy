<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy\Api;

use DateTime;
use SilverStripe\ORM\DB;

/**
 * Methods that intentionally trigger errors, for testing error reporting.
 */
class ErrorMethods
{
    public static function getMethods(): array
    {
        return [
            'exception',
            'databaseError',
            'typeWarning',
            'typeError',
            'deprecatedWarning',
            'deprecatedError',
        ];
    }

    public static function exception(): never
    {
        $datetime = (new \DateTimeImmutable('now', new \DateTimeZone('Pacific/Auckland')))->format('Y-m-d H:i:s');
        throw new \Exception("I am testing at " . $datetime);
    }

    public static function databaseError(): void
    {
        DB::query('SEELECT 1 FROM SiteTree');
    }

    public static function typeWarning(): void
    {
        $value = null;
        $length = strlen($value);
    }

    public static function typeError(): void
    {
        $fn = fn(DateTime $dt) => $dt->format('Y-m-d H:i:s');
        $fn("I ain't DateTime");
    }

    #[\Deprecated(message: 'I warn you deprecated')]
    public static function deprecatedWarning(): void
    {
    }

    public static function deprecatedError(): void
    {
        trigger_error('I am deprecated', E_USER_DEPRECATED);
        return;
    }
}
