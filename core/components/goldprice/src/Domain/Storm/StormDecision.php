<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Storm;

/**
 * Outcome of one storm evaluation for one weight group (ТЗ п.6.2).
 *
 * Asymmetric by design: a crash freezes the sale side (we do not sell below
 * what we paid), a spike freezes the buy side. The unfrozen side keeps
 * following the market.
 */
final class StormDecision
{
    public const MODE_NORMAL = 'normal';
    public const MODE_CRASH = 'crash';
    public const MODE_SPIKE = 'spike';

    public const EVENT_NONE = '';
    public const EVENT_ON = 'storm_on';
    public const EVENT_OFF = 'storm_off';
    public const EVENT_FLIP = 'storm_flip';

    /** @var string */
    private $mode;

    /** @var string */
    private $reason;

    /** @var float|null null when history cannot measure the window */
    private $changePct;

    /** @var int|null */
    private $startedAt;

    /** @var int|null */
    private $expiresAt;

    /** @var string */
    private $event;

    private function __construct(
        string $mode,
        string $reason,
        ?float $changePct,
        ?int $startedAt,
        ?int $expiresAt,
        string $event
    ) {
        $this->mode = $mode;
        $this->reason = $reason;
        $this->changePct = $changePct;
        $this->startedAt = $startedAt;
        $this->expiresAt = $expiresAt;
        $this->event = $event;
    }

    public static function calm(string $reason, ?float $changePct, string $event = self::EVENT_NONE): self
    {
        return new self(self::MODE_NORMAL, $reason, $changePct, null, null, $event);
    }

    public static function storm(
        string $mode,
        string $reason,
        float $changePct,
        int $startedAt,
        int $expiresAt,
        string $event
    ): self {
        return new self($mode, $reason, $changePct, $startedAt, $expiresAt, $event);
    }

    public function getMode(): string
    {
        return $this->mode;
    }

    public function isActive(): bool
    {
        return $this->mode !== self::MODE_NORMAL;
    }

    public function shouldFreezeSale(): bool
    {
        return $this->mode === self::MODE_CRASH;
    }

    public function shouldFreezeBuy(): bool
    {
        return $this->mode === self::MODE_SPIKE;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getChangePct(): ?float
    {
        return $this->changePct;
    }

    public function getStartedAt(): ?int
    {
        return $this->startedAt;
    }

    public function getExpiresAt(): ?int
    {
        return $this->expiresAt;
    }

    /** Empty when nothing changed: only transitions are worth a log entry. */
    public function getEvent(): string
    {
        return $this->event;
    }
}
