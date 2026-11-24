<?php

declare(strict_types=1);

final class Availability
{
    public static function activeService(int $serviceId): ?array
    {
        $stmt = Database::pdo()->prepare('SELECT * FROM services WHERE id = ? AND is_active = 1');
        $stmt->execute([$serviceId]);
        return $stmt->fetch() ?: null;
    }

    public static function barbersFor(int $serviceId, int $barberId = 0): array
    {
        $sql = 'SELECT b.* FROM barbers b JOIN barber_services bs ON bs.barber_id = b.id
                WHERE bs.service_id = ? AND b.is_active = 1';
        $params = [$serviceId];
        if ($barberId > 0) {
            $sql .= ' AND b.id = ?';
            $params[] = $barberId;
        }
        $stmt = Database::pdo()->prepare($sql . ' ORDER BY b.sort_order, b.name');
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function slots(int $serviceId, int $barberId, string $date, int $ignoreAppointmentId = 0): array
    {
        $service = self::activeService($serviceId);
        $day = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if (!$service || !$day) {
            return [];
        }

        [$first, $last] = Schedule::bookingWindow();
        if ($day < $first || $day > $last) {
            return [];
        }

        $duration = (int) $service['duration_min'];
        $earliest = Schedule::earliestStart();
        $result = [];

        foreach (self::barbersFor($serviceId, $barberId) as $barber) {
            $hours = Schedule::hoursFor($day, (int) $barber['id']);
            if ($hours === null) {
                continue;
            }
            [$open, $close] = $hours;
            $taken = self::takenSlots((int) $barber['id'], $day, $ignoreAppointmentId);

            for ($start = $open; $start->modify("+$duration minutes") <= $close; $start = $start->modify('+' . Schedule::SLOT_MINUTES . ' minutes')) {
                if ($start < $earliest) {
                    continue;
                }
                foreach (Schedule::slotStarts($start, $duration) as $slot) {
                    if (isset($taken[$slot])) {
                        continue 2;
                    }
                }
                $result[$start->format('H:i')][] = (int) $barber['id'];
            }
        }

        ksort($result);
        return $result;
    }

    private static function takenSlots(int $barberId, DateTimeImmutable $day, int $ignoreAppointmentId): array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT slot_start FROM appointment_slots WHERE barber_id = ? AND slot_start >= ? AND slot_start < ? AND appointment_id <> ?'
        );
        $stmt->execute([$barberId, $day->format('Y-m-d 00:00:00'), $day->modify('+1 day')->format('Y-m-d 00:00:00'), $ignoreAppointmentId]);
        return array_flip($stmt->fetchAll(PDO::FETCH_COLUMN));
    }
}
