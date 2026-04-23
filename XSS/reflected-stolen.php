<?php
session_start();

function setFlash($msg) {
    $_SESSION['flash'] = $msg;
}

function popFlash() {
    if (!isset($_SESSION['flash'])) {
        return '';
    }
    $msg = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $msg;
}

$db = new mysqli('localhost', 'root', '', 'xss_demo');
if ($db->connect_error) {
    die('DB greška: ' . $db->connect_error);
}

$message = popFlash();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'reset') {
        $db->query('TRUNCATE TABLE stolen_reflected');
        setFlash('Obrisani svi ukradeni podaci (reflected).');
        header('Location: reflected-stolen.php');
        exit;
    }
}

if (isset($_GET['creds'])) {
    $stolen = $_GET['creds'];
    $ins = $db->prepare('INSERT INTO stolen_reflected (stolen) VALUES (?)');
    $ins->bind_param('s', $stolen);
    $ins->execute();
    $ins->close();
}

$rows = [];
$res = $db->query('SELECT stolen, created_at FROM stolen_reflected ORDER BY id DESC');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $rows[] = $row;
    }
    $res->close();
}

function esc($v) { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ukradeni podaci (reflected)</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Ukradeni podaci (reflected)</h1>
<?php if ($message): ?>
    <div class="card"><strong><?php echo esc($message); ?></strong></div>
<?php endif; ?>

<div class="card">
    <?php if (isset($_GET['creds'])): ?>
        <p><strong>Primljeno:</strong> <?php echo esc($_GET['creds']); ?></p>
    <?php else: ?>
        <p>Nema novih podataka u query stringu.</p>
    <?php endif; ?>
    <p style="color:#666; font-size: 0.9em;">URL query: <?php echo esc($_SERVER['QUERY_STRING'] ?? ''); ?></p>
    <form method="POST" action="reflected-stolen.php" style="margin-top:8px;">
        <input type="hidden" name="action" value="reset">
        <button type="submit">Obriši sve ukradene unose</button>
    </form>
</div>

<div class="card">
    <h3>Snimljeno u bazi</h3>
    <?php if (empty($rows)): ?>
        <p>Nema unosa.</p>
    <?php else: ?>
        <ol>
            <?php foreach ($rows as $row): ?>
                <li><?php echo esc($row['stolen']); ?> <em>(<?php echo esc($row['created_at']); ?>)</em></li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
</body>
</html>
