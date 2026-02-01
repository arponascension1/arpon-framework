<?php

namespace Arpon\Support;

use DateInvalidTimeZoneException;
use DateTime;
use DateTimeZone;
use Exception;

class Carbon extends DateTime
{
    public function __construct(string|null $datetime = 'now', DateTimeZone|null $timezone = null)
    {
        parent::__construct($datetime ?? 'now', $timezone);
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Exception
     */
    public static function now(DateTimeZone|string|null $timezone = null): static
    {
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        return new static('now', $timezone);
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public static function today(DateTimeZone|string|null $timezone = null): static
    {
        return static::now($timezone)->startOfDay();
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public static function yesterday(DateTimeZone|string|null $timezone = null): static
    {
        return static::today($timezone)->subDays(1);
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public static function tomorrow(DateTimeZone|string|null $timezone = null): static
    {
        return static::today($timezone)->addDays(1);
    }

    /**
     * @throws DateInvalidTimeZoneException
     * @throws Exception
     */
    public static function parse(string|DateTime|null $datetime, DateTimeZone|string|null $timezone = null): static
    {
        if ($datetime === null) {
            return static::now($timezone);
        }

        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }
        if ($datetime instanceof DateTime) {
            return static::createFromFormat('Y-m-d H:i:s', $datetime->format('Y-m-d H:i:s'), $timezone ?: $datetime->getTimezone());
        }

        return new static($datetime, $timezone);
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public static function createFromFormat($format, $datetime, $timezone = null): static|false
    {
        if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        }

        $date = parent::createFromFormat($format, $datetime, $timezone);
        
        if ($date === false) {
            return false;
        }

        return static::parse($date->format('Y-m-d H:i:s'), $date->getTimezone());
    }

    public function addDays(int $days): static
    {
        $this->modify("+{$days} days");
        return $this;
    }

    public function subDays(int $days): static
    {
        $this->modify("-{$days} days");
        return $this;
    }

    public function addHours(int $hours): static
    {
        $this->modify("+{$hours} hours");
        return $this;
    }

    public function subHours(int $hours): static
    {
        $this->modify("-{$hours} hours");
        return $this;
    }

    public function addMinutes(int $minutes): static
    {
        $this->modify("+{$minutes} minutes");
        return $this;
    }

    public function subMinutes(int $minutes): static
    {
        $this->modify("-{$minutes} minutes");
        return $this;
    }

    public function addMonths(int $months): static
    {
        $this->modify("+{$months} months");
        return $this;
    }

    public function subMonths(int $months): static
    {
        $this->modify("-{$months} months");
        return $this;
    }

    public function addYears(int $years): static
    {
        $this->modify("+{$years} years");
        return $this;
    }

    public function subYears(int $years): static
    {
        $this->modify("-{$years} years");
        return $this;
    }

    public function startOfDay(): static
    {
        $this->setTime(0, 0, 0);
        return $this;
    }

    public function endOfDay(): static
    {
        $this->setTime(23, 59, 59);
        return $this;
    }

    public function startOfMonth(): static
    {
        $this->modify('first day of this month')->startOfDay();
        return $this;
    }

    public function endOfMonth(): static
    {
        $this->modify('last day of this month')->endOfDay();
        return $this;
    }

    public function startOfYear(): static
    {
        $this->setDate((int)$this->format('Y'), 1, 1)->startOfDay();
        return $this;
    }

    public function endOfYear(): static
    {
        $this->setDate((int)$this->format('Y'), 12, 31)->endOfDay();
        return $this;
    }

    public function diffInDays(Carbon|DateTime $date): int
    {
        $diff = $this->diff($date);
        return (int)$diff->format('%r%a');
    }

    public function diffInHours(Carbon|DateTime $date): int
    {
        $diff = $this->diff($date);
        return (int)$diff->format('%r') * (($diff->days * 24) + $diff->h);
    }

    public function diffInMinutes(Carbon|DateTime $date): int
    {
        $seconds = $this->getTimestamp() - $date->getTimestamp();
        return (int)($seconds / 60);
    }

    public function diffInSeconds(Carbon|DateTime $date): int
    {
        return $this->getTimestamp() - $date->getTimestamp();
    }

    public function diffForHumans(Carbon|DateTime|null $date = null): string
    {
        $now = $date ?? static::now($this->getTimezone());
        $diff = $this->diffInSeconds($now);
        $isPast = $diff < 0;
        $diff = abs($diff);

        if ($diff < 60) {
            $value = $diff;
            $unit = $value === 1 ? 'second' : 'seconds';
        } elseif ($diff < 3600) {
            $value = floor($diff / 60);
            $unit = $value === 1 ? 'minute' : 'minutes';
        } elseif ($diff < 86400) {
            $value = floor($diff / 3600);
            $unit = $value === 1 ? 'hour' : 'hours';
        } elseif ($diff < 2592000) {
            $value = floor($diff / 86400);
            $unit = $value === 1 ? 'day' : 'days';
        } elseif ($diff < 31536000) {
            $value = floor($diff / 2592000);
            $unit = $value === 1 ? 'month' : 'months';
        } else {
            $value = floor($diff / 31536000);
            $unit = $value === 1 ? 'year' : 'years';
        }

        return $isPast 
            ? "{$value} {$unit} ago" 
            : "in {$value} {$unit}";
    }

    public function toDateString(): string
    {
        return $this->format('Y-m-d');
    }

    public function toDateTimeString(): string
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function toTimeString(): string
    {
        return $this->format('H:i:s');
    }

    public function toFormattedDateString(): string
    {
        return $this->format('M d, Y');
    }

    public function toDayDateTimeString(): string
    {
        return $this->format('D, M d, Y g:i A');
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function isToday(): bool
    {
        return $this->toDateString() === static::now($this->getTimezone())->toDateString();
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function isYesterday(): bool
    {
        return $this->toDateString() === static::yesterday($this->getTimezone())->toDateString();
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function isTomorrow(): bool
    {
        return $this->toDateString() === static::tomorrow($this->getTimezone())->toDateString();
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function isPast(): bool
    {
        return $this->getTimestamp() < static::now($this->getTimezone())->getTimestamp();
    }

    /**
     * @throws DateInvalidTimeZoneException
     */
    public function isFuture(): bool
    {
        return $this->getTimestamp() > static::now($this->getTimezone())->getTimestamp();
    }

    public function isWeekday(): bool
    {
        $dayOfWeek = (int)$this->format('N');
        return $dayOfWeek >= 1 && $dayOfWeek <= 5;
    }

    public function isWeekend(): bool
    {
        return !$this->isWeekday();
    }

    public function __toString(): string
    {
        return $this->toDateTimeString();
    }
}
