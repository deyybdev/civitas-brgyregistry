<?php
include "db.php";

$message = "";
$messageType = "ok";
$form = ['household_id' => 0, 'household_name' => '', 'purok_id' => '', 'house_number' => '', 'street' => ''];

// ----- Save or delete (form submitted) -----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'save') {
            $id = (int) ($_POST['household_id'] ?? 0);
            $name = properCase($_POST['household_name']);
            $purok = (int) $_POST['purok_id'];
            $number = trim($_POST['house_number']);
            $street = properCase($_POST['street']);

            if ($id > 0) {
                $stmt = $conn->prepare("UPDATE Household SET household_name = ?, purok_id = ?, house_number = ?, street = ?
                    WHERE household_id = ?");
                $stmt->bind_param("sissi", $name, $purok, $number, $street, $id);
                $stmt->execute();
                $message = "Household updated.";
            } else {
                $stmt = $conn->prepare("INSERT INTO Household (household_name, purok_id, house_number, street)
                    VALUES (?, ?, ?, ?)");
                $stmt->bind_param("siss", $name, $purok, $number, $street);
                $stmt->execute();
                $message = "Household saved.";
            }
        } elseif ($action === 'delete') {
            $id = (int) $_POST['household_id'];
            $stmt = $conn->prepare("DELETE FROM Household WHERE household_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $message = "Household deleted.";
        }
    } catch (mysqli_sql_exception $e) {
        $message = friendlyError($e);
        $messageType = "err";
        if ($action === 'delete' && $e->getCode() == 1451) {
            $message = "Cannot delete: this household still has residents.";
        }
        if ($action === 'save') {   // keep what the user typed
            $form = ['household_id' => $id, 'household_name' => $name, 'purok_id' => $purok,
                     'house_number' => $number, 'street' => $street];
        }
    }
}

// ----- Load a household into the form (Edit link) -----
if ((int) $form['household_id'] === 0 && isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $stmt = $conn->prepare("SELECT household_id, household_name, purok_id, house_number, street
        FROM Household WHERE household_id = ?");
    $stmt->bind_param("i", $editId);
    $stmt->execute();
    $found = $stmt->get_result()->fetch_assoc();
    if ($found) {
        $form = $found;
    }
}

// ----- Members of one household (View link) -----
$viewHousehold = null;
$members = [];
$viewId = (int) ($_GET['view'] ?? 0);
if ($viewId > 0) {
    $stmt = $conn->prepare("SELECT h.household_id, h.household_name, h.house_number, h.street, p.purok_name
        FROM Household h JOIN Purok p ON p.purok_id = h.purok_id WHERE h.household_id = ?");
    $stmt->bind_param("i", $viewId);
    $stmt->execute();
    $viewHousehold = $stmt->get_result()->fetch_assoc();

    if ($viewHousehold) {
        $stmt = $conn->prepare("SELECT resident_id, last_name, first_name, middle_name, birth_date, status
            FROM Resident WHERE household_id = ? ORDER BY birth_date, resident_id");
        $stmt->bind_param("i", $viewId);
        $stmt->execute();
        $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

// ----- Household list with filters -----
$q = trim($_GET['q'] ?? '');
$purokFilter = (int) ($_GET['purok'] ?? 0);
$like = "%" . $q . "%";

$stmt = $conn->prepare("SELECT h.household_id, h.household_name, p.purok_name, h.house_number, h.street,
        COUNT(r.resident_id) AS members, COALESCE(SUM(r.status = 'Active'), 0) AS active_members
    FROM Household h
    JOIN Purok p ON p.purok_id = h.purok_id
    LEFT JOIN Resident r ON r.household_id = h.household_id
    WHERE (h.household_name LIKE ? OR h.street LIKE ?) AND (? = 0 OR p.purok_id = ?)
    GROUP BY h.household_id, h.household_name, p.purok_id, p.purok_name, h.house_number, h.street
    ORDER BY p.purok_id, h.household_name");
$stmt->bind_param("ssii", $like, $like, $purokFilter, $purokFilter);
$stmt->execute();
$households = $stmt->get_result();

$puroks = $conn->query("SELECT purok_id, purok_name FROM Purok ORDER BY purok_id")->fetch_all(MYSQLI_ASSOC);

$title = "Households";
$active = "households.php";
include "header.php";
?>
<?php if ($message): ?>
    <div class="note <?= $messageType ?> msg"><?= h($message) ?></div>
<?php endif; ?>

<div class="row">

    <div class="card" style="flex:2; min-width:440px">
        <form class="filters" method="get" action="households.php">
            <input class="f" name="q" value="<?= h($q) ?>" placeholder="Search family name or street" aria-label="Search households">
            <select name="purok" aria-label="Purok" data-search>
                <option value="0">All puroks</option>
                <?php foreach ($puroks as $p): ?>
                    <option value="<?= $p['purok_id'] ?>"<?= sel($purokFilter, $p['purok_id']) ?>><?= h($p['purok_name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn">Filter</button>
        </form>
        <p class="count"><?= $households->num_rows ?> households</p>

        <div class="tw">
            <table>
                <tr>
                    <th>ID</th><th>Household</th><th>Purok</th><th>Address</th><th>Members</th><th></th>
                </tr>
                <?php while ($r = $households->fetch_assoc()): ?>
                    <tr>
                        <td><?= $r['household_id'] ?></td>
                        <td><a href="households.php?view=<?= $r['household_id'] ?>" style="color:inherit; font-weight:500"><?= h($r['household_name']) ?></a></td>
                        <td><?= h($r['purok_name']) ?></td>
                        <td><?= h($r['house_number'] . ' ' . $r['street']) ?></td>
                        <td><?= $r['active_members'] ?> active / <?= $r['members'] ?> total</td>
                        <td>
                            <div class="actions">
                                <a class="btn s" href="households.php?view=<?= $r['household_id'] ?>">View</a>
                                <a class="btn s" href="households.php?edit=<?= $r['household_id'] ?>">Edit</a>
                                <form method="post" action="households.php" onsubmit="return confirm('Delete this household?')">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="household_id" value="<?= $r['household_id'] ?>">
                                    <button class="btn s danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </div>
    </div>

    <div class="col" style="flex:1; min-width:290px">

        <?php if ($viewHousehold): ?>
            <div class="card">
                <h3><?= h($viewHousehold['household_name']) ?></h3>
                <p class="sub"><?= h($viewHousehold['purok_name'] . ', ' . $viewHousehold['house_number'] . ' ' . $viewHousehold['street']) ?></p>
                <?php if (count($members) === 0): ?>
                    <p class="sub">No residents in this household yet.</p>
                <?php endif; ?>
                <?php foreach ($members as $m): ?>
                    <div class="li">
                        <span class="av"><?= h(substr($m['first_name'], 0, 1) . substr($m['last_name'], 0, 1)) ?></span>
                        <div>
                            <a href="resident.php?id=<?= $m['resident_id'] ?>" style="color:inherit; font-weight:500"><?= h($m['last_name'] . ', ' . $m['first_name'] . ' ' . $m['middle_name']) ?></a>
                            <small><?= h($m['birth_date']) ?></small>
                        </div>
                        <?= badge($m['status']) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h3><?= (int) $form['household_id'] > 0 ? 'Edit household #' . (int) $form['household_id'] : 'Add household' ?></h3>
            <form method="post" action="households.php">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="household_id" value="<?= (int) $form['household_id'] ?>">

                <label for="household_name">Household name (family name)</label>
                <input class="f" id="household_name" name="household_name" value="<?= h($form['household_name']) ?>" placeholder="For example: Asaytuno Family" required>

                <label for="purok_id">Purok</label>
                <select id="purok_id" name="purok_id" required data-search>
                    <option value="">Select purok</option>
                    <?php foreach ($puroks as $p): ?>
                        <option value="<?= $p['purok_id'] ?>"<?= sel($form['purok_id'], $p['purok_id']) ?>><?= h($p['purok_name']) ?></option>
                    <?php endforeach; ?>
                </select>

                <label for="house_number">House number</label>
                <input class="f" id="house_number" name="house_number" value="<?= h($form['house_number']) ?>" required>

                <label for="street">Street</label>
                <input class="f" id="street" name="street" value="<?= h($form['street']) ?>" required>

                <div style="display:flex; gap:10px; margin-top:16px">
                    <button class="btn">Save household</button>
                    <?php if ((int) $form['household_id'] > 0): ?>
                        <a class="btn s" style="height:38px" href="households.php">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

    </div>
</div>
<?php include "footer.php"; ?>