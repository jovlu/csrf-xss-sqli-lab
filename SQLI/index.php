<?php
// Podešavanje korišćenja pripremljenih upita.
$SAFE_MODE = false;

session_start();
mysqli_report(MYSQLI_REPORT_OFF);

// Flash status.
function setFlash($msg) {
    $_SESSION['flash'] = $msg;
}

function popFlash() {
    $msg = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $msg;
}

// Povezivanje sa bazom podataka.
$db = new mysqli('localhost', 'root', '', 'sqli_demo');
if ($db->connect_error) {
    die('DB greška: ' . $db->connect_error);
}

$message = popFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['action'] ?? '') === 'reset') {
        // Vraća tabelu i demo naloge na početno stanje.
        $db->query("CREATE TABLE IF NOT EXISTS users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL)");
        $db->query('TRUNCATE TABLE users');
        $db->query("INSERT INTO users (username, password) VALUES ('ana', 'lozinka'), ('marko', 'tajna123')");
        setFlash('Reset: vraćeni početni nalozi.');
        header('Location: index.php');
        exit;
    }

    $u = $_POST['username'] ?? '';
    $p = $_POST['password'] ?? '';

    $multiError = '';
    $row = null;

    if ($SAFE_MODE) {
        // Pripremljen upit
        $stmt = $db->prepare('SELECT id, username FROM users WHERE username = ? AND password = ? LIMIT 1');
        $stmt->bind_param('ss', $u, $p);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res instanceof mysqli_result) {
            $row = $res->fetch_assoc();
            $res->free();
        }
        $stmt->close();
    } else {
        // Ranjiva varijanta direktno ubacuje korisnički unos u SQL string.
        $sql = "SELECT id, username FROM users WHERE username = '$u' AND password = '$p' LIMIT 1";

        if (strpos($sql, ';') !== false) {
            if ($db->multi_query($sql)) {
                $first = $db->store_result();
                if ($first) {
                    $row = $first->fetch_assoc();
                    $first->free();
                }
                while ($db->more_results() && $db->next_result()) {
                    $tmp = $db->store_result();
                    if ($tmp) { $tmp->free(); }
                }
            } else {
                $multiError = $db->error;
            }
        } else {
            $res = $db->query($sql);
            if ($res instanceof mysqli_result) {
                $row = $res->fetch_assoc();
                $res->free();
            }
        }
    }

    if (!$SAFE_MODE && $multiError) {
        $message = 'SQL greška (stacked upiti blokirani na serveru): ' . $multiError;
    } elseif ($row) {
        $message = "Login uspešan! Dobrodošli, {$row['username']}.";
    } elseif ($SAFE_MODE) {
        $message = 'Login neuspešan.';
    } else {
        $message = 'Login neuspešan.';
    }

    setFlash($message);
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SQL Injection demo</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>SQL Injection demo (login)</h1>

<?php if ($message): ?>
    <div class="card"><strong><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></strong></div>
<?php endif; ?>

<div class="card">
    <form method="POST" action="index.php">
        <label>Username</label><br/>
        <input name="username" type="text" value="<?php echo htmlspecialchars($_POST['username'] ?? 'ana', ENT_QUOTES, 'UTF-8'); ?>" required></br>
        <label>Password</label><br/>
        <input name="password" type="password" value="lozinka"><br/>
        <button type="submit">Login</button>
    </form>
</div>

<div class="card">
    <h3>Administracija</h3>
    <p>Resetuje demo korisnike i tabelu.</p>
    <form method="POST" action="index.php" style="margin-top: 12px;">
        <input type="hidden" name="action" value="reset">
        <button type="submit">Resetuj bazu</button>
    </form>
</div>
</body>
</html>
