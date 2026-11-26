<?php

require dirname(__DIR__, 2) . '/src/admin.php';

$pdo = Database::pdo();
$barbers = $pdo->query('SELECT id, name FROM barbers ORDER BY sort_order, name')->fetchAll();

$from = get_string('from') ?: date('Y-m-d');
$to = get_string('to') ?: date('Y-m-d', strtotime('+6 days'));
$barberId = get_int('barber');
$status = get_string('status') ?: 'booked';

$v = (new Validator())->date('from', $from)->date('to', $to);
if ($v->fails()) {
    $from = date('Y-m-d');
    $to = date('Y-m-d', strtotime('+6 days'));
}

$sql = 'SELECT a.*, b.name AS barber_name FROM appointments a JOIN barbers b ON b.id = a.barber_id
        WHERE a.start_at >= ? AND a.start_at < ?';
$params = [$from . ' 00:00:00', date('Y-m-d', strtotime($to . ' +1 day')) . ' 00:00:00'];
if ($barberId > 0) {
    $sql .= ' AND a.barber_id = ?';
    $params[] = $barberId;
}
if (in_array($status, ['booked', 'cancelled', 'completed', 'no_show'], true)) {
    $sql .= ' AND a.status = ?';
    $params[] = $status;
} else {
    $status = 'all';
}
$stmt = $pdo->prepare($sql . ' ORDER BY a.start_at, b.sort_order');
$stmt->execute($params);
$appointments = $stmt->fetchAll();

$stats = $pdo->query("SELECT
    SUM(DATE(start_at) = CURDATE() AND status = 'booked') AS today,
    COALESCE(SUM(CASE WHEN DATE(start_at) = CURDATE() AND status IN ('booked','completed') THEN price_cents END), 0) AS today_revenue,
    SUM(start_at >= NOW() AND start_at < CURDATE() + INTERVAL 7 DAY AND status = 'booked') AS week,
    SUM(status = 'cancelled' AND updated_at >= CURDATE() - INTERVAL 7 DAY) AS cancelled
    FROM appointments")->fetch();

$statusLabels = ['booked' => 'Geboekt', 'cancelled' => 'Geannuleerd', 'completed' => 'Afgerond', 'no_show' => 'Niet gekomen'];

view('admin/header', ['title' => 'Afspraken', 'active' => 'index.php']);
?>
<div class="admin-title">
    <h1>Afspraken</h1>
    <a class="btn btn-primary btn-sm" href="<?= e(url('book.php')) ?>" target="_blank" rel="noopener">Nieuwe afspraak</a>
</div>

<div class="stats">
    <div class="stat"><strong><?= (int) $stats['today'] ?></strong><span>Afspraken vandaag</span></div>
    <div class="stat"><strong><?= e(money((int) $stats['today_revenue'])) ?></strong><span>Verwachte omzet vandaag</span></div>
    <div class="stat"><strong><?= (int) $stats['week'] ?></strong><span>Komende 7 dagen</span></div>
    <div class="stat"><strong><?= (int) $stats['cancelled'] ?></strong><span>Geannuleerd, afgelopen 7 dagen</span></div>
</div>

<form class="filters" method="get">
    <div class="field">
        <label for="from">Van</label>
        <input type="date" id="from" name="from" value="<?= e($from) ?>">
    </div>
    <div class="field">
        <label for="to">Tot en met</label>
        <input type="date" id="to" name="to" value="<?= e($to) ?>">
    </div>
    <div class="field">
        <label for="barber">Barbier</label>
        <select id="barber" name="barber">
            <option value="0">Alle barbiers</option>
            <?php foreach ($barbers as $barber): ?>
                <option value="<?= (int) $barber['id'] ?>"<?= $barberId === (int) $barber['id'] ? ' selected' : '' ?>><?= e($barber['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="all"<?= $status === 'all' ? ' selected' : '' ?>>Alle</option>
            <?php foreach ($statusLabels as $key => $label): ?>
                <option value="<?= e($key) ?>"<?= $status === $key ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <button class="btn btn-outline" type="submit">Filteren</button>
</form>

<div class="table-wrap">
    <?php if ($appointments === []): ?>
        <p class="empty">Geen afspraken gevonden voor deze selectie.</p>
    <?php else: ?>
        <table class="table">
            <thead>
            <tr><th>Datum</th><th>Tijd</th><th>Klant</th><th>Behandeling</th><th>Barbier</th><th>Status</th><th class="actions"><span class="sr-only">Acties</span></th></tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td class="num"><?= e(format_short_date($a['start_at'])) ?></td>
                    <td class="num"><?= e(format_time($a['start_at'])) ?>–<?= e(format_time($a['end_at'])) ?></td>
                    <td><?= e($a['customer_name']) ?><br><span class="muted small"><?= e($a['customer_phone'] ?: $a['customer_email']) ?></span></td>
                    <td><?= e($a['service_name']) ?></td>
                    <td><?= e($a['barber_name']) ?></td>
                    <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($statusLabels[$a['status']]) ?></span></td>
                    <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/appointment.php?id=' . (int) $a['id'])) ?>">Openen</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
<?php view('admin/footer'); ?>
