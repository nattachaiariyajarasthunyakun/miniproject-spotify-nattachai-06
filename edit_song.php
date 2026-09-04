<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';

requireArtist();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $songId = (int) $_POST['song_id'];
    $artistId = $_SESSION['user']['artist_id'];
    $newTitle = trim($_POST['title']);

    if ($newTitle !== '') {
        // An artist can rename only a song connected to their own artist ID.
        $sql = 'UPDATE songs SET title = ? WHERE song_id = ? AND artist = ?';
        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 'sis', $newTitle, $songId, $artistId);
        mysqli_stmt_execute($statement);
    }
}

header('Location: index.php');
exit;
