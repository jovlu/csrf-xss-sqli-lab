<?php
// Same-site podešavanje kolačića.
$mode = 'None';

// Zasebna sesija za POST primer.
session_name('CSRFPOST');
session_set_cookie_params([
    'path' => '/CSRF/post',
    'samesite' => $mode,
]);
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
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
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
    } elseif ($action === 'logout') {
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
    } elseif ($action === 'change_password' && $loggedIn) {
        $new = trim($_POST['new_password'] ?? '');
        if ($new === '') {
            setFlash('Prazno');
        } else {
            $q = $db->prepare('UPDATE users SET password = ? WHERE id = ?');
            $q->bind_param('si', $new, $_SESSION['user_id']);
            $q->execute();
            $q->close();
            setFlash('Lozinka postavljena na ' . $new);
        }
        redirectHome();
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
    <title>CSRF POST žrtva</title>
    <link rel="stylesheet" href="../../style.css">
</head>
<body>
<h1>POST žrtva</h1>

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
        <h3>Dobrodošli</h3>
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
        <p>Lozinka u bazi: <code><?php echo $user['password']; ?></code></p>
    <?php else: ?>
        <p>Nisi ulogovan.</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3>Promena lozinke</h3>
    <?php if ($loggedIn && $user): ?>
        <form method="POST" action="index.php">
            <input type="hidden" name="action" value="change_password">
            <label>Nova lozinka</label>
            <input type="text" name="new_password" required><br>
            <br>
            <button type="submit">Promeni</button>
        </form>
    <?php else: ?>
        <p>Prvo se prijavite.</p>
    <?php endif; ?>
</div>
</body>
</html>
