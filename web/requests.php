<?php
include "db.php";

$message = "";
$messageType = "ok";
$selectedRequest = (int) ($_GET['pay'] ?? 0);

// Payment state shown as a badge.
function payState($row)
{
    if ($row['fee_charged'] == 0) return 'No fee';
    if ($row['balance'] <= 0) return 'Paid';
    if ($row['total_paid'] > 0) return 'Partial';
    return 'Unpaid';
}

// ----- Record a payment or update a status -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['request_id'] ?? 0);
    try {
        if ($action === 'pay') {
            $selectedRequest = $id;
            $amount = (float) $_POST['amount'];
            $row = callProcedure($conn, "CALL sp_record_payment(?, ?, ?)", "ids", [$id, $amount, $_POST['payment_date']]);
            $message = "Payment recorded for request #" . $id . ". New balance: " . number_format($row['balance'], 2);
            $selectedRequest = 0;
        } elseif ($action === 'status') {
            $newStatus = $_POST['new_status'];
            $stmt = $conn->prepare("UPDATE DocumentRequest SET status = ? WHERE request_id = ? AND status <> 'Released'");
            $stmt->bind_param("si", $newStatus, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $message = "Request #" . $id . " is now " . $newStatus . ".";
            } else {
                $message = "Request not found or already Released.";
                $messageType = "err";
            }
        }
    } catch (mysqli_sql_exception $e) {
        $message = friendlyError($e);   // for example: Payment exceeds remaining balance
        $messageType = "err";
    }
}

// ----- Request list (reads the view) -----
$q = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$like = "%" . $q . "%";
$stmt = $conn->prepare("SELECT * FROM vw_request_balances
    WHERE resident_name LIKE ? AND (? = '' OR status = ?)
    ORDER BY request_date DESC, request_id DESC");
$stmt->bind_param("sss", $like, $statusFilter, $statusFilter);
$stmt->execute();
$requests = $stmt->get_result();

$withBalance = $conn->query("SELECT request_id, resident_name, type_name, balance
    FROM vw_request_balances WHERE balance > 0 ORDER BY request_id")->fetch_all(MYSQLI_ASSOC);
$unreleased = $conn->query("SELECT request_id, resident_name, type_name, status
    FROM vw_request_balances WHERE status <> 'Released' ORDER BY request_id")->fetch_all(MYSQLI_ASSOC);

$title = "Requests & Payments";
$active = "requests.php";
include "header.php";
?>
<?php if ($message): ?>
    <div class="note <?= $messageType ?> msg"><?= h($message) ?></div>
<?php endif; ?>

<div class="row">

    <div class="card" style="flex:2; min-width:480px">
        <form class="filters" method="get" action="requests.php">
            <input class="f" name="q" value="<?= h($q) ?>" placeholder="Search by resident" aria-label="Search by resident">
            <select name="status" aria-label="Status">
                <option value="">All statuses</option>
                <?php foreach (['Pending', 'Processing', 'Released'] as $s): ?>
                    <option<?= sel($statusFilter, $s) ?>><?= $s ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn">Filter</button>
        </form>
        <p class="count"><?= $requests->num_rows ?> requests</p>

        <div class="tw">
            <table>
                <tr>
                    <th>Req</th><th>Resident</th><th>Document</th><th>Status</th><th>Fee</th><th>Paid</th><th>Balance</th><th>Payment</th><th></th>
                </tr>
                <?php while ($r = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['request_id'] ?></td>
                        <td><?= h($r['resident_name']) ?></td>
                        <td><?= h($r['type_name']) ?></td>
                        <td><?= badge($r['status']) ?></td>
                        <td><?= number_format($r['fee_charged'], 2) ?></td>
                        <td><?= number_format($r['total_paid'], 2) ?></td>
                        <td><b><?= number_format($r['balance'], 2) ?></b></td>
                        <td><?= badge(payState($r)) ?></td>
                        <td>
                            <?php if ($r['balance'] > 0): ?>
                                <a class="btn s" href="requests.php?pay=<?= $r['request_id'] ?>">Pay</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="col" style="flex:1; min-width:290px">

        <div class="card">
            <h3>Record payment</h3>
            <form method="post" action="requests.php">
                <input type="hidden" name="action" value="pay">

                <label for="pay_request">Request with balance</label>
                <select id="pay_request" name="request_id" required data-search>
                    <option value="">Select request</option>
                    <?php foreach ($withBalance as $b): ?>
                        <option value="<?= $b['request_id'] ?>"<?= sel($selectedRequest, $b['request_id']) ?>>
                            <?= h('#' . $b['request_id'] . ' ' . $b['resident_name'] . ' - ' . $b['type_name'] . ' (balance ' . number_format($b['balance'], 2) . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="amount">Amount</label>
                <input class="f" id="amount" name="amount" type="number" step="0.01" required>

                <label for="payment_date">Payment date</label>
                <input class="f" id="payment_date" name="payment_date" type="date" value="<?= date('Y-m-d') ?>" required>

                <button class="btn" style="margin-top:16px; width:100%">Record payment</button>
            </form>
        </div>

        <div class="card">
            <h3>Update status</h3>
            <form method="post" action="requests.php">
                <input type="hidden" name="action" value="status">

                <label for="status_request">Request not yet Released</label>
                <select id="status_request" name="request_id" required data-search>
                    <option value="">Select request</option>
                    <?php foreach ($unreleased as $u): ?>
                        <option value="<?= $u['request_id'] ?>">
                            <?= h('#' . $u['request_id'] . ' ' . $u['resident_name'] . ' - ' . $u['type_name'] . ' (' . $u['status'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <label for="new_status">New status</label>
                <select id="new_status" name="new_status">
                    <option>Processing</option>
                    <option>Released</option>
                </select>

                <button class="btn" style="margin-top:16px; width:100%">Update status</button>
            </form>
        </div>

    </div>
</div>
<?php include "footer.php"; ?>