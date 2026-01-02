<?php
require __DIR__ . '/app/bootstrap.php';

$username = 'admin';
$newPassword = 'Andrada_1968!';

$db = db();
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $db->prepare("UPDATE users SET passhash=:p WHERE username=:u");
$stmt->bindValue(':p', $hash, SQLITE3_TEXT);
$stmt->bindValue(':u', $username, SQLITE3_TEXT);
$stmt->execute();

if ($db->changes() > 0) {
    echo "<h1>SUCCESS</h1>Password for user <b>$username</b> has been updated to: <b>$newPassword</b>";
} else {
    echo "<h1>ERROR</h1>User <b>$username</b> not found or password already set.";
}
