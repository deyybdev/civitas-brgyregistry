<?php
// Each page sets $title and $active before including this file.

$icons = [
    'grid'   => '<rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>',
    'users'  => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>',
    'plus'   => '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/><path d="M12 18v-6"/><path d="M9 15h6"/>',
    'wallet' => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
    'bar'    => '<path d="M3 3v18h18"/><path d="M18 17V9"/><path d="M13 17V5"/><path d="M8 17v-3"/>',
    'check'  => '<path d="M3.85 8.62a4 4 0 0 1 4.78-4.77 4 4 0 0 1 6.74 0 4 4 0 0 1 4.78 4.78 4 4 0 0 1 0 6.74 4 4 0 0 1-4.77 4.78 4 4 0 0 1-6.75 0 4 4 0 0 1-4.78-4.77 4 4 0 0 1 0-6.76Z"/><path d="m9 12 2 2 4-4"/>',
    'search' => '<circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>',
    'moon'   => '<path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>',
    'sun'    => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/>',
    'home'   => '<path d="M15 21v-8a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v8"/><path d="M3 10a2 2 0 0 1 .709-1.528l7-5.999a2 2 0 0 1 2.582 0l7 5.999A2 2 0 0 1 21 10v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>',
];

$menu = [
    'index.php'       => ['Dashboard', 'grid'],
    'residents.php'   => ['Residents', 'users'],
    'households.php'  => ['Households', 'home'],
    'request_new.php' => ['New Request', 'plus'],
    'requests.php'    => ['Requests & Payments', 'wallet'],
    'reports.php'     => ['Reports', 'bar'],
    'verify.php'      => ['Verify Resident', 'check'],
];

// Returns an icon as inline SVG.
function icon($name)
{
    global $icons;
    return '<svg class="i" viewBox="0 0 24 24" aria-hidden="true">' . $icons[$name] . '</svg>';
}

// Returns a colored status badge.
function badge($status)
{
    $colors = [
        'Active'      => 'a',
        'Released'    => 'a',
        'Pending'     => 'p',
        'Inactive'    => 'p',
        'Processing'  => 'r',
        'Transferred' => 'r',
        'Deceased'    => 'x',
        'Paid'        => 'a',
        'Partial'     => 'p',
        'Unpaid'      => 'x',
        'No fee'      => 'r',
        'Verified'    => 'a',
        'Not verified' => 'x',
    ];
    $class = $colors[$status] ?? 'r';
    return '<span class="tag ' . $class . '">' . htmlspecialchars($status) . '</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Civitas | <?= htmlspecialchars($title) ?></title>
    <link rel="icon" href="images/civitas-mark.svg">
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script>
        // Apply the saved theme before the page paints, to avoid a flash
        try { document.documentElement.dataset.theme = localStorage.getItem("theme") || "light"; }
        catch (e) { document.documentElement.dataset.theme = "light"; }
    </script>
    <link rel="stylesheet" href="css/style.css?v=<?= filemtime(__DIR__ . '/css/style.css') ?>">
</head>
<body>
<div class="app">

    <nav class="sb">
        <a class="logo" href="index.php">
            <img src="images/civitas-mark.svg" alt=""><span style="margin-right:-0.28em">Civitas</span>
        </a>
        <?php foreach ($menu as $file => [$label, $iconName]): ?>
            <a href="<?= $file ?>" class="<?= $active === $file ? 'on' : '' ?>">
                <?= icon($iconName) ?><?= htmlspecialchars($label) ?>
            </a>
        <?php endforeach; ?>

        <button class="theme" id="theme-toggle" type="button">
            <span class="to-dark" style="justify-content:center"><?= icon('moon') ?>Dark mode</span>
            <span class="to-light" style="justify-content:center"><?= icon('sun') ?>Light mode</span>
        </button>
    </nav>

    <main class="main">
        <div class="top">
            <h2><?= htmlspecialchars($title) ?></h2>
            <form class="sr" action="verify.php" method="get">
                <?= icon('search') ?>
                <input name="q" placeholder="Search a resident..." aria-label="Search a resident">
            </form>
            <a class="btn" href="request_new.php">New request</a>
        </div>
