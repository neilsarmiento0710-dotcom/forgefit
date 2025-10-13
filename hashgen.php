<?php
// CHANGE THIS to the actual password you are trying to use for the trainer
$plain_password = 'trainer1234'; 

// Generate the new, correctly formatted hash
$new_hash = password_hash($plain_password, PASSWORD_DEFAULT);

echo "New Hashed Password: " . $new_hash . "\n";
?>