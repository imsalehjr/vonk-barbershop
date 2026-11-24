<?php

declare(strict_types=1);

final class Schedule
{
    public const SLOT_MINUTES = 15;

    public static function hoursFor(DateTimeImmutable $date, ?int $barberId = null): ?array
    {
        $pdo = Database::pdo();
        $day = $date->format('Y-m-d');

        $stmt = $pdo->prepare('SELECT open_time, close_time, is_closed FROM opening_hours WHERE weekday = ?');
        $stmt->execute([(int) $date->format('N')]);
        $hours = $stmt->fetch();
        if (!$hours) {
            return null;
        }

        $stmt = $pdo->prepare('SELECT barber_id, is_closed, open_time, close_time FROM schedule_exceptions WHERE date = ? AND (barber_id IS NULL OR barber_id = ?) ORDER BY barber_id IS NULL DESC');
        $stmt->execute([$day, $barberId ?? 0]);

        foreach ($stmt->fetchAll() as $exception) {
            if ((int) $exception['is_closed'] === 1) {
                return null;
            }
            $hours = ['open_time' => $exception['open_time'], 'close_time' => $exception['close_time'], 'is_closed' => 0];
        }

        if ((int) $hours['is_closed'] === 1 || $hours['open_time'] === null || $hours['close_time'] === null) {
            return null;
        }

        return [
            new DateTimeImmutable($day . ' ' . $hours['open_time']),
            new DateTimeImmutable($day . ' ' . $hours['close_time']),
        ];
    }

    public static function slotStarts(DateTimeImmutable $start, int $durationMin): array
    {
        $slots = [];
        for ($i = 0; $i < $durationMin; $i += self::SLOT_MINUTES) {
            $slots[] = $start->modify("+$i minutes")->format('Y-m-d H:i:s');
        }
        return $slots;
    }

    public static function bookingWindow(): array
    {
        $today = new DateTimeImmutable('today');
        return [$today, $today->modify('+' . Env::int('BOOKING_DAYS_AHEAD', 56) . ' days')];
    }

    public static function earliestStart(): DateTimeImmutable
    {
        return (new DateTimeImmutable())->modify('+' . Env::int('BOOKING_MIN_LEAD_MINUTES', 60) . ' minutes');
    }
}
