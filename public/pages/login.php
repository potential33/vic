<?php
// /public/app/public/pages/login.php
$pageTitle = 'Log in';

// Already logged in → go home
if (auth_check()) {
    redirect('/');
}

$error    = null;
$username = $_POST['username'] ?? '';
$next     = $_GET['next'] ?? ($_POST['next'] ?? '/');
// Only allow relative paths, block open redirect
if (!str_starts_with($next, '/') || str_starts_with($next, '//')) $next = '/';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res = auth_attempt($username, $_POST['password'] ?? '');
    if ($res['ok']) {
        flash_set('success', 'Welcome back.');
        redirect($next);
    }
    $error = $res['error'];
}
?>
<div class="login-layout">
    <div class="login-brand">
        <img src="/assets/logo.svg" alt="" width="58" height="56">
        <p class="eyebrow">CHIBUGWU SCHOOL PORTAL</p>
        <h1>Welcome back</h1>
        <p class="muted">A better-connected school community starts here.</p>
    </div>
    <div class="card login-card">
        <h2>Sign in</h2>
        <p class="muted">Enter your account details to continue.</p>

        <?php if ($error): ?>
            <div class="flash flash-error" style="margin-bottom:1rem;"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div class="field">
                <label>Username, admission number, email, or full name</label>
                <input name="username" required autofocus
                       value="<?= e($username) ?>" autocomplete="username">
            </div>
            <div class="field">
                <label>Password</label>
                <input type="password" name="password" required
                       autocomplete="current-password">
            </div>
            <div class="actions">
                <button class="btn" type="submit" style="width:100%;">Log in</button>
            </div>
        </form>
    </div>
</div>