<?php

require dirname(__DIR__, 2) . '/src/admin.php';

$pdo = Database::pdo();
$errors = [];
$editId = is_post() ? post_int('id') : get_int('edit');
$form = ['name' => '', 'description' => '', 'duration_min' => 30, 'price' => '', 'sort_order' => 0, 'is_active' => 1];

if ($editId > 0 && !is_post()) {
    $stmt = $pdo->prepare('SELECT * FROM services WHERE id = ?');
    $stmt->execute([$editId]);
    if ($row = $stmt->fetch()) {
        $form = $row;
        $form['price'] = number_format($row['price_cents'] / 100, 2, '.', '');
    } else {
        $editId = 0;
    }
}

if (is_post()) {
    $form = [
        'name' => post_string('name'),
        'description' => post_string('description'),
        'duration_min' => post_int('duration_min'),
        'price' => str_replace(',', '.', post_string('price')),
        'sort_order' => post_int('sort_order'),
        'is_active' => post_string('is_active') === '1' ? 1 : 0,
    ];

    $v = (new Validator())
        ->required('name', $form['name'], 'Naam')
        ->maxLength('name', $form['name'], 100, 'Naam')
        ->maxLength('description', $form['description'], 255, 'Omschrijving');
    if ($form['duration_min'] < 15 || $form['duration_min'] > 240 || $form['duration_min'] % 15 !== 0) {
        $v->add('duration_min', 'De duur moet een veelvoud van 15 minuten zijn (15–240).');
    }
    if (!preg_match('/^\d{1,4}(\.\d{1,2})?$/', $form['price'])) {
        $v->add('price', 'Vul een prijs in, bijvoorbeeld 28,00');
    }

    if (!$v->fails()) {
        $data = [
            $form['name'],
            $form['description'],
            $form['duration_min'],
            (int) round((float) $form['price'] * 100),
            $form['sort_order'],
            $form['is_active'],
        ];
        if ($editId > 0) {
            $pdo->prepare('UPDATE services SET name = ?, description = ?, duration_min = ?, price_cents = ?, sort_order = ?, is_active = ? WHERE id = ?')
                ->execute([...$data, $editId]);
            flash('success', 'Behandeling bijgewerkt.');
        } else {
            $pdo->prepare('INSERT INTO services (name, description, duration_min, price_cents, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)')
                ->execute($data);
            flash('success', 'Behandeling toegevoegd. Vergeet niet om hem aan je barbiers te koppelen.');
        }
        redirect('admin/services.php');
    }
    $errors = $v->errors();
}

$services = $pdo->query('SELECT * FROM services ORDER BY sort_order, name')->fetchAll();

view('admin/header', ['title' => 'Behandelingen', 'active' => 'services.php']);
?>
<div class="admin-title"><h1>Behandelingen</h1></div>

<div class="table-wrap">
    <table class="table">
        <thead><tr><th>Naam</th><th>Duur</th><th>Prijs</th><th>Status</th><th class="actions"><span class="sr-only">Acties</span></th></tr></thead>
        <tbody>
        <?php foreach ($services as $s): ?>
            <tr>
                <td><?= e($s['name']) ?><br><span class="muted small"><?= e($s['description']) ?></span></td>
                <td class="num"><?= (int) $s['duration_min'] ?> min</td>
                <td class="num"><?= e(money((int) $s['price_cents'])) ?></td>
                <td><span class="badge badge-<?= $s['is_active'] ? 'completed' : 'no_show' ?>"><?= $s['is_active'] ? 'Actief' : 'Verborgen' ?></span></td>
                <td class="actions"><a class="btn btn-ghost btn-sm" href="<?= e(url('admin/services.php?edit=' . (int) $s['id'])) ?>#service-form">Bewerken</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<form class="card" id="service-form" method="post" action="<?= e(url('admin/services.php')) ?>" novalidate>
    <h2><?= $editId > 0 ? 'Behandeling bewerken' : 'Behandeling toevoegen' ?></h2>
    <?= Csrf::field() ?>
    <input type="hidden" name="id" value="<?= (int) $editId ?>">
    <div class="form-grid">
        <div class="field">
            <label for="name">Naam</label>
            <input type="text" id="name" name="name" maxlength="100" required value="<?= e((string) $form['name']) ?>">
            <?php if (isset($errors['name'])): ?><p class="field-error"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="price">Prijs (€)</label>
            <input type="text" id="price" name="price" inputmode="decimal" required value="<?= e((string) $form['price']) ?>">
            <?php if (isset($errors['price'])): ?><p class="field-error"><?= e($errors['price']) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="duration_min">Duur</label>
            <select id="duration_min" name="duration_min">
                <?php for ($m = 15; $m <= 240; $m += 15): ?>
                    <option value="<?= $m ?>"<?= (int) $form['duration_min'] === $m ? ' selected' : '' ?>><?= $m ?> min</option>
                <?php endfor; ?>
            </select>
            <?php if (isset($errors['duration_min'])): ?><p class="field-error"><?= e($errors['duration_min']) ?></p><?php endif; ?>
        </div>
        <div class="field">
            <label for="sort_order">Volgorde</label>
            <input type="number" id="sort_order" name="sort_order" min="0" max="999" value="<?= (int) $form['sort_order'] ?>">
        </div>
    </div>
    <div class="field">
        <label for="description">Omschrijving</label>
        <input type="text" id="description" name="description" maxlength="255" value="<?= e((string) $form['description']) ?>">
        <?php if (isset($errors['description'])): ?><p class="field-error"><?= e($errors['description']) ?></p><?php endif; ?>
    </div>
    <label class="check"><input type="checkbox" name="is_active" value="1"<?= $form['is_active'] ? ' checked' : '' ?>> <span>Zichtbaar op de website</span></label>
    <button class="btn btn-primary" type="submit"><?= $editId > 0 ? 'Wijzigingen opslaan' : 'Behandeling toevoegen' ?></button>
    <?php if ($editId > 0): ?><a class="btn btn-ghost" href="<?= e(url('admin/services.php')) ?>">Annuleren</a><?php endif; ?>
</form>
<?php view('admin/footer'); ?>
