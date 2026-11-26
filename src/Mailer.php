<?php

declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

final class Mailer
{
    public static function sendBookingMail(string $type, array $appointment, ?string $token = null): bool
    {
        $subjects = [
            'confirmed' => 'Je afspraak is bevestigd',
            'rescheduled' => 'Je afspraak is gewijzigd',
            'cancelled' => 'Je afspraak is geannuleerd',
        ];

        ob_start();
        view('emails/booking', [
            'type' => $type,
            'appointment' => $appointment,
            'manageUrl' => $token ? url('manage.php?token=' . $token) : null,
        ]);
        $html = (string) ob_get_clean();

        return self::send($appointment['customer_email'], $appointment['customer_name'], $subjects[$type] . ' - ' . Env::get('APP_NAME'), $html);
    }

    public static function send(string $toEmail, string $toName, string $subject, string $html): bool
    {
        try {
            $mail = new PHPMailer(true);
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(Env::get('MAIL_FROM', 'bookings@example.test'), Env::get('MAIL_FROM_NAME', 'Barbershop'));
            $mail->addAddress($toEmail, $toName);
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $html;
            $mail->AltBody = trim(html_entity_decode(strip_tags(str_replace(['<br>', '</p>'], "\n", $html)), ENT_QUOTES, 'UTF-8'));

            if (Env::get('MAIL_DRIVER') === 'smtp' && !is_demo()) {
                $mail->isSMTP();
                $mail->Host = Env::get('SMTP_HOST', '');
                $mail->Port = Env::int('SMTP_PORT', 587);
                $mail->SMTPAuth = true;
                $mail->Username = Env::get('SMTP_USER', '');
                $mail->Password = Env::get('SMTP_PASS', '');
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                return $mail->send();
            }

            $mail->preSend();
            $file = BASE_PATH . '/storage/mail/' . date('Ymd-His') . '-' . bin2hex(random_bytes(4)) . '.eml';
            return file_put_contents($file, $mail->getSentMIMEMessage()) !== false;
        } catch (Throwable $e) {
            error_log('Mail failed: ' . $e->getMessage());
            return false;
        }
    }
}
