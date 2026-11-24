<?php

require dirname(__DIR__, 2) . '/src/admin.php';

$pdo = Database::pdo();
$services = $pdo->query('SELECT id, name FROM services ORDER BY sort_order, name')->fetchAll();
$serviceIds = array_map('intval', array_column($services, 'id'));
$errors = [];
$editId = is_post() ? post_int('id') : get_int('edit');
$form = ['name' => '', 'bio' => '', 'sort_order' => 0, 'is_active' => 1, 'services' => $serviceIds];

if ($editId > 0 && !is_post()) {
    $stmt = $pdo->prepare('SELECT * FROM barbers WHERE id = ?');
    $stmt->execute([$editId]);
    if ($row = $stmt->fetch()) {
        $form = $row;
        $stmt = $pdo->prepare('SELECT service_id FROM barber_services WHERE barber_id = ?');
        $stmt->execute([$editId]);
        $form['services'] = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } else {
        $editId = 0;
    }
}

if (is_post()) {
    $chosen = $_POST['services'] ?? [];
    $form = [
        'name' => post_string('name'),
        'bio' => post_string('bio'),
        'sort_order' => post_int('sort_order'),
        'is_active' => post_string('is_active') === '1' ? 1 : 0,
        'services' => array_values(array_intersect($serviceIds, array_map('intval', is_array($chosen) ? $chosen : []))),
    ];

    $v = (new Validator())
        ->required('name', $form['name'], 'Name')
        ->maxLength('name', $form['name'], 100, 'Name')
        ->maxLength('bio', $form['bio'], 255, 'Bio');

    if (!$v->fails()) {
        $pdo->beginTransaction();
        if ($editId > 0) {
            $pdo->prepare('UPDATE barbers SET name = ?, bio = ?, sort_order = ?, is_active = ? WHERE id = ?')
                ->execute([$form['name'], $form['bio'], $form['sort_order'], $form['is_active'], $editId]);
            $barberId = $editId;
        } else {
            $pdo->prepare('INSERT INTO barbers (name, bio, sort_order, is_active) VALUES (?, ?, ?, ?)')
                ->execute([$form['name'], $form['bio'], $form['sort_order'], $form['is_active']]);
            $barberId = (int) $pdo->lastInsertId();
        }
        $pdo->prepare('DELETE FROM barber_services WHERE barber_id = ?')->execute([$barberId]);
        $insert = $pdo->prepare('INSERT INTO barber_services (barber_id, service_id) VALUES (?, ?)');
        foreach ($form['services'] as $serviceId) {
            $insert->execute([$barberId, $serviceId]);
        }
        $pdo->commit();

        flash('success', $editId > 0 ? 'Barber updated.' : 'Barber added.');
        redirect('admin/barbers.php');
    }
    $errors = $v->errors();
}

$barbers = $pdo->query('SELECT b.*, COUNT(bs.service_id) AS service_count FROM barbers b LEFT JOIN barber_services bs ON bs.barber_id = b.id GROUP BY b.id ORDER BY b.sort_order, b.name')->fetchAll();

view('admin/header', ['title' => 'Barbers', 'active' => 'barbers.php']);
?>
<div class="admin-title"><h1>Barbers</h1></div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Name</th><th>Services</th><th>Status</th><th class="actions"><span class="sr-only">Actions</span></th></tr></thead>
        <tbody>
        <?php foreach ($barbers as $b): ?>
            <tr>
                <td><?= e($b['name']) ?><br><span class="muted small"><?= e($b['bio']) ?></span></td>
                <td class="num"><?= (int) $b['service_count'] ?></td>
                <td><span class="badge badge-<?= $b['is_active'] ? 'completed' : 'no_show' ?>"><?= $b['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/barbers.php?edit=' . (int) $b['id'])) ?>#barber-form">Edit</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<form class="card" id="barber-form" method="post" action="<?= e(url('admin/barbers.php')) ?>" novalidate>
    <h2><?= $editId > 0 ? 'Edit barber' : 'Add a barber' ?></h2>
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $editId ?>">
    <div class="form-grid">
        <div class="field">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" maxlength="100" required value="<?= e((string) $form['name']) ?>">
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="sort_order">Sort order</label>
            <input type="number" id="sort_order" name="sort_order" min="0" max="999" value="<?= (int) $form['sort_order'] ?>">
        </div>
    </div>
    <div class="field">
        <label for="bio">Short bio</label>
        <input type="text" id="bio" name="bio" maxlength="255" value="<?= e((string) $form['bio']) ?>">
        <?php if (isset($errors['bio'])): ?><p class="field-error"><?= e($errors['bio']) ?></p><?php endif; ?>
    </div>
    <fieldset class="field">
        <legend class="label">Services this barber offers</legend>
        <?php foreach ($services as $s): ?>
            <label class="check"><input type="checkbox" name="services[]" value="<?= (int) $s['id'] ?>"<?= in_array((int) $s['id'], $form['services'], true) ? ' checked' : '' ?>> <span><?= e($s['name']) ?></span></label>
        <?php endforeach; ?>
    </fieldset>
    <label class="check"><input type="checkbox" name="is_active" value="1"<?= $form['is_active'] ? ' checked' : '' ?>> <span>Active (can be booked)</span></label>
    <button class="btn btn-primary" type="submit"><?= $editId > 0 ? 'Save changes' : 'Add barber' ?></button>
    <?php if ($editId > 0): ?><a class="btn btn-ghost" href="<?= e(url('admin/barbers.php')) ?>">Cancel</a><?php endif; ?>
</form>
<?php view('admin/footer'); ?>
