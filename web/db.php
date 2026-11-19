<?php
// Database connection and small helpers. Every page starts with: include "db.php";

// Turn database errors into exceptions so pages can catch and show them.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // host, username, password, database name (XAMPP default: root with no password)
    $conn = new mysqli("localhost", "root", "", "civitas_brgyregistry");
    $conn->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . htmlspecialchars($e->getMessage()));
}

// Safe printing of database values inside HTML.
function h($text)
{
    return htmlspecialchars((string) $text);
}

// Cleans a name: trims, removes extra spaces, and uses Capital Case ("MARK dave" becomes "Mark Dave").
function properCase($text)
{
    $text = preg_replace('/\s+/u', ' ', trim($text));
    return mb_convert_case($text, MB_CASE_TITLE, "UTF-8");
}

// Returns " selected" when two values match (for <option> tags).
function sel($a, $b)
{
    return (string) $a === (string) $b ? ' selected' : '';
}

// Turns a database error into a short message for the page.
function friendlyError(mysqli_sql_exception $e)
{
    $code = $e->getCode();
    if ($code == 1062) return "That record already exists (duplicate).";
    if ($code == 1451) return "Cannot delete: other records depend on it.";
    if ($code == 1452) return "The selected record does not exist.";
    if ($code == 3819 || $code == 4025) return "A value is not allowed by the database rules.";
    return $e->getMessage();   // messages from our stored procedures and triggers
}

// Calls a stored procedure, returns the first row it selects, and clears leftover results.
function callProcedure($conn, $sql, $types, $params)
{
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    if ($result) {
        $result->free();
    }
    $stmt->close();
    while ($conn->more_results()) {
        $conn->next_result();
    }
    return $row;
}
