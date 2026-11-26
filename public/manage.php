<?php

require dirname(__DIR__) . '/src/bootstrap.php';

header('Referrer-Policy: no-referrer');

$token = is_post() ? post_string('token') : get_string('token');
$appointment = BookingService::findByToken($token);

if (!$appointment) {
    http_response_code(404);
    view('layout/header', ['title' => 'Afspraak niet gevonden']);
    echo '<section class="section"><div class="container narrow"><div class="card"><h1>Afspraak niet gevonden</h1><p>Deze link klopt niet of is verlopen. Controleer de link in je e-mail of bel ons.</p></div></div></section>';
    view('layout/footer');
    exit;
}

$canChange = BookingService::canCustomerChange($appointment);
$errors = [];
$old = ['barber' => (int) $appointment['barber_id'], 'date' => get_string('date'), 'time' => ''];

if (is_post()) {
    Csrf::verify();
    $action = post_string('action');

    if (!$canChange) {
        flash('error', 'Deze afspraak kun je niet meer online wijzigen. Bel ons even.');
        redirect('manage.php?token=' . $token);
    }

    if ($action === 'cancel' && post_string('confirm') !== '1') {
        flash('error', 'Vink het vakje aan om de annulering te bevestigen.');
        redirect('manage.php?token=' . $token);
    }

    if ($action === 'cancel') {
        BookingService::setStatus((int) $appointment['id'], 'cancelled');
        Mailer::sendBookingMail('cancelled', BookingService::find((int) $appointment['id']));
        flash('success', 'Je afspraak is geannuleerd.');
        redirect('manage.php?token=' . $token);
    }

    if ($action === 'reschedule') {
        $old = ['barber' => post_int('barber'), 'date' => post_string('date'), 'time' => post_string('time')];
        $validator = (new Validator())->date('date', $old['date'])->time('time', $old['time']);

        if (!$validator->fails()) {
            try {
                BookingService::reschedule($appointment, $old['barber'], $old['date'], $old['time']);
                Mailer::sendBookingMail('rescheduled', BookingService::find((int) $appointment['id']), $token);
                flash('success', 'Je afspraak is gewijzigd. We hebben je een nieuwe bevestiging gestuurd.');
                redirect('manage.php?token=' . $token);
            } catch (BookingException $e) {
                $validator->add('time', $e->getMessage());
            }
        }
        $errors = $validator->errors();
    }
}

$barbers = Availability::barbersFor((int) $appointment['service_id']);
$slots = null;
if ($canChange && $old['date'] !== '' && !(new Validator())->date('date', $old['date'])->fails()) {
    $slots = Availability::slots((int) $appointment['service_id'], $old['barber'], $old['date'], (int) $appointment['id']);
}

$statusLabels = ['booked' => 'Bevestigd', 'cancelled' => 'Geannuleerd', 'completed' => 'Afgerond', 'no_show' => 'Niet gekomen'];

view('layout/header', ['title' => 'Afspraak beheren']);
?>
<section class="page-head">
    <div class="container">
        <h1>Jouw afspraak</h1>
    </div>
</section>
<section class="section">
    <div class="container narrow">
        <div class="card">
            <p><span class="badge badge-<?= e($appointment['status']) ?>"><?= e($statusLabels[$appointment['status']]) ?></span></p>
            <dl class="summary">
                <div><dt>Behandeling</dt><dd><?= e($appointment['service_name']) ?></dd></div>
                <div><dt>Barbier</dt><dd><?= e($appointment['barber_name']) ?></dd></div>
                <div><dt>Datum</dt><dd><?= e(format_date($appointment['start_at'])) ?></dd></div>
                <div><dt>Tijd</dt><dd><?= e(format_time($appointment['start_at'])) ?> – <?= e(format_time($appointment['end_at'])) ?></dd></div>
                <div><dt>Prijs</dt><dd><?= e(money((int) $appointment['price_cents'])) ?></dd></div>
            </dl>
            <?php if (!$canChange && $appointment['status'] === 'booked'): ?>
                <p class="muted">Wijzigen kan tot <?= Env::int('CANCEL_CUTOFF_HOURS', 2) ?> uur voor je afspraak. Hulp nodig? Bel <a href="tel:+31401234567">040 123 45 67</a>.</p>
            <?php endif; ?>
        </div>

        <?php if ($canChange): ?>
            <form class="card booking-form" id="booking-form" method="post" action="<?= e(url('manage.php')) ?>" novalidate>
                <h2>Datum of tijd wijzigen</h2>
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="service" value="<?= (int) $appointment['service_id'] ?>">
                <input type="hidden" id="slot-token" value="<?= e($token) ?>">
                <div class="field">
                    <label for="barber">Barbier</label>
                    <select id="barber" name="barber">
                        <option value="0"<?= $old['barber'] === 0 ? ' selected' : '' ?>>Geen voorkeur</option>
                        <?php foreach ($barbers as $barber): ?>
                            <option value="<?= (int) $barber['id'] ?>"<?= $old['barber'] === (int) $barber['id'] ? ' selected' : '' ?>><?= e($barber['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php view('partials/booking-fields', ['old' => $old, 'errors' => $errors, 'slots' => $slots]); ?>
                <button class="btn btn-primary" type="submit">Nieuwe tijd opslaan</button>
            </form>

            <form class="card" method="post" action="<?= e(url('manage.php')) ?>">
                <h2>Afspraak annuleren</h2>
                <p class="muted">Kun je niet? Annuleer dan, zodat iemand anders je plek kan krijgen.</p>
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="action" value="cancel">
                <label class="check">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span>Ja, ik wil deze afspraak annuleren</span>
                </label>
                <button class="btn btn-danger" type="submit">Afspraak annuleren</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php view('layout/footer'); ?>
