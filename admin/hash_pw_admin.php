<?php
//Hash password admin untuk di-copy ke database
$password_polos = "admin123";
$password_hashed = password_hash($password_polos, PASSWORD_DEFAULT);

echo "Copy hash ini ke database:<br>";
echo "<b>" . $password_hashed . "</b>";
?>