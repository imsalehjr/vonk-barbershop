<?php

require dirname(__DIR__) . '/src/bootstrap.php';

view('layout/header', ['title' => 'Privacy']);
?>
<section class="page-head">
    <div class="container"><h1>Privacy statement</h1></div>
</section>
<section class="section">
    <div class="container narrow prose">
        <p>Vonk Barbershop is a fictional business created as a portfolio demo. This statement shows how a real barbershop could handle your data under the GDPR (AVG).</p>
        <h2>What we collect</h2>
        <p>When you book, we store your name, email address, optional phone number and the details of your appointment.</p>
        <h2>Why</h2>
        <p>Only to schedule your appointment, send you a confirmation and contact you if something changes. We never sell or share your data.</p>
        <h2>How long</h2>
        <p>Appointment data is kept for a maximum of 12 months, after which it is deleted.</p>
        <h2>Your rights</h2>
        <p>You can ask us to view, correct or delete your data at any time by emailing hello@vonk-barbershop.test.</p>
        <h2>Cookies</h2>
        <p>We only use one functional session cookie that keeps the booking form secure. No tracking or advertising cookies.</p>
    </div>
</section>
<?php view('layout/footer'); ?>
