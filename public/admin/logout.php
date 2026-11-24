<?php

require dirname(__DIR__, 2) . '/src/bootstrap.php';

if (is_post()) {
    Csrf::verify();
    Auth::logout();
}

redirect('admin/login.php');
