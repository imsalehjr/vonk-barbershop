<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$done = $_SESSION['booking_done'] ?? null;
$appointment = $done ? BookingService::find((int) $done['id']) : null;
if (!$appointment) {
    redirect('book.php');
}

view('layout/header', ['title' => 'Booking confirmed']);
?>
<section class="section">
    <div class="container narrow">
        <div class="card confirm-card">
            <div class="confirm-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 12.5 10 17l9-10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h1>You're booked, <?= e($appointment['customer_name']) ?>!</h1>
            <dl class="summary">
                <div><dt>Service</dt><dd><?= e($appointment['service_name']) ?></dd></div>
                <div><dt>Barber</dt><dd><?= e($appointment['barber_name']) ?></dd></div>
                <div><dt>Date</dt><dd><?= e(format_date($appointment['start_at'])) ?></dd></div>
                <div><dt>Time</dt><dd><?= e(format_time($appointment['start_at'])) ?> – <?= e(format_time($appointment['end_at'])) ?></dd></div>
                <div><dt>Price</dt><dd><?= e(money((int) $appointment['price_cents'])) ?> <span class="muted">(pay in the shop)</span></dd></div>
            </dl>
            <p>We sent a confirmation to <strong><?= e($appointment['customer_email']) ?></strong> with a link to change or cancel.</p>
            <div class="hero-actions">
                <a class="btn btn-outline" href="<?= e(url('manage.php?token=' . $done['token'])) ?>">Manage booking</a>
                <a class="btn btn-ghost" href="<?= e(url()) ?>">Back to home</a>
            </div>
        </div>
    </div>
</section>
<?php view('layout/footer'); ?>
