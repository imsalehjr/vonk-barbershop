<?php
[$windowStart, $windowEnd] = Schedule::bookingWindow();
$selectedTime = $old['time'] ?? '';
?>
<div class="field">
    <label for="date">Date</label>
    <input type="date" id="date" name="date" required
           min="<?= e($windowStart->format('Y-m-d')) ?>" max="<?= e($windowEnd->format('Y-m-d')) ?>"
           value="<?= e($old['date'] ?? '') ?>"
           <?= isset($errors['date']) ? 'aria-invalid="true" aria-describedby="date-error"' : '' ?>>
    <?php if (isset($errors['date'])): ?><p class="field-error" id="date-error"><?= e($errors['date']) ?></p><?php endif; ?>
</div>

<div class="field">
    <span class="label" id="time-label">Time</span>
    <div class="slots" id="slots" data-slots-url="<?= e(url('api/slots.php')) ?>" data-ignore="<?= (int) ($ignoreAppointment ?? 0) ?>" role="radiogroup" aria-labelledby="time-label" aria-live="polite">
        <?php if ($slots === null): ?>
            <p class="muted slots-hint">Choose a service and date to see available times.</p>
        <?php elseif ($slots === []): ?>
            <p class="muted slots-hint">No times available on this day. Please try another date.</p>
        <?php else: ?>
            <?php $currentPart = ''; ?>
            <?php foreach (array_keys($slots) as $time): ?>
                <?php $part = $time < '12:00' ? 'Morning' : ($time < '17:00' ? 'Afternoon' : 'Evening'); ?>
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
