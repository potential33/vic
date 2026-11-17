<?php
// /public/app/public/layout.php
declare(strict_types=1);
$flash = flash_get();
$user = auth_user();
$role = $user['role'] ?? null;
$currentRoute = (string) ($_GET['r'] ?? 'dashboard');

$navigation = [];
if ($role === 'admin') {
    $navigation = [
        ['label' => 'People', 'items' => [
            ['Students', 'students'], ['Teachers', 'teachers'], ['Enrollments', 'enrollments'],
        ]],
        ['label' => 'Teaching', 'items' => [
            ['Assignments', 'assignments'], ['Grade entry', 'results/entry'], ['Coverage', 'coverage'],
        ]],
        ['label' => 'Results', 'items' => [
            ['Approve results', 'results/approve'], ['Anomalies', 'results/anomalies'],
        ]],
        ['label' => 'Reports', 'items' => [
            ['Student reports', 'reports/student'], ['Broadsheet', 'reports/class'],
            ['Remarks', 'reports/remarks'], ['Publish reports', 'reports/publish'],
        ]],
        ['label' => 'School setup', 'items' => [
            ['Arm subjects', 'class-arms/subjects'], ['Exemptions', 'exemptions'], ['Settings', 'settings'],
        ]],
    ];
} elseif ($role === 'teacher') {
    $navigation = [
        ['label' => 'People', 'items' => [
            ['Students', 'students'],
        ]],
        ['label' => 'Teaching', 'items' => [
            ['Assignments', 'assignments'], ['Grade entry', 'results/entry'], ['My coverage', 'coverage'],
        ]],
        ['label' => 'Reports', 'items' => [
            ['Student reports', 'reports/student'], ['Broadsheet', 'reports/class'],
        ]],
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle ?? 'School') ?> · Chibugwu</title>
    <link rel="stylesheet" href="/assets/style.css?v=<?= e((string) filemtime(__DIR__ . '/assets/style.css')) ?>">
</head>
<body>
<header class="topbar">
    <div class="header-inner">
        <a href="<?= e(route_link('dashboard')) ?>" class="brand">
            <img src="/assets/logo.svg" alt="" width="42" height="40">
            <span>Chibugwu</span>
        </a>
        <?php if ($user): ?>
            <nav class="primary-nav" aria-label="Main navigation">
                <a class="nav-link <?= $currentRoute === 'dashboard' ? 'active' : '' ?>"
                   href="<?= e(route_link('dashboard')) ?>">Overview</a>
                <?php if ($role === 'student'): ?>
                    <a class="nav-link <?= $currentRoute === 'me/report' ? 'active' : '' ?>"
                       href="<?= e(route_link('me/report')) ?>">My report card</a>
                <?php else: ?>
                    <?php foreach ($navigation as $group): ?>
                        <?php
                        $groupActive = false;
                        foreach ($group['items'] as $item) {
                            if ($currentRoute === $item[1]) {
                                $groupActive = true;
                                break;
                            }
                        }
                        ?>
                        <details class="nav-group">
                            <summary class="<?= $groupActive ? 'active' : '' ?>"><?= e($group['label']) ?></summary>
                            <div class="nav-menu">
                                <?php foreach ($group['items'] as [$label, $route]): ?>
                                    <a class="<?= $currentRoute === $route ? 'active' : '' ?>"
                                       href="<?= e(route_link($route)) ?>"><?= e($label) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>
            </nav>
            <div class="user-controls">
                <span class="user-name"><?= e($user['teacher_name'] ?? $user['username']) ?></span>
                <details class="account-menu">
                    <summary class="account-toggle" aria-label="Account options">Account</summary>
                    <div class="account-popover">
                        <span class="account-role"><?= e(ucfirst((string) $role)) ?> account</span>
                        <a href="<?= e(route_link('password')) ?>">Change password</a>
                        <form method="post" action="<?= e(route_link('logout')) ?>">
                            <?= csrf_field() ?>
                            <button type="submit">Log out</button>
                        </form>
                    </div>
                </details>
            </div>
        <?php endif; ?>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<main>
    <?= $content ?>
</main>

<footer class="footer">
    <small>Chibugwu School Portal</small>
</footer>
</body>
</html>
