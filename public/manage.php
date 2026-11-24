<?php

require dirname(__DIR__) . '/src/bootstrap.php';

header('Referrer-Policy: no-referrer');

$token = is_post() ? post_string('token') : get_string('token');
$appointment = BookingService::findByToken($token);

if (!$appointment) {
    http_response_code(404);
    view('layout/header', ['title' => 'Booking not found']);
    echo '<section class="section"><div class="container narrow"><div class="card"><h1>Booking not found</h1><p>This link is invalid or has expired. Please check the link in your email or call us.</p></div></div></section>';
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
        flash('error', 'This appointment can no longer be changed online. Please call us.');
        redirect('manage.php?token=' . $token);
    }

    if ($action === 'cancel' && post_string('confirm') !== '1') {
        flash('error', 'Please tick the box to confirm the cancellation.');
        redirect('manage.php?token=' . $token);
    }

    if ($action === 'cancel') {
        BookingService::setStatus((int) $appointment['id'], 'cancelled');
        Mailer::sendBookingMail('cancelled', BookingService::find((int) $appointment['id']));
        flash('success', 'Your appointment has been cancelled.');
        redirect('manage.php?token=' . $token);
    }

    if ($action === 'reschedule') {
        $old = ['barber' => post_int('barber'), 'date' => post_string('date'), 'time' => post_string('time')];
        $validator = (new Validator())->date('date', $old['date'])->time('time', $old['time']);

        if (!$validator->fails()) {
            try {
                BookingService::reschedule($appointment, $old['barber'], $old['date'], $old['time']);
                Mailer::sendBookingMail('rescheduled', BookingService::find((int) $appointment['id']), $token);
                flash('success', 'Your appointment has been changed. We sent you a new confirmation.');
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

$statusLabels = ['booked' => 'Confirmed', 'cancelled' => 'Cancelled', 'completed' => 'Completed', 'no_show' => 'Missed'];

view('layout/header', ['title' => 'Manage booking']);
?>
<section class="page-head">
    <div class="container">
        <h1>Your appointment</h1>
    </div>
</section>
<section class="section">
    <div class="container narrow">
        <div class="card">
            <p><span class="badge badge-<?= e($appointment['status']) ?>"><?= e($statusLabels[$appointment['status']]) ?></span></p>
            <dl class="summary">
                <div><dt>Service</dt><dd><?= e($appointment['service_name']) ?></dd></div>
                <div><dt>Barber</dt><dd><?= e($appointment['barber_name']) ?></dd></div>
                <div><dt>Date</dt><dd><?= e(format_date($appointment['start_at'])) ?></dd></div>
                <div><dt>Time</dt><dd><?= e(format_time($appointment['start_at'])) ?> – <?= e(format_time($appointment['end_at'])) ?></dd></div>
                <div><dt>Price</dt><dd><?= e(money((int) $appointment['price_cents'])) ?></dd></div>
            </dl>
            <?php if (!$canChange && $appointment['status'] === 'booked'): ?>
                <p class="muted">Changes are possible up to <?= Env::int('CANCEL_CUTOFF_HOURS', 2) ?> hours before your appointment. Need help? Call <a href="tel:+31401234567">040 123 45 67</a>.</p>
            <?php endif; ?>
        </div>

        <?php if ($canChange): ?>
            <form class="card booking-form" id="booking-form" method="post" action="<?= e(url('manage.php')) ?>" novalidate>
                <h2>Change date or time</h2>
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="action" value="reschedule">
                <input type="hidden" name="service" value="<?= (int) $appointment['service_id'] ?>">
                <input type="hidden" id="slot-token" value="<?= e($token) ?>">
                <div class="field">
                    <label for="barber">Barber</label>
                    <select id="barber" name="barber">
                        <option value="0"<?= $old['barber'] === 0 ? ' selected' : '' ?>>No preference</option>
                        <?php foreach ($barbers as $barber): ?>
                            <option value="<?= (int) $barber['id'] ?>"<?= $old['barber'] === (int) $barber['id'] ? ' selected' : '' ?>><?= e($barber['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php view('partials/booking-fields', ['old' => $old, 'errors' => $errors, 'slots' => $slots]); ?>
                <button class="btn btn-primary" type="submit">Save new time</button>
            </form>

            <form class="card" method="post" action="<?= e(url('manage.php')) ?>">
                <h2>Cancel appointment</h2>
                <p class="muted">Can't make it? Cancel so someone else can take your spot.</p>
                <?= Csrf::field() ?>
                <input type="hidden" name="token" value="<?= e($token) ?>">
                <input type="hidden" name="action" value="cancel">
                <label class="check">
                    <input type="checkbox" name="confirm" value="1" required>
                    <span>Yes, I want to cancel this appointment</span>
                </label>
                <button class="btn btn-danger" type="submit">Cancel appointment</button>
            </form>
        <?php endif; ?>
    </div>
</section>
<?php view('layout/footer'); ?>
