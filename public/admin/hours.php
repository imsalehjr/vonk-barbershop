<?php

require dirname(__DIR__, 2) . '/src/admin.php';

$pdo = Database::pdo();
$barbers = $pdo->query('SELECT id, name FROM barbers ORDER BY sort_order, name')->fetchAll();
$validTime = fn (string $t): bool => (bool) preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $t);

if (is_post()) {
    $action = post_string('action');

    if ($action === 'weekly') {
        $rows = [];
        $ok = true;
        for ($day = 1; $day <= 7; $day++) {
            $closed = ($_POST['closed'][$day] ?? '') === '1';
            $open = is_string($_POST['open'][$day] ?? null) ? $_POST['open'][$day] : '';
            $close = is_string($_POST['close'][$day] ?? null) ? $_POST['close'][$day] : '';
            if (!$closed && (!$validTime($open) || !$validTime($close) || $open >= $close)) {
                $ok = false;
                flash('error', weekday_name($day) . ': enter a valid opening and closing time, or mark the day as closed.');
                break;
            }
            $rows[] = [$closed ? null : $open, $closed ? null : $close, $closed ? 1 : 0, $day];
        }
        if ($ok) {
            $stmt = $pdo->prepare('UPDATE opening_hours SET open_time = ?, close_time = ?, is_closed = ? WHERE weekday = ?');
            foreach ($rows as $row) {
                $stmt->execute($row);
            }
            flash('success', 'Opening hours saved. Existing appointments are not changed.');
        }
        redirect('admin/hours.php');
    }

    if ($action === 'add_exception') {
        $date = post_string('date');
        $barberId = post_int('barber');
        $closed = post_string('type') !== 'custom';
        $open = post_string('open');
        $close = post_string('close');
        $note = mb_substr(post_string('note'), 0, 150);

        $v = (new Validator())->date('date', $date);
        if (!$closed && (!$validTime($open) || !$validTime($close) || $open >= $close)) {
            $v->add('time', 'Enter valid custom hours.');
        }
        if ($barberId > 0 && !in_array($barberId, array_map('intval', array_column($barbers, 'id')), true)) {
            $v->add('barber', 'Unknown barber.');
        }

        if ($v->fails()) {
            flash('error', implode(' ', $v->errors()));
        } else {
            $pdo->prepare('INSERT INTO schedule_exceptions (date, barber_id, is_closed, open_time, close_time, note) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$date, $barberId ?: null, $closed ? 1 : 0, $closed ? null : $open, $closed ? null : $close, $note]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'booked' AND DATE(start_at) = ?" . ($barberId ? ' AND barber_id = ?' : ''));
            $stmt->execute($barberId ? [$date, $barberId] : [$date]);
            $affected = (int) $stmt->fetchColumn();

            flash('success', 'Exception added.' . ($affected > 0 ? " Note: $affected existing appointment(s) on this day. Move or cancel them manually." : ''));
        }
        redirect('admin/hours.php');
    }

    if ($action === 'delete_exception') {
        $pdo->prepare('DELETE FROM schedule_exceptions WHERE id = ?')->execute([post_int('exception_id')]);
        flash('success', 'Exception removed.');
        redirect('admin/hours.php');
    }
}

$hours = $pdo->query('SELECT * FROM opening_hours ORDER BY weekday')->fetchAll();
$exceptions = $pdo->query('SELECT e.*, b.name AS barber_name FROM schedule_exceptions e LEFT JOIN barbers b ON b.id = e.barber_id WHERE e.date >= CURDATE() ORDER BY e.date')->fetchAll();

view('admin/header', ['title' => 'Opening hours', 'active' => 'hours.php']);
?>
<div class="admin-title"><h1>Opening hours</h1></div>

<form class="card" method="post">
    <h2>Weekly schedule</h2>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="weekly">
    <div class="table-wrap">
        <table class="table hours-table">
            <thead><tr><th>Day</th><th>Opens</th><th>Closes</th><th>Closed</th></tr></thead>
            <tbody>
            <?php foreach ($hours as $h): $d = (int) $h['weekday']; ?>
                <tr>
                    <th scope="row"><?= e(weekday_name($d)) ?></th>
                    <td><label class="sr-only" for="open-<?= $d ?>">Opens on <?= e(weekday_name($d)) ?></label><input type="time" id="open-<?= $d ?>" name="open[<?= $d ?>]" step="900" value="<?= e(substr((string) $h['open_time'], 0, 5)) ?>"></td>
                    <td><label class="sr-only" for="close-<?= $d ?>">Closes on <?= e(weekday_name($d)) ?></label><input type="time" id="close-<?= $d ?>" name="close[<?= $d ?>]" step="900" value="<?= e(substr((string) $h['close_time'], 0, 5)) ?>"></td>
                    <td><label class="check"><input type="checkbox" name="closed[<?= $d ?>]" value="1"<?= $h['is_closed'] ? ' checked' : '' ?>><span class="sr-only">Closed on <?= e(weekday_name($d)) ?></span></label></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button class="btn btn-primary mt" type="submit">Save weekly schedule</button>
</form>

<div class="card">
    <h2>Holidays &amp; days off</h2>
    <p class="muted">Close the shop on a specific date, set different hours, or give one barber a day off.</p>
    <?php if ($exceptions !== []): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Date</th><th>Who</th><th>Hours</th><th>Note</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
                <tbody>
                <?php foreach ($exceptions as $ex): ?>
                    <tr>
                        <td class="num"><?= e(format_date($ex['date'])) ?></td>
                        <td><?= e($ex['barber_name'] ?? 'Whole shop') ?></td>
                        <td><?= $ex['is_closed'] ? 'Closed' : e(substr($ex['open_time'], 0, 5) . '–' . substr($ex['close_time'], 0, 5)) ?></td>
                        <td><?= e($ex['note']) ?></td>
                        <td class="actions">
                            <form class="inline-form" method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete_exception">
                                <input type="hidden" name="exception_id" value="<?= (int) $ex['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <h3>Add exception</h3>
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="add_exception">
        <div class="form-grid">
            <div class="field">
                <label for="ex-date">Date</label>
                <input type="date" id="ex-date" name="date" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="field">
                <label for="ex-barber">Applies to</label>
                <select id="ex-barber" name="barber">
                    <option value="0">Whole shop</option>
                    <?php foreach ($barbers as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="ex-type">Type</label>
                <select id="ex-type" name="type">
                    <option value="closed">Closed / day off</option>
                    <option value="custom">Different hours</option>
                </select>
            </div>
            <div class="field">
                <label for="ex-note">Note</label>
                <input type="text" id="ex-note" name="note" maxlength="150" placeholder="e.g. King's Day">
            </div>
            <div class="field">
                <label for="ex-open">Opens (for different hours)</label>
                <input type="time" id="ex-open" name="open" step="900">
            </div>
            <div class="field">
                <label for="ex-close">Closes (for different hours)</label>
                <input type="time" id="ex-close" name="close" step="900">
            </div>
        </div>
        <button class="btn btn-outline" type="submit">Add exception</button>
    </form>
</div>
<?php view('admin/footer'); ?>
