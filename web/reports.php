<?php
include "db.php";

$title = "Reports";
$active = "reports.php";
include "header.php";   // loaded first because the report code below uses badge()
?>
<style>
    .tabs { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
    .tabs a { padding: 8px 14px; border: 1px solid var(--btn-border, #cfcfcf); border-radius: 99px; background: var(--card, #fff); color: var(--ink, #111); text-decoration: none; }
    .tabs a.on { border-color: var(--ink, #111); background: var(--ink, #111); color: var(--on-ink, #fff); }
    .mb { margin-bottom: 14px; }
</style>
<?php

$tabs = [
    'daily'     => 'Daily fees',
    'pending'   => 'Pending requests',
    'unpaid'    => 'Unpaid balances',
    'released'  => 'Released documents',
    'residents' => 'Residents per purok',
];
$report = $_GET['r'] ?? 'daily';
if (!isset($tabs[$report])) {
    $report = 'daily';
}

// Optional date range (used by the daily and released reports).
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';
$fromSql = $from !== '' ? $from : '2000-01-01';
$toSql = $to !== '' ? $to : '2100-01-01';

$stats = [];      // summary tiles: label => value
$sections = [];   // tables: title, cols, rows (rows are already escaped HTML)

if ($report === 'daily') {
    $stmt = $conn->prepare("SELECT payment_date, COUNT(*) AS payments, SUM(amount_paid) AS total
        FROM Payment WHERE payment_date BETWEEN ? AND ?
        GROUP BY payment_date ORDER BY payment_date DESC");
    $stmt->bind_param("ss", $fromSql, $toSql);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    $sum = 0;
    $count = 0;
    while ($r = $res->fetch_assoc()) {
        $rows[] = [h($r['payment_date']), $r['payments'], number_format($r['total'], 2)];
        $sum += $r['total'];
        $count += $r['payments'];
    }
    $stats = ['Total collected' => number_format($sum, 2), 'Payments' => number_format($count), 'Days with payments' => count($rows)];
    $sections[] = ['title' => 'Daily sales report', 'cols' => ['Date', 'Payments', 'Total collected'], 'rows' => $rows];

} elseif ($report === 'pending') {
    $res = $conn->query("SELECT request_id, resident_name, type_name, request_date, status, balance
        FROM vw_request_balances WHERE status <> 'Released' ORDER BY request_date, request_id");
    $rows = [];
    $pendingCount = 0;
    $processingCount = 0;
    while ($r = $res->fetch_assoc()) {
        $rows[] = [$r['request_id'], h($r['resident_name']), h($r['type_name']), h($r['request_date']),
                   badge($r['status']), number_format($r['balance'], 2)];
        if ($r['status'] === 'Pending') $pendingCount++; else $processingCount++;
    }
    $stats = ['Open requests' => count($rows), 'Pending' => $pendingCount, 'Processing' => $processingCount];
    $sections[] = ['title' => 'Pending requests report', 'cols' => ['Req', 'Resident', 'Document', 'Request date', 'Status', 'Balance'], 'rows' => $rows];

} elseif ($report === 'unpaid') {
    $res = $conn->query("SELECT request_id, resident_name, type_name, status, fee_charged, total_paid, balance
        FROM vw_request_balances WHERE balance > 0 ORDER BY balance DESC, request_id");
    $rows = [];
    $outstanding = 0;
    while ($r = $res->fetch_assoc()) {
        $rows[] = [$r['request_id'], h($r['resident_name']), h($r['type_name']), badge($r['status']),
                   number_format($r['fee_charged'], 2), number_format($r['total_paid'], 2), '<b>' . number_format($r['balance'], 2) . '</b>'];
        $outstanding += $r['balance'];
    }
    $stats = ['Requests with balance' => count($rows), 'Total outstanding' => number_format($outstanding, 2)];
    $sections[] = ['title' => 'Unpaid balances report', 'cols' => ['Req', 'Resident', 'Document', 'Status', 'Fee', 'Paid', 'Balance'], 'rows' => $rows];

} elseif ($report === 'released') {
    $stmt = $conn->prepare("SELECT type_name, COUNT(*) AS released, SUM(total_paid) AS collected
        FROM vw_request_balances WHERE status = 'Released' AND release_date BETWEEN ? AND ?
        GROUP BY type_name ORDER BY type_name");
    $stmt->bind_param("ss", $fromSql, $toSql);
    $stmt->execute();
    $res = $stmt->get_result();
    $byType = [];
    $total = 0;
    while ($r = $res->fetch_assoc()) {
        $byType[] = [h($r['type_name']), $r['released'], number_format($r['collected'], 2)];
        $total += $r['released'];
    }

    $stmt = $conn->prepare("SELECT request_id, resident_name, type_name, request_date, release_date, fee_charged
        FROM vw_request_balances WHERE status = 'Released' AND release_date BETWEEN ? AND ?
        ORDER BY release_date DESC, request_id DESC");
    $stmt->bind_param("ss", $fromSql, $toSql);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) {
        $rows[] = [$r['request_id'], h($r['resident_name']), h($r['type_name']), h($r['request_date']),
                   h($r['release_date']), number_format($r['fee_charged'], 2)];
    }
    $stats = ['Documents released' => $total];
    $sections[] = ['title' => 'Released documents by type', 'cols' => ['Document', 'Released', 'Fees collected'], 'rows' => $byType];
    $sections[] = ['title' => 'Released documents list', 'cols' => ['Req', 'Resident', 'Document', 'Requested', 'Released', 'Fee'], 'rows' => $rows];

} else {   // residents per purok and household (Active residents only)
    $res = $conn->query("SELECT p.purok_name, COUNT(DISTINCT h.household_id) AS households, COUNT(r.resident_id) AS residents
        FROM Purok p
        LEFT JOIN Household h ON h.purok_id = p.purok_id
        LEFT JOIN Resident r ON r.household_id = h.household_id AND r.status = 'Active'
        GROUP BY p.purok_id, p.purok_name ORDER BY p.purok_id");
    $byPurok = [];
    $totalActive = 0;
    while ($r = $res->fetch_assoc()) {
        $byPurok[] = [h($r['purok_name']), $r['households'], $r['residents']];
        $totalActive += $r['residents'];
    }

    $res = $conn->query("SELECT p.purok_name, h.house_number, h.street, COUNT(r.resident_id) AS residents
        FROM Purok p
        JOIN Household h ON h.purok_id = p.purok_id
        LEFT JOIN Resident r ON r.household_id = h.household_id AND r.status = 'Active'
        GROUP BY p.purok_id, p.purok_name, h.household_id, h.house_number, h.street
        ORDER BY p.purok_id, h.street, h.house_number");
    $byHousehold = [];
    while ($r = $res->fetch_assoc()) {
        $byHousehold[] = [h($r['purok_name']), h($r['house_number'] . ' ' . $r['street']), $r['residents']];
    }

    $notActive = $conn->query("SELECT COUNT(*) FROM Resident WHERE status <> 'Active'")->fetch_row()[0];
    $stats = ['Active residents' => number_format($totalActive), 'Not counted (not Active)' => number_format($notActive)];
    $sections[] = ['title' => 'Residents per purok', 'cols' => ['Purok', 'Households', 'Active residents'], 'rows' => $byPurok];
    $sections[] = ['title' => 'Residents per household', 'cols' => ['Purok', 'Household', 'Active residents'], 'rows' => $byHousehold];
}

?>
<div class="tabs no-print">
    <?php foreach ($tabs as $key => $label): ?>
        <a href="reports.php?r=<?= $key ?>" class="<?= $report === $key ? 'on' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</div>

<?php if ($report === 'daily' || $report === 'released'): ?>
    <form class="filters no-print" method="get" action="reports.php" style="max-width:560px">
        <input type="hidden" name="r" value="<?= $report ?>">
        <input class="f" type="date" name="from" value="<?= h($from) ?>" aria-label="From date">
        <input class="f" type="date" name="to" value="<?= h($to) ?>" aria-label="To date">
        <button class="btn">Apply</button>
    </form>
<?php endif; ?>

<div class="card mb">
    <div class="g">
        <?php foreach ($stats as $label => $value): ?>
            <div class="stat"><span><?= $label ?></span><b><?= $value ?></b></div>
        <?php endforeach; ?>
    </div>
</div>

<?php foreach ($sections as $section): ?>
    <div class="card mb">
        <h3><?= h($section['title']) ?></h3>
        <?php if (count($section['rows']) === 0): ?>
            <p class="sub">No records.</p>
        <?php else: ?>
            <div class="tw">
                <table>
                    <tr>
                        <?php foreach ($section['cols'] as $col): ?><th><?= $col ?></th><?php endforeach; ?>
                    </tr>
                    <?php foreach ($section['rows'] as $row): ?>
                        <tr>
                            <?php foreach ($row as $cell): ?><td><?= $cell ?></td><?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<button class="btn s no-print" onclick="window.print()">Print report</button>
<?php include "footer.php"; ?>