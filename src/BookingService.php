<?php

declare(strict_types=1);

final class BookingException extends RuntimeException
{
}

final class BookingService
{
    public static function book(int $serviceId, int $barberId, string $date, string $time, array $customer): array
    {
        $service = Availability::activeService($serviceId);
        if (!$service) {
            throw new BookingException('Deze behandeling is niet beschikbaar.');
        }

        $candidates = self::availableBarbersAt($serviceId, $barberId, $date, $time);
        if ($candidates === []) {
            throw new BookingException('Sorry, deze tijd is net door iemand anders geboekt. Kies een andere tijd.');
        }

        $token = bin2hex(random_bytes(32));
        $start = new DateTimeImmutable("$date $time");
        $end = $start->modify('+' . (int) $service['duration_min'] . ' minutes');
        $pdo = Database::pdo();

        foreach ($candidates as $candidateId) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO appointments (barber_id, service_id, customer_name, customer_email, customer_phone, start_at, end_at, service_name, price_cents, manage_token_hash)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $candidateId,
                    $serviceId,
                    $customer['name'],
                    $customer['email'],
                    $customer['phone'],
                    $start->format('Y-m-d H:i:s'),
                    $end->format('Y-m-d H:i:s'),
                    $service['name'],
                    (int) $service['price_cents'],
                    hash('sha256', $token),
                ]);
                $appointmentId = (int) $pdo->lastInsertId();

                self::reserveSlots($appointmentId, $candidateId, $start, (int) $service['duration_min']);

                $pdo->commit();

                return ['id' => $appointmentId, 'token' => $token];
            } catch (PDOException $e) {
                $pdo->rollBack();
                if (!self::isDuplicate($e)) {
                    throw $e;
                }
            }
        }

        throw new BookingException('Sorry, deze tijd is net door iemand anders geboekt. Kies een andere tijd.');
    }

    public static function reschedule(array $appointment, int $barberId, string $date, string $time): void
    {
        $serviceId = (int) $appointment['service_id'];
        $candidates = self::availableBarbersAt($serviceId, $barberId, $date, $time, (int) $appointment['id']);
        if ($candidates === []) {
            throw new BookingException('Deze tijd is niet meer beschikbaar. Kies een andere tijd.');
        }

        $duration = (int) ((strtotime($appointment['end_at']) - strtotime($appointment['start_at'])) / 60);
        $start = new DateTimeImmutable("$date $time");
        $end = $start->modify("+$duration minutes");
        $pdo = Database::pdo();

        foreach ($candidates as $candidateId) {
            try {
                $pdo->beginTransaction();
                $pdo->prepare('DELETE FROM appointment_slots WHERE appointment_id = ?')->execute([$appointment['id']]);
                $pdo->prepare('UPDATE appointments SET barber_id = ?, start_at = ?, end_at = ? WHERE id = ? AND status = ?')
                    ->execute([$candidateId, $start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s'), $appointment['id'], 'booked']);
                self::reserveSlots((int) $appointment['id'], $candidateId, $start, $duration);
                $pdo->commit();
                return;
            } catch (PDOException $e) {
                $pdo->rollBack();
                if (!self::isDuplicate($e)) {
                    throw $e;
                }
            }
        }

        throw new BookingException('Deze tijd is niet meer beschikbaar. Kies een andere tijd.');
    }

    public static function setStatus(int $appointmentId, string $status): void
    {
        if (!in_array($status, ['booked', 'cancelled', 'completed', 'no_show'], true)) {
            throw new InvalidArgumentException('Invalid status.');
        }

        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $pdo->prepare('UPDATE appointments SET status = ? WHERE id = ?')->execute([$status, $appointmentId]);
            if ($status === 'cancelled') {
                $pdo->prepare('DELETE FROM appointment_slots WHERE appointment_id = ?')->execute([$appointmentId]);
            }
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function findByToken(string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            return null;
        }
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, b.name AS barber_name FROM appointments a JOIN barbers b ON b.id = a.barber_id WHERE a.manage_token_hash = ?'
        );
        $stmt->execute([hash('sha256', $token)]);
        return $stmt->fetch() ?: null;
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare(
            'SELECT a.*, b.name AS barber_name FROM appointments a JOIN barbers b ON b.id = a.barber_id WHERE a.id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function canCustomerChange(array $appointment): bool
    {
        $cutoff = (new DateTimeImmutable())->modify('+' . Env::int('CANCEL_CUTOFF_HOURS', 2) . ' hours');
        return $appointment['status'] === 'booked' && new DateTimeImmutable($appointment['start_at']) > $cutoff;
    }

    private static function availableBarbersAt(int $serviceId, int $barberId, string $date, string $time, int $ignoreAppointmentId = 0): array
    {
        $slots = Availability::slots($serviceId, $barberId, $date, $ignoreAppointmentId);
        return $slots[$time] ?? [];
    }

    private static function reserveSlots(int $appointmentId, int $barberId, DateTimeImmutable $start, int $duration): void
    {
        $stmt = Database::pdo()->prepare('INSERT INTO appointment_slots (appointment_id, barber_id, slot_start) VALUES (?, ?, ?)');
        foreach (Schedule::slotStarts($start, $duration) as $slot) {
            $stmt->execute([$appointmentId, $barberId, $slot]);
        }
    }

    private static function isDuplicate(PDOException $e): bool
    {
        return ($e->errorInfo[1] ?? null) === 1062;
    }
}
