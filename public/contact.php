<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$hours = Database::pdo()->query('SELECT * FROM opening_hours ORDER BY weekday')->fetchAll();

view('layout/header', ['title' => 'Contact', 'active' => 'contact.php']);
?>
<section class="page-head">
    <div class="container">
        <h1>Zo vind je ons</h1>
        <p class="lead">Twee minuten lopen van station Eindhoven Centraal. Fietsenstalling direct voor de deur.</p>
    </div>
</section>
<section class="section">
    <div class="container split">
        <div class="card">
            <h2>Adres</h2>
            <address>Voorbeeldstraat 12<br>5611 AB Eindhoven</address>
            <h2>Contact</h2>
            <p><a href="tel:+31401234567">040 123 45 67</a><br><a href="mailto:hallo@vonk-barbershop.test">hallo@vonk-barbershop.test</a></p>
            <a class="btn btn-primary" href="<?= e(url('book.php')) ?>">Online boeken</a>
        </div>
        <div class="card">
            <h2>Openingstijden</h2>
            <?php view('partials/hours', ['hours' => $hours]); ?>
        </div>
    </div>
</section>
<?php view('layout/footer'); ?>
