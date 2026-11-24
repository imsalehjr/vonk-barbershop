<?php

require dirname(__DIR__) . '/src/bootstrap.php';

$hours = Database::pdo()->query('SELECT * FROM opening_hours ORDER BY weekday')->fetchAll();

view('layout/header', ['title' => 'Contact', 'active' => 'contact.php']);
?>
<section class="page-head">
    <div class="container">
        <h1>Find us</h1>
        <p class="lead">Two minutes from Eindhoven Central Station. Bike parking right in front.</p>
    </div>
</section>
<section class="section">
    <div class="container split">
        <div class="card">
            <h2>Address</h2>
            <address>Voorbeeldstraat 12<br>5611 AB Eindhoven</address>
            <h2>Contact</h2>
            <p><a href="tel:+31401234567">040 123 45 67</a><br><a href="mailto:hello@vonk-barbershop.test">hello@vonk-barbershop.test</a></p>
            <a class="btn btn-primary" href="<?= e(url('book.php')) ?>">Book online</a>
        </div>
        <div class="card">
            <h2>Opening hours</h2>
            <?php view('partials/hours', ['hours' => $hours]); ?>
        </div>
    </div>
</section>
<?php view('layout/footer'); ?>
