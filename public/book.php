<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$pdo = Database::pdo();
$services = $pdo->query('SELECT * FROM services WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();
$barbers = $pdo->query('SELECT * FROM barbers WHERE is_active = 1 ORDER BY sort_order, name')->fetchAll();

$barberServices = [];
foreach ($pdo->query('SELECT barber_id, service_id FROM barber_services') as $row) {
    $barberServices[(int) $row['barber_id']][] = (int) $row['service_id'];
}

$errors = [];
$old = [
    'service' => get_int('service'),
    'barber' => get_int('barber'),
    'date' => get_string('date'),
    'time' => '',
    'name' => '',
    'email' => '',
    'phone' => '',
];

if (is_post()) {
    Csrf::verify();

    $old = [
        'service' => post_int('service'),
        'barber' => post_int('barber'),
        'date' => post_string('date'),
        'time' => post_string('time'),
        'name' => post_string('name'),
        'email' => post_string('email'),
        'phone' => post_string('phone'),
    ];

    if (post_string('website') !== '') {
        redirect('book.php');
    }

    $recent = array_filter($_SESSION['booking_times'] ?? [], fn ($t) => $t > time() - 3600);
    $validator = new Validator();

    if (count($recent) >= 5) {
        $validator->add('form', 'Je hebt in korte tijd meerdere afspraken gemaakt. Bel ons als je er nog meer wilt maken.');
    }
    if (!Availability::activeService($old['service'])) {
        $validator->add('service', 'Kies een behandeling.');
    }
    if ($old['barber'] > 0 && !in_array($old['service'], $barberServices[$old['barber']] ?? [], true)) {
        $validator->add('barber', 'Deze barbier biedt de gekozen behandeling niet aan.');
    }
    $validator
        ->date('date', $old['date'])
        ->time('time', $old['time'])
        ->required('name', $old['name'], 'Naam')
        ->maxLength('name', $old['name'], 100, 'Naam')
        ->required('email', $old['email'], 'E-mailadres')
        ->maxLength('email', $old['email'], 190, 'E-mailadres')
        ->email('email', $old['email'])
        ->phone('phone', $old['phone']);

    if (!$validator->fails()) {
        try {
            $result = BookingService::book($old['service'], $old['barber'], $old['date'], $old['time'], [
                'name' => $old['name'],
                'email' => $old['email'],
                'phone' => $old['phone'],
            ]);

            $recent[] = time();
            $_SESSION['booking_times'] = array_values($recent);

            Mailer::sendBookingMail('confirmed', BookingService::find($result['id']), $result['token']);

            session_regenerate_id(true);
            $_SESSION['booking_done'] = $result;
            redirect('booking-confirmed.php');
        } catch (BookingException $e) {
            $validator->add('time', $e->getMessage());
        }
    }

    $errors = $validator->errors();
}

$slots = null;
if ($old['service'] > 0 && $old['date'] !== '' && (new Validator())->date('date', $old['date'])->fails() === false) {
    $slots = Availability::slots($old['service'], $old['barber'], $old['date']);
}

view('layout/header', ['title' => 'Afspraak maken', 'active' => 'book.php']);
?>
<section class="page-head">
    <div class="container">
        <h1>Afspraak maken</h1>
        <p class="lead">Kies een behandeling, een barbier en een tijd. Je krijgt een bevestiging per e-mail.</p>
    </div>
</section>
<section class="section">
    <div class="container narrow">
        <?php if ($errors !== []): ?>
            <div class="alert alert-error" role="alert">
                <?= e($errors['form'] ?? 'Controleer de velden hieronder.') ?>
            </div>
        <?php endif; ?>

        <form class="booking-form" id="booking-form" method="post" action="<?= e(url('book.php')) ?>" novalidate>
            <?= Csrf::field() ?>

            <fieldset class="step">
                <legend><span class="step-number">1</span> Behandeling</legend>
                <div class="choice-grid">
                    <?php foreach ($services as $service): ?>
                        <label class="choice">
                            <input type="radio" name="service" value="<?= (int) $service['id'] ?>" required<?= $old['service'] === (int) $service['id'] ? ' checked' : '' ?>>
                            <span class="choice-body">
                                <span class="choice-title"><?= e($service['name']) ?></span>
                                <span class="choice-meta"><?= (int) $service['duration_min'] ?> min &middot; <?= e(money((int) $service['price_cents'])) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['service'])): ?><p class="field-error"><?= e($errors['service']) ?></p><?php endif; ?>
            </fieldset>

            <fieldset class="step">
                <legend><span class="step-number">2</span> Barbier</legend>
                <div class="choice-grid">
                    <label class="choice">
                        <input type="radio" name="barber" value="0"<?= $old['barber'] === 0 ? ' checked' : '' ?>>
                        <span class="choice-body">
                            <span class="choice-title">Geen voorkeur</span>
                            <span class="choice-meta">Eerste barbier die vrij is</span>
                        </span>
                    </label>
                    <?php foreach ($barbers as $barber): ?>
                        <label class="choice" data-services="<?= e(implode(',', $barberServices[(int) $barber['id']] ?? [])) ?>">
                            <input type="radio" name="barber" value="<?= (int) $barber['id'] ?>"<?= $old['barber'] === (int) $barber['id'] ? ' checked' : '' ?>>
                            <span class="choice-body">
                                <span class="choice-title"><?= e($barber['name']) ?></span>
                                <span class="choice-meta"><?= e(mb_strimwidth($barber['bio'], 0, 40, '…')) ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <?php if (isset($errors['barber'])): ?><p class="field-error"><?= e($errors['barber']) ?></p><?php endif; ?>
            </fieldset>

            <fieldset class="step">
                <legend><span class="step-number">3</span> Datum &amp; tijd</legend>
                <?php view('partials/booking-fields', ['old' => $old, 'errors' => $errors, 'slots' => $slots]); ?>
            </fieldset>

            <fieldset class="step">
                <legend><span class="step-number">4</span> Jouw gegevens</legend>
                <div class="field">
                    <label for="name">Naam</label>
                    <input type="text" id="name" name="name" required maxlength="100" autocomplete="name" value="<?= e($old['name']) ?>"<?= isset($errors['name']) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="email">E-mailadres</label>
                    <input type="email" id="email" name="email" required maxlength="190" autocomplete="email" value="<?= e($old['email']) ?>"<?= isset($errors['email']) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['email'])): ?><p class="field-error"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div class="field">
                    <label for="phone">Telefoonnummer <span class="optional">(optioneel)</span></label>
                    <input type="tel" id="phone" name="phone" maxlength="20" autocomplete="tel" value="<?= e($old['phone']) ?>"<?= isset($errors['phone']) ? ' aria-invalid="true"' : '' ?>>
                    <?php if (isset($errors['phone'])): ?><p class="field-error"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>
                <div class="hp-field" aria-hidden="true">
                    <label for="website">Laat dit veld leeg</label>
                    <input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
                </div>
            </fieldset>

            <button class="btn btn-primary btn-block" type="submit">Afspraak bevestigen</button>
            <p class="muted small">Door te boeken ga je akkoord met onze <a href="<?= e(url('privacy.php')) ?>">privacyverklaring</a>. Gratis annuleren kan tot <?= Env::int('CANCEL_CUTOFF_HOURS', 2) ?> uur van tevoren.</p>
        </form>
    </div>
</section>
<?php view('layout/footer'); ?>
