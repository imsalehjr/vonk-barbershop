<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$done = $_SESSION['booking_done'] ?? null;
$appointment = $done ? BookingService::find((int) $done['id']) : null;
if (!$appointment) {
    redirect('book.php');
}

view('layout/header', ['title' => 'Afspraak bevestigd']);
?>
<section class="section">
    <div class="container narrow">
        <div class="card confirm-card">
            <div class="confirm-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M5 12.5 10 17l9-10" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h1>Je afspraak staat, <?= e($appointment['customer_name']) ?>!</h1>
            <dl class="summary">
                <div><dt>Behandeling</dt><dd><?= e($appointment['service_name']) ?></dd></div>
                <div><dt>Barbier</dt><dd><?= e($appointment['barber_name']) ?></dd></div>
                <div><dt>Datum</dt><dd><?= e(format_date($appointment['start_at'])) ?></dd></div>
                <div><dt>Tijd</dt><dd><?= e(format_time($appointment['start_at'])) ?> – <?= e(format_time($appointment['end_at'])) ?></dd></div>
                <div><dt>Prijs</dt><dd><?= e(money((int) $appointment['price_cents'])) ?> <span class="muted">(betalen in de zaak)</span></dd></div>
            </dl>
            <?php if (is_demo()): ?>
                <p>Bij een echte zaak krijg je nu een bevestiging per e-mail. In deze demo wordt geen e-mail verstuurd. Gebruik de knop hieronder om je afspraak te wijzigen of te annuleren.</p>
            <?php else: ?>
                <p>We hebben een bevestiging gestuurd naar <strong><?= e($appointment['customer_email']) ?></strong>, met een link om je afspraak te wijzigen of te annuleren.</p>
            <?php endif; ?>
            <div class="hero-actions">
                <a class="btn btn-outline" href="<?= e(url('manage.php?token=' . $done['token'])) ?>">Afspraak beheren</a>
                <a class="btn btn-ghost" href="<?= e(url()) ?>">Terug naar home</a>
            </div>
        </div>
    </div>
</section>
<?php view('layout/footer'); ?>
