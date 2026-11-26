<?php

require dirname(__DIR__) . '/src/bootstrap.php';

view('layout/header', ['title' => 'Privacy']);
?>
<section class="page-head">
    <div class="container"><h1>Privacyverklaring</h1></div>
</section>
<section class="section">
    <div class="container narrow prose">
        <p>Vonk Barbershop is een verzonnen bedrijf, gemaakt als portfoliodemo. Deze verklaring laat zien hoe een echte kapperszaak volgens de AVG met je gegevens kan omgaan.</p>
        <h2>Wat we bewaren</h2>
        <p>Als je een afspraak maakt, bewaren we je naam, e-mailadres, eventueel je telefoonnummer en de gegevens van je afspraak.</p>
        <h2>Waarom</h2>
        <p>Alleen om je afspraak in te plannen, je een bevestiging te sturen en contact op te nemen als er iets verandert. We verkopen of delen je gegevens nooit.</p>
        <h2>Hoe lang</h2>
        <p>Afspraakgegevens bewaren we maximaal 12 maanden. Daarna worden ze verwijderd.</p>
        <h2>Jouw rechten</h2>
        <p>Je kunt ons altijd vragen je gegevens in te zien, aan te passen of te verwijderen. Mail daarvoor naar hallo@vonk-barbershop.test.</p>
        <h2>Cookies</h2>
        <p>We gebruiken alleen één functionele sessiecookie die het boekingsformulier beveiligt. Geen tracking- of advertentiecookies.</p>
    </div>
</section>
<?php view('layout/footer'); ?>
