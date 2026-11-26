<?php
[$windowStart, $windowEnd] = Schedule::bookingWindow();
$selectedTime = $old['time'] ?? '';
?>
<div class="field">
    <label for="date">Datum</label>
    <input type="date" id="date" name="date" required
           min="<?= e($windowStart->format('Y-m-d')) ?>" max="<?= e($windowEnd->format('Y-m-d')) ?>"
           value="<?= e($old['date'] ?? '') ?>"
           <?= isset($errors['date']) ? 'aria-invalid="true" aria-describedby="date-error"' : '' ?>>
    <?php if (isset($errors['date'])): ?><p class="field-error" id="date-error"><?= e($errors['date']) ?></p><?php endif; ?>
</div>

<div class="field">
    <span class="label" id="time-label">Tijd</span>
    <div class="slots" id="slots" data-slots-url="<?= e(url('api/slots.php')) ?>" data-ignore="<?= (int) ($ignoreAppointment ?? 0) ?>" role="radiogroup" aria-labelledby="time-label" aria-live="polite">
        <?php if ($slots === null): ?>
            <p class="muted slots-hint">Kies een behandeling en datum om de vrije tijden te zien.</p>
        <?php elseif ($slots === []): ?>
            <p class="muted slots-hint">Op deze dag zijn geen tijden vrij. Probeer een andere datum.</p>
        <?php else: ?>
            <?php $currentPart = ''; ?>
            <?php foreach (array_keys($slots) as $time): ?>
                <?php $part = $time < '12:00' ? 'Ochtend' : ($time < '17:00' ? 'Middag' : 'Avond'); ?>
                <?php if ($part !== $currentPart): $currentPart = $part; ?>
                    <p class="slots-group"><?= e($part) ?></p>
                <?php endif; ?>
                <label class="slot">
                    <input type="radio" name="time" value="<?= e($time) ?>" required<?= $selectedTime === $time ? ' checked' : '' ?>>
                    <span><?= e($time) ?></span>
                </label>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php if (isset($errors['time'])): ?><p class="field-error"><?= e($errors['time']) ?></p><?php endif; ?>
</div>
