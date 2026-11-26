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
            flash('success', 'Openingstijden opgeslagen. Bestaande afspraken blijven staan.');
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
            $v->add('time', 'Vul geldige afwijkende tijden in.');
        }
        if ($barberId > 0 && !in_array($barberId, array_map('intval', array_column($barbers, 'id')), true)) {
            $v->add('barber', 'Onbekende barbier.');
        }

        if ($v->fails()) {
            flash('error', implode(' ', $v->errors()));
        } else {
            $pdo->prepare('INSERT INTO schedule_exceptions (date, barber_id, is_closed, open_time, close_time, note) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute([$date, $barberId ?: null, $closed ? 1 : 0, $closed ? null : $open, $closed ? null : $close, $note]);

            $stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE status = 'booked' AND DATE(start_at) = ?" . ($barberId ? ' AND barber_id = ?' : ''));
            $stmt->execute($barberId ? [$date, $barberId] : [$date]);
            $affected = (int) $stmt->fetchColumn();

            flash('success', 'Uitzondering toegevoegd.' . ($affected > 0 ? " Let op: er staan $affected afspraak/afspraken op deze dag. Verplaats of annuleer die zelf." : ''));
        }
        redirect('admin/hours.php');
    }

    if ($action === 'delete_exception') {
        $pdo->prepare('DELETE FROM schedule_exceptions WHERE id = ?')->execute([post_int('exception_id')]);
        flash('success', 'Uitzondering verwijderd.');
        redirect('admin/hours.php');
    }
}

$hours = $pdo->query('SELECT * FROM opening_hours ORDER BY weekday')->fetchAll();
$exceptions = $pdo->query('SELECT e.*, b.name AS barber_name FROM schedule_exceptions e LEFT JOIN barbers b ON b.id = e.barber_id WHERE e.date >= CURDATE() ORDER BY e.date')->fetchAll();

view('admin/header', ['title' => 'Openingstijden', 'active' => 'hours.php']);
?>
<div class="admin-title"><h1>Openingstijden</h1></div>

<form class="card" method="post">
    <h2>Weekrooster</h2>
    <?= Csrf::field() ?>
    <input type="hidden" name="action" value="weekly">
    <div class="table-wrap">
        <table class="table hours-table">
            <thead><tr><th>Dag</th><th>Open</th><th>Dicht</th><th>Gesloten</th></tr></thead>
            <tbody>
            <?php foreach ($hours as $h): $d = (int) $h['weekday']; ?>
                <tr>
                    <th scope="row"><?= e(weekday_name($d)) ?></th>
                    <td><label class="sr-only" for="open-<?= $d ?>">Open op <?= e(weekday_name($d)) ?></label><input type="time" id="open-<?= $d ?>" name="open[<?= $d ?>]" step="900" value="<?= e(substr((string) $h['open_time'], 0, 5)) ?>"></td>
                    <td><label class="sr-only" for="close-<?= $d ?>">Dicht op <?= e(weekday_name($d)) ?></label><input type="time" id="close-<?= $d ?>" name="close[<?= $d ?>]" step="900" value="<?= e(substr((string) $h['close_time'], 0, 5)) ?>"></td>
                    <td><label class="check"><input type="checkbox" name="closed[<?= $d ?>]" value="1"<?= $h['is_closed'] ? ' checked' : '' ?>><span class="sr-only">Gesloten op <?= e(weekday_name($d)) ?></span></label></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <button class="btn btn-primary mt" type="submit">Weekrooster opslaan</button>
</form>

<div class="card">
    <h2>Feestdagen &amp; vrije dagen</h2>
    <p class="muted">Sluit de zaak op een bepaalde datum, stel afwijkende tijden in of geef één barbier een vrije dag.</p>
    <?php if ($exceptions !== []): ?>
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Datum</th><th>Wie</th><th>Tijden</th><th>Notitie</th><th class="actions"><span class="sr-only">Acties</span></th></tr></thead>
                <tbody>
                <?php foreach ($exceptions as $ex): ?>
                    <tr>
                        <td class="num"><?= e(format_date($ex['date'])) ?></td>
                        <td><?= e($ex['barber_name'] ?? 'Hele zaak') ?></td>
                        <td><?= $ex['is_closed'] ? 'Gesloten' : e(substr($ex['open_time'], 0, 5) . '–' . substr($ex['close_time'], 0, 5)) ?></td>
                        <td><?= e($ex['note']) ?></td>
                        <td class="actions">
                            <form class="inline-form" method="post">
                                <?= Csrf::field() ?>
                                <input type="hidden" name="action" value="delete_exception">
                                <input type="hidden" name="exception_id" value="<?= (int) $ex['id'] ?>">
                                <button class="btn btn-danger btn-sm" type="submit">Verwijderen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <form method="post" novalidate>
        <h3>Uitzondering toevoegen</h3>
        <?= Csrf::field() ?>
        <input type="hidden" name="action" value="add_exception">
        <div class="form-grid">
            <div class="field">
                <label for="ex-date">Datum</label>
                <input type="date" id="ex-date" name="date" required min="<?= date('Y-m-d') ?>">
            </div>
            <div class="field">
                <label for="ex-barber">Geldt voor</label>
                <select id="ex-barber" name="barber">
                    <option value="0">Hele zaak</option>
                    <?php foreach ($barbers as $b): ?>
                        <option value="<?= (int) $b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="field">
                <label for="ex-type">Soort</label>
                <select id="ex-type" name="type">
                    <option value="closed">Gesloten / vrije dag</option>
                    <option value="custom">Afwijkende tijden</option>
                </select>
            </div>
            <div class="field">
                <label for="ex-note">Notitie</label>
                <input type="text" id="ex-note" name="note" maxlength="150" placeholder="bijv. Koningsdag">
            </div>
            <div class="field">
                <label for="ex-open">Open (bij afwijkende tijden)</label>
                <input type="time" id="ex-open" name="open" step="900">
            </div>
            <div class="field">
                <label for="ex-close">Dicht (bij afwijkende tijden)</label>
                <input type="time" id="ex-close" name="close" step="900">
            </div>
        </div>
        <button class="btn btn-outline" type="submit">Uitzondering toevoegen</button>
    </form>
</div>
<?php view('admin/footer'); ?>
