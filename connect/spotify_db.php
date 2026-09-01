<?php
$conn = mysqli_connect('localhost', 'root', '', 'w5-spotify-db');
if (!$conn) {
    die('Database connection failed. Check connect/spotify_db.php and your MySQL service.');
}
mysqli_set_charset($conn, 'utf8mb4');
