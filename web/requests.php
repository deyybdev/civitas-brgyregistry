<?php
$title = "Requests & Payments";
$active = "requests.php";
include "header.php";

// Sample data. Later: read from the view vw_request_balances.
$requests = [
    [101, 'Reyes, Ana', 'Barangay Clearance', 'Pending', 50, 0],
    [102, 'Santos, Mark', 'Cert. of Residency', 'Processing', 50, 20],
    [104, 'Cruz, Liza', 'Barangay Clearance', 'Released', 50, 50],
];
?>
<div class="card">
    <div class="tw">
        <table>
            <tr>
                <th>Req</th><th>Resident</th><th>Document</th><th>Status</th><th>Fee</th><th>Paid</th><th>Balance</th>
            </tr>
            <?php foreach ($requests as [$id, $name, $doc, $status, $fee, $paid]): ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars($name) ?></td>
                    <td><?= htmlspecialchars($doc) ?></td>
                    <td><?= badge($status) ?></td>
                    <td><?= number_format($fee, 2) ?></td>
                    <td><?= number_format($paid, 2) ?></td>
                    <td><b><?= number_format($fee - $paid, 2) ?></b></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php include "footer.php"; ?>
