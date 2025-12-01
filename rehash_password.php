<?php
require "db.php";

$username = "admin";  // change if needed

// get the existing plain password
$q = $conn->prepare("SELECT password FROM users WHERE username=?");
$q->bind_param("s", $username);
$q->execute();
$r = $q->get_result()->fetch_assoc();

$plain = $r['password']; // this is your un-hashed value, e.g. "1234"

// re-hash it
$hashed = password_hash($plain, PASSWORD_DEFAULT);

// update DB with hashed password
$u = $conn->prepare("UPDATE users SET password=? WHERE username=?");
$u->bind_param("ss", $hashed, $username);
$u->execute();

echo "Password for '$username' has been hashed: $hashed";
?>
