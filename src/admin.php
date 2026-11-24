<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');

Auth::require();

if (is_post()) {
    Csrf::verify();
}
