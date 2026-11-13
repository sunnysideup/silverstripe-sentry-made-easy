<?php

declare(strict_types=1);

namespace Sunnysideup\SentryMadeEasy;

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
use SilverStripe\ORM\Connect\DatabaseException;
use Sunnysideup\SentryMadeEasy\Api\SentryConfig;
use Sunnysideup\SentryMadeEasy\Attachers\Attacher;
use Sunnysideup\SentryMadeEasy\Attachers\CurrentUserAttacher;
use Sunnysideup\SentryMadeEasy\Attachers\RequestAttacher;
use Sunnysideup\SentryMadeEasy\Attachers\ServerInfoAttacher;
use Throwable;

/**
 * Reports Monolog records as Sentry issues through an isolated Sentry hub.
 */
class SentryHandler extends AbstractProcessingHandler
{
    private HubInterface $hub;

    /** @var list<Attacher> */
    private array $attachers;

    /**
     * @return list<Attacher>
     */
    protected static function getDefaultAttachers(): array
    {
        return [new RequestAttacher(), new CurrentUserAttacher(), new ServerInfoAttacher()];
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

        $options += [
            'dsn' => SentryConfig::getSentryDSN(),
            'environment' => SentryConfig::getSentryEnvironment(),
            'release' => SentryConfig::getSentryRelease(),
            // SilverStripe already registers PHP error and exception handlers.
            'default_integrations' => false,
        ];

        $this->hub = new Hub(ClientBuilder::create($options)->getClient());
    }

    protected function write(LogRecord $record): void
    {
        $event = Event::createEvent();
        $event->setMessage($record->message);
        $event->setLogger('monolog.' . $record->channel);
        $event->setTimestamp((float) $record->datetime->format('U.u'));

        $event->setLevel(static::mapLogLevelToSeverity($record->level));

        foreach ($this->attachers as $attacher) {
            try {
                $attacher->attach($event);
            } catch (Throwable) {
                // swallow exception as we can't do anything here
            }
        }

        $context = $record->context;
        $hint = new EventHint();

        $this->attachTags($event, $hint, $context);
        $this->attachException($event, $hint, $context);

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

    /**
     * @param array<string, mixed> $context
     */
    protected function attachTags(Event $event, EventHint $hint, array &$context): void
    {
        if (is_array($context['tags'] ?? null)) {
            foreach ($context['tags'] as $tag => $value) {
                if (is_string($tag) && is_string($value)) {
                    $event->setTag($tag, $value);
                }
            }
            unset($context['tags']);
        }
    }

    /**
     * @param array<string, mixed> $context
     */
    protected function attachException(Event $event, EventHint $hint, array &$context): void
    {
        if (!isset($context['exception'])) {
            return;
        }

        if ($context['exception'] instanceof Throwable) {
            $hint->exception = $context['exception'];

            if ($hint->exception instanceof DatabaseException && !SentryConfig::shouldExcludeSensitiveData()) {
                $event->setContext('database', [
                    'sql' => $hint->exception->getSQL(),
                    'parameters' => $hint->exception->getParameters(),
                ]);
            }

            unset($context['exception']);
        }
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
