<?php

require dirname(__DIR__, 2) . '/src/admin.php';

$id = is_post() ? post_int('id') : get_int('id');
$appointment = BookingService::find($id);
if (!$appointment) {
    flash('error', 'Appointment not found.');
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
            flash('success', 'Status updated.');
        } catch (InvalidArgumentException $e) {
            flash('error', 'Invalid status.');
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
                flash('success', 'Appointment moved.');
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
$statusLabels = ['booked' => 'Booked', 'cancelled' => 'Cancelled', 'completed' => 'Completed', 'no_show' => 'No-show'];

view('admin/header', ['title' => 'Appointment #' . $id, 'active' => 'index.php']);
?>
<div class="admin-title">
    <h1>Appointment #<?= $id ?></h1>
    <a class="link-arrow" href="<?= e(url('admin/index.php')) ?>">Back to list</a>
</div>

<div class="split">
    <div class="card">
        <p><span class="badge badge-<?= e($appointment['status']) ?>"><?= e($statusLabels[$appointment['status']]) ?></span></p>
        <dl class="summary">
            <div><dt>Customer</dt><dd><?= e($appointment['customer_name']) ?></dd></div>
            <div><dt>Email</dt><dd><a href="mailto:<?= e($appointment['customer_email']) ?>"><?= e($appointment['customer_email']) ?></a></dd></div>
            <div><dt>Phone</dt><dd><?= $appointment['customer_phone'] !== '' ? '<a href="tel:' . e($appointment['customer_phone']) . '">' . e($appointment['customer_phone']) . '</a>' : '–' ?></dd></div>
            <div><dt>Service</dt><dd><?= e($appointment['service_name']) ?></dd></div>
            <div><dt>Barber</dt><dd><?= e($appointment['barber_name']) ?></dd></div>
            <div><dt>Date</dt><dd><?= e(format_date($appointment['start_at'])) ?></dd></div>
            <div><dt>Time</dt><dd><?= e(format_time($appointment['start_at'])) ?> – <?= e(format_time($appointment['end_at'])) ?></dd></div>
            <div><dt>Price</dt><dd><?= e(money((int) $appointment['price_cents'])) ?></dd></div>
            <div><dt>Booked on</dt><dd><?= e((new DateTimeImmutable($appointment['created_at']))->format('j M Y H:i')) ?></dd></div>
        </dl>

        <form method="post">
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="status">
            <div class="field">
                <label for="status">Change status</label>
                <select id="status" name="status">
                    <?php foreach ($statusLabels as $key => $label): ?>
                        <option value="<?= e($key) ?>"<?= $appointment['status'] === $key ? ' selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <label class="check"><input type="checkbox" name="notify" value="1" checked> <span>Email the customer when cancelling</span></label>
            <button class="btn btn-outline btn-sm" type="submit">Update status</button>
        </form>
    </div>

    <?php if ($appointment['status'] === 'booked'): ?>
        <form class="card booking-form" id="booking-form" method="post" novalidate>
            <h2>Move appointment</h2>
            <?= Csrf::field() ?>
            <input type="hidden" name="id" value="<?= $id ?>">
            <input type="hidden" name="action" value="reschedule">
            <input type="hidden" name="service" value="<?= (int) $appointment['service_id'] ?>">
            <div class="field">
                <label for="barber">Barber</label>
                <select id="barber" name="barber">
                    <option value="0"<?= $old['barber'] === 0 ? ' selected' : '' ?>>Any available</option>
                    <?php foreach ($barbers as $barber): ?>
                        <option value="<?= (int) $barber['id'] ?>"<?= $old['barber'] === (int) $barber['id'] ? ' selected' : '' ?>><?= e($barber['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php view('partials/booking-fields', ['old' => $old, 'errors' => $errors, 'slots' => $slots, 'ignoreAppointment' => $id]); ?>
            <label class="check"><input type="checkbox" name="notify" value="1" checked> <span>Email the customer</span></label>
            <button class="btn btn-primary" type="submit">Move appointment</button>
        </form>
    <?php endif; ?>
</div>
<?php view('admin/footer'); ?>
