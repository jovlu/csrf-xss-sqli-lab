<?php
// Podešavanje safeMode režima.
$safeMode = false;

// Podrazumevani demo podaci za prikaz na stranici.
$defaults = [
    'name' => 'Mila Petrović',
    'dob' => '19.08.2001',
    'phone' => '+381 64 123 4567',
    'address' => 'Knez Mihailova 12, Beograd',
];

$profile = $defaults;
$profile['name'] = $_GET['name'] ?? $defaults['name'];

$headline = 'Student ETF-a';

// Ako je safeMode uključen, specijalni HTML karakteri se pretvaraju u običan tekst.
function renderValue($value, $safeMode)
{
    return $safeMode ? htmlspecialchars($value, ENT_QUOTES, 'UTF-8') : $value;
}

function escapeAttr($value)
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reflektovani XSS demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Reflektovani XSS</h1>

<div class="card">
    <h3>Tvoj profil</h3>
    <p id="profile">
        <strong><?php echo renderValue($profile['name'], $safeMode); ?></strong>
    </p>
    <p><strong>Status / naslov:</strong> <?php echo renderValue($headline, $safeMode); ?></p>
    <ul class="profile-meta">
        <li><strong>Datum rođenja:</strong> <?php echo renderValue($profile['dob'], $safeMode); ?></li>
        <li><strong>Telefon:</strong> <?php echo renderValue($profile['phone'], $safeMode); ?></li>
        <li><strong>Adresa:</strong> <?php echo renderValue($profile['address'], $safeMode); ?></li>
    </ul>
</div>

<div class="card">
    <h3>Izmena profila</h3>
    <form method="GET" action="reflected.php" class="stacked">
        <label for="name">Ime i prezime</label><br>
        <input id="name" type="text" name="name" value="<?php echo escapeAttr($profile['name']); ?>"><br>

        <button type="submit">Promeni ime</button>
    </form>
</div>
</body>
</html>
