<?php
// Temporary file: shows which db.php is loaded and which helpers it has. Delete after use.
include "db.php";

echo "<h3>Files PHP loaded</h3>";
foreach (get_included_files() as $file) {
    echo htmlspecialchars($file) . "<br>";
}

echo "<h3>Helpers in db.php</h3>";
foreach (['h', 'sel', 'properCase', 'friendlyError', 'callProcedure'] as $name) {
    echo $name . ": " . (function_exists($name) ? "OK" : "MISSING") . "<br>";
}
