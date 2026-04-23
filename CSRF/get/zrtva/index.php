<?php
// Zasebna sesija za GET primer
session_name('CSRFGET');
session_set_cookie_params(['path' => '/CSRF/get', 'httponly' => true, 'samesite' => 'Lax']);
session_start();

// Flash status
function setFlash($msg) {
    $_SESSION['flash'] = $msg;
}

function popFlash() {
    $msg = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $msg;
}

function redirectHome() {
    header('Location: index.php');
    exit;
}

// Podešavanje CSRF tokena
$csrfEnabled = false;
if ($csrfEnabled && !isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
}

// Povezivanje sa bazom podataka.
$db = new mysqli('localhost', 'root', '', 'csrf_demo');
if ($db->connect_error) {
    die('DB greška');
}

$msg = popFlash();
$loggedIn = isset($_SESSION['user_id']);
$user = null;

// Učitavanje podataka o nalogu.
function loadUser($db, $id) {
    $q = $db->prepare('SELECT id, username, password FROM users WHERE id = ?');
    $q->bind_param('i', $id);
    $q->execute();
    $row = $q->get_result()->fetch_assoc();
    $q->close();
    return $row;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $a = $_POST['action'] ?? '';
    if ($a === 'login') {
        // Učitavanje korisnika na osnovu unetih podataka.
        $u = $_POST['username'] ?? '';
        $p = $_POST['password'] ?? '';
        $q = $db->prepare('SELECT id FROM users WHERE username = ? AND password = ? LIMIT 1');
        $q->bind_param('ss', $u, $p);
        $q->execute();
        $found = $q->get_result()->fetch_assoc();
        $q->close();
        if ($found) {
            $_SESSION['user_id'] = $found['id'];
            $loggedIn = true;
            setFlash('Login OK');
        } else {
            setFlash('Pogrešno');
        }
        redirectHome();
    } elseif ($a === 'logout') {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'] ?? '/',
            'domain' => $cookie['domain'] ?? '',
            'secure' => $cookie['secure'] ?? false,
            'httponly' => $cookie['httponly'] ?? true,
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
        session_destroy();
        redirectHome();
    }
}

// Promena lozinke se ovde pokreće preko GET parametara u URL-u.
if (($_GET['action'] ?? '') === 'change_password') {
    $tokenMissing = $csrfEnabled && (!isset($_GET['csrf'], $_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_GET['csrf']));

    if (!$loggedIn) {
        $msg = 'Nisi ulogovan.';
    } elseif ($tokenMissing) {
        $msg = 'CSRF token nedostaje ili nije ispravan.';
    } else {
        $new = trim($_GET['new_password'] ?? '');
        if ($new === '') {
            $msg = 'Prazna lozinka.';
        } else {
            $q = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $q->bind_param('si', $new, $_SESSION['user_id']);
            $q->execute();
            $q->close();
            $msg = 'Lozinka promenjena na "' . $new . '"';
        }
    }
}

if ($loggedIn) {
    $user = loadUser($db, $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>CSRF GET žrtva</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
<h1>GET žrtva</h1>

<?php if ($msg): ?>
    <div class="card"><strong><?php echo $msg; ?></strong></div>
<?php endif; ?>

<div class="card">
    <?php if (!$loggedIn): ?>
        <h3>Login</h3>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="login">
            <label>Korisnik</label>
            <input type="text" name="username" value="<?php echo $_POST['username'] ?? 'test'; ?>" required><br>
            <label>Lozinka</label>
            <input type="password" name="password" value="<?php echo $_POST['password'] ?? '123'; ?>" required><br>
            <button type="submit">Login</button>
        </form>
    <?php else: ?>
        <h3>Već si ulogovan</h3>
        <form method="POST" action="index.php" style="margin-top:8px;">
            <input type="hidden" name="action" value="logout">
            <button type="submit">Logout</button>
        </form>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Profil</h3>
    <?php if ($loggedIn && $user): ?>
        <p>Ulogovan kao <strong><?php echo $user['username']; ?></strong></p>
        <p>Lozinka: <code><?php echo $user['password']; ?></code></p>
    <?php else: ?>
        <p>Nisi ulogovan.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Promena lozinke (GET)</h3>
    <?php if ($loggedIn && $user): ?>
        <form method="GET" action="index.php">
            <input type="hidden" name="action" value="change_password">
            <?php if ($csrfEnabled && isset($_SESSION['csrf_token'])): ?>
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php endif; ?>
            <label>Nova lozinka</label>
            <input type="text" name="new_password" required><br>
            <br>
            <button type="submit">Promeni lozinku</button>
        </form>
    <?php else: ?>
        <p>Prvo se prijavite, pa će forma biti dostupna.</p>
    <?php endif; ?>
</div>
</body>
</html>
