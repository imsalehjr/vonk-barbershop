<dl class="hours">
    <?php foreach ($hours as $row): ?>
        <div class="hours-row<?= (int) $row['weekday'] === (int) date('N') ? ' is-today' : '' ?>">
            <dt><?= e(weekday_name((int) $row['weekday'])) ?></dt>
            <dd><?= (int) $row['is_closed'] === 1 ? 'Closed' : e(substr($row['open_time'], 0, 5) . ' – ' . substr($row['close_time'], 0, 5)) ?></dd>
        </div>
    <?php endforeach; ?>
</dl>
