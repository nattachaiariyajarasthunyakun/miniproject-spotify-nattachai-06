<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';

requireUser();

$message = '';
$userId = $_SESSION['user']['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);

    if ($title == '') {
        $message = 'Please enter a playlist name.';
    } else {
        $idResult = mysqli_query($conn, 'SELECT COALESCE(MAX(playlist_id), 0) + 1 AS next_id FROM playlist');
        $playlistId = mysqli_fetch_assoc($idResult)['next_id'];
        $image = '';

        $sql = 'INSERT INTO playlist (playlist_id, title, profile_img_url, user_id) VALUES (?, ?, ?, ?)';
        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 'issi', $playlistId, $title, $image, $userId);

        if (mysqli_stmt_execute($statement)) {
            header('Location: playlist.php');
            exit;
        }

        $message = 'Could not create the playlist.';
    }
}

$sql = 'SELECT * FROM playlist WHERE user_id = ? ORDER BY playlist_id DESC';
$statement = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($statement, 'i', $userId);
mysqli_stmt_execute($statement);
$playlists = mysqli_stmt_get_result($statement);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My playlists · SoundSpace</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body class="upload-page">
    <main class="upload-box">
        <a class="logo" href="index.php"><span>♪</span> SoundSpace</a>
        <section class="upload-card">
            <a class="back-link" href="index.php">← Back to library</a>
            <p class="green-text">YOUR PLAYLISTS</p>
            <h1>Create a playlist</h1>

            <?php if ($message != '') { ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php } ?>

            <form method="POST" class="small-form">
                <label>Playlist name</label>
                <input type="text" name="title" placeholder="e.g. Study music" required>
                <button class="button" type="submit">Create playlist</button>
            </form>

            <div class="playlist-list">
                <?php if (mysqli_num_rows($playlists) == 0) { ?>
                    <p class="muted">You have not created a playlist yet.</p>
                <?php } else { ?>
                    <?php while ($playlist = mysqli_fetch_assoc($playlists)) { ?>
                        <div class="playlist-item"><span>♫</span><?= htmlspecialchars($playlist['title']) ?></div>
                    <?php } ?>
                <?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
