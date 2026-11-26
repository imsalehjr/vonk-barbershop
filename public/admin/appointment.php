<?php

require dirname(__DIR__, 2) . '/src/admin.php';

$id = is_post() ? post_int('id') : get_int('id');
$appointment = BookingService::find($id);
if (!$appointment) {
    flash('error', 'Afspraak niet gevonden.');
    redirect('admin/index.php');
}

$errors = [];
$old = ['barber' => (int) $appointment['barber_id'], 'date' => get_string('date'), 'time' => ''];

if (is_post()) {
    $action = post_string('action');

    if ($action === 'status') {
        $status = post_string('status');
        try {
            BookingService::setStatus($id, $status);
            if ($status === 'cancelled' && post_string('notify') === '1') {
                Mailer::sendBookingMail('cancelled', BookingService::find($id));
            }
            flash('success', 'Status bijgewerkt.');
        } catch (InvalidArgumentException $e) {
            flash('error', 'Ongeldige status.');
        }
        redirect('admin/appointment.php?id=' . $id);
    }

    if ($action === 'reschedule' && $appointment['status'] === 'booked') {
        $old = ['barber' => post_int('barber'), 'date' => post_string('date'), 'time' => post_string('time')];
        $validator = (new Validator())->date('date', $old['date'])->time('time', $old['time']);
        if (!$validator->fails()) {
            try {
                BookingService::reschedule($appointment, $old['barber'], $old['date'], $old['time']);
                if (post_string('notify') === '1') {
                    Mailer::sendBookingMail('rescheduled', BookingService::find($id));
                }
                flash('success', 'Afspraak verplaatst.');
                redirect('admin/appointment.php?id=' . $id);
            } catch (BookingException $e) {
                $validator->add('time', $e->getMessage());
            }
        }
        $errors = $validator->errors();
    }
}

$barbers = Availability::barbersFor((int) $appointment['service_id']);
$slots = null;
if ($old['date'] !== '' && !(new Validator())->date('date', $old['date'])->fails()) {
    $slots = Availability::slots((int) $appointment['service_id'], $old['barber'], $old['date'], $id);
}
$statusLabels = ['booked' => 'Geboekt', 'cancelled' => 'Geannuleerd', 'completed' => 'Afgerond', 'no_show' => 'Niet gekomen'];

view('admin/header', ['title' => 'Afspraak #' . $id, 'active' => 'index.php']);
?>
<div class="admin-title">
    <h1>Afspraak #<?= $id ?></h1>
    <a class="link-arrow" href="<?= e(url('admin/index.php')) ?>">Terug naar overzicht</a>
</div>

<div class="split">
    <div class="card">
        <p><span class="badge badge-<?= e($appointment['status']) ?>"><?= e($statusLabels[$appointment['status']]) ?></span></p>
        <dl class="summary">
            <div><dt>Klant</dt><dd><?= e($appointment['customer_name']) ?></dd></div>
            <div><dt>E-mail</dt><dd><a href="mailto:<?= e($appointment['customer_email']) ?>"><?= e($appointment['customer_email']) ?></a></dd></div>
            <div><dt>Telefoon</dt><dd><?= $appointment['customer_phone'] !== '' ? '<a href="tel:' . e($appointment['customer_phone']) . '">' . e($appointment['customer_phone']) . '</a>' : '–' ?></dd></div>
            <div><dt>Behandeling</dt><dd><?= e($appointment['service_name']) ?></dd></div>
            <div><dt>Barbier</dt><dd><?= e($appointment['barber_name']) ?></dd></div>
            <div><dt>Datum</dt><dd><?= e(format_date($appointment['start_at'])) ?></dd></div>
            <div><dt>Tijd</dt><dd><?= e(format_time($appointment['start_at'])) ?> – <?= e(format_time($appointment['end_at'])) ?></dd></div>
            <div><dt>Prijs</dt><dd><?= e(money((int) $appointment['price_cents'])) ?></dd></div>
            <div><dt>Geboekt op</dt><dd><?= e(format_datetime($appointment['created_at'])) ?></dd></div>
        </dl>

        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="status">
            <div class="field">
                <label for="status">Status wijzigen</label>
                <select id="status" name="status">
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $appointment['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="check"><input type="checkbox" name="notify" value="1" checked> <span>Klant mailen bij annuleren</span></label>
            <button class="btn btn-outline btn-sm" type="submit">Status opslaan</button>
        </form>
    </div>

    <?php if ($appointment['status'] === 'booked'): ?>
        <form class="card booking-form" id="booking-form" method="post" novalidate>
            <h2>Afspraak verplaatsen</h2>
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="service" value="<?= (int) $appointment['service_id'] ?>">
            <div class="field">
                <label for="barber">Barbier</label>
                <select id="barber" name="barber">
                    <option value="0"<?= $old['barber'] === 0 ? ' selected' : '' ?>>Wie er vrij is</option>
                    <?php foreach ($barbers as $barber): ?>
                        <option value="<?= (int) $barber['id'] ?>"<?= $old['barber'] === (int) $barber['id'] ? ' selected' : '' ?>><?= e($barber['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php view('partials/booking-fields', ['old' => $old, 'errors' => $errors, 'slots' => $slots, 'ignoreAppointment' => $id]); ?>
            <label class="check"><input type="checkbox" name="notify" value="1" checked> <span>Klant mailen</span></label>
            <button class="btn btn-primary" type="submit">Afspraak verplaatsen</button>
        </form>
    <?php endif; ?>
</div>
<?php view('admin/footer'); ?>
