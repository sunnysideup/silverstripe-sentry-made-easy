<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy;

use InvalidArgumentException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\EventHint;
use Sentry\Severity;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;
use Sunnysideup\SentryMadeEasy\Attachers\Attacher;
use Sunnysideup\SentryMadeEasy\Attachers\CurrentUserAttacher;
use Sunnysideup\SentryMadeEasy\Attachers\ExceptionAttacher;
use Sunnysideup\SentryMadeEasy\Attachers\RequestAttacher;
use Sunnysideup\SentryMadeEasy\Attachers\ServerInfoAttacher;
use Sunnysideup\SentryMadeEasy\Attachers\TagAttacher;
use Throwable;

/**
 * Reports Monolog records as Sentry issues through an isolated Sentry hub.
 */
class SentryHandler extends AbstractProcessingHandler
{
    private ?HubInterface $hub = null;

    /** @var list<Attacher> */
    private array $attachers;

    /**
     * @return list<Attacher>
     */
    protected static function getDefaultAttachers(): array
    {
        return [
            new RequestAttacher(),
            new CurrentUserAttacher(),
            new ServerInfoAttacher(),
            // do not change the order of these attachers
            new TagAttacher(),
            new ExceptionAttacher(),
        ];
    }

    /**
     * @param array<string, mixed> $options Sentry client options, overriding environment defaults.
     */
    public function __construct(
        int|string|Level $level = Level::Error,
        bool $bubble = true,
        array $options = []
    ) {
        parent::__construct($level, $bubble);
        $this->attachers = static::getDefaultAttachers();

        $sentryDsn = SentryConfig::getSentryDSN();
        if ($sentryDsn === null) {
            return;
        }

        $options += [
            'dsn' => $sentryDsn,
            'environment' => SentryConfig::getSentryEnvironment(),
            'release' => SentryConfig::getSentryRelease(),
            // SilverStripe already registers PHP error and exception handlers.
            'default_integrations' => false,
        ];

        try {
            $client = ClientBuilder::create($options)->getClient();
            // extra checks for dsn
            if ($client->getOptions()->getDsn() === null) {
                return;
            }

            $this->hub = new Hub($client);
        } catch (InvalidArgumentException) {
            // swallow exceptions as we don't want to interrupt the site because of invalid configuration
        }
    }

    protected function write(LogRecord $record): void
    {
        if ($this->hub === null) {
            return;
        }

        $event = Event::createEvent();
        $event->setMessage($record->message);
        $event->setLogger('monolog.' . $record->channel);
        $event->setTimestamp((float) $record->datetime->format('U.u'));
        $event->setLevel(static::mapLogLevelToSeverity($record->level));

        $hint = new EventHint();
        $context = $record->context;

        foreach ($this->attachers as $attacher) {
            try {
                $attacher->attach($event, $hint);
                $attacher->attachWithContext($event, $hint, $context);
            } catch (Throwable) {
                // swallow exception as we can't do anything here
            }
        }

        $this->hub->withScope(function (Scope $scope) use ($record, $context, $event, $hint): void {
            $scope->setExtra('monolog.channel', $record->channel);
            $scope->setExtra('monolog.level', $record->level->getName());

            if ($context !== []) {
                $scope->setExtra('monolog.context', $context);
            }

            if ($record->extra !== []) {
                $scope->setExtra('monolog.extra', $record->extra);
            }

            $this->hub->captureEvent($event, $hint);
        });
    }

    protected static function mapLogLevelToSeverity(Level $level): Severity
    {
        return match ($level) {
            Level::Debug => Severity::debug(),
            Level::Info, Level::Notice => Severity::info(),
            Level::Warning => Severity::warning(),
            Level::Error => Severity::error(),
            Level::Critical, Level::Alert, Level::Emergency => Severity::fatal(),
        };
    }
}
