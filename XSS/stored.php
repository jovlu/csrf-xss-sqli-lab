<?php
// Podešavanje safeMode režima. 
$safeMode = false;
$message = '';

// Povezivanje sa bazom podataka.
$db = new mysqli('localhost', 'root', '', 'xss_demo');
if ($db->connect_error) {
    die('DB greška: ' . $db->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';

    if ($action === 'reset') {
        $db->query('TRUNCATE TABLE comments');
        $message = 'Svi komentari obrisani.';
        header('Location: stored.php');
        exit;
    } else {
        $author = trim($_POST['author'] ?? 'Anonimni');
        $body = trim($_POST['comment'] ?? '');
        if ($body !== '') {
            $ins = $db->prepare('INSERT INTO comments (author, body) VALUES (?, ?)');
            $ins->bind_param('ss', $author, $body);
            $ins->execute();
            $ins->close();
            $message = 'Komentar dodat.';
            header('Location: stored.php');
            exit;
        }
    }
}

// Učitavanje komentara.
$comments = [];
$res = $db->query('SELECT author, body, created_at FROM comments ORDER BY id DESC');
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $comments[] = $row;
    }
    $res->close();
}

function esc($value) {
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stored XSS demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Stored XSS</h1>
<p>Gosti mogu da ostave komentar. U ranjivoj verziji, maliciozni sadržaj (JS/CSS) se ispisuje svakom posetiocu.</p>

<?php if ($message): ?>
    <div class="card"><strong><?php echo esc($message); ?></strong></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="stored.php">
        <input type="hidden" name="action" value="add">
        <label>Autor (opciono)</label>
        <input type="text" name="author" placeholder="Anonimni" value="<?php echo esc($_POST['author'] ?? ''); ?>">
        <label>Komentar</label>
        <textarea name="comment" rows="3" placeholder="Unesi komentar..."></textarea>
        <button type="submit">Pošalji</button>
    </form>
    <form method="POST" action="stored.php" style="margin-top: 8px;">
        <input type="hidden" name="action" value="reset">
        <button type="submit">Obriši sve komentare</button>
    </form>
</div>

<div class="card">
    <h3>Komentari</h3>
    <?php if (empty($comments)): ?>
        <p>Nema komentara još uvek.</p>
    <?php else: ?>
        <ol>
            <?php foreach ($comments as $comment): ?>
                <li>
                    <strong>
                        <?php
                        echo $safeMode ? esc($comment['author']) : $comment['author'];
                        ?>
                    </strong>
                    <em>(<?php echo $comment['created_at']; ?>)</em><br>
                    <?php
                        echo $safeMode ? esc($comment['body']) : $comment['body'];
                    ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</div>
</body>
</html>
