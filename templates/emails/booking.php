<?php
$headings = [
    'confirmed' => 'See you soon, ' . $appointment['customer_name'] . '!',
    'rescheduled' => 'Your appointment has been changed',
    'cancelled' => 'Your appointment has been cancelled',
];
?>
<!DOCTYPE html>
<html lang="en">
<body style="margin:0;padding:24px;background:#f4f1ec;font-family:Arial,Helvetica,sans-serif;color:#1c1c1c;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;margin:0 auto;background:#ffffff;border-radius:8px;">
    <tr><td style="padding:24px 32px;background:#141414;color:#c8a25b;font-size:22px;font-weight:bold;letter-spacing:3px;border-radius:8px 8px 0 0;">VONK</td></tr>
    <tr><td style="padding:32px;">
        <h1 style="margin:0 0 16px;font-size:22px;"><?= e($headings[$type]) ?></h1>
        <p style="margin:0 0 4px;"><strong><?= e($appointment['service_name']) ?></strong> with <?= e($appointment['barber_name']) ?></p>
        <p style="margin:0 0 4px;"><?= e(format_date($appointment['start_at'])) ?> at <?= e(format_time($appointment['start_at'])) ?></p>
        <p style="margin:0 0 24px;"><?= e(money((int) $appointment['price_cents'])) ?> &middot; pay in the shop</p>
        <?php if ($manageUrl && $type !== 'cancelled'): ?>
            <p style="margin:0 0 24px;"><a href="<?= e($manageUrl) ?>" style="display:inline-block;padding:12px 20px;background:#c8a25b;color:#141414;text-decoration:none;border-radius:4px;font-weight:bold;">Change or cancel</a></p>
            <p style="margin:0;font-size:13px;color:#666;">Changes are possible up to <?= Env::int('CANCEL_CUTOFF_HOURS', 2) ?> hours before your appointment. Keep this link private.</p>
        <?php endif; ?>
    </td></tr>
    <tr><td style="padding:16px 32px;font-size:12px;color:#888;">Vonk Barbershop &middot; Voorbeeldstraat 12, 5611 AB Eindhoven</td></tr>
</table>
</body>
</html>
