<?php

declare(strict_types=1);

namespace NiceBase;

use DateTimeZone;
use InvalidArgumentException;

/**
 * Useful wrapper for DateTimeImmutable
 *
 * Adds SQL date format and utility functions
 * @see https://www.php.net/manual/en/class.datetimeinterface.php
 */
class DateTimeImmutable extends \DateTimeImmutable
{
    public const SQL = 'Y-m-d H:i:s';

    /**
     * @param string|int $value
     * @return int The timestamp
     */
    public static function timestampFrom(string|int $value): int
    {
        // Dates coming from as a string (e.g. from a database row) are considered UTC
        // Timestamps are always UTC
        if (is_int($value)) {
            return $value;
        }
        $datetime = self::createFromFormat(self::SQL, $value);
        if (false === $datetime) {
            throw new InvalidArgumentException('Invalid date/time string');
        }
        return $datetime->getTimestamp();
    }

    public static function dateFrom(int $timestamp, ?DateTimeZone $timeZone = null): DateTimeImmutable
    {
        return (new DateTimeImmutable("@$timestamp"))
            ->setTimezone($timeZone ?? new DateTimeZone('UTC'));
    }

    /** @noinspection SenselessProxyMethodInspection */
    public function __unserialize(array $data): void
    {
        parent::__unserialize($data);
    }
}
