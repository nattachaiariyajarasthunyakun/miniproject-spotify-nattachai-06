<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';
requireArtist();

$artistId = $_SESSION['user']['artist_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $albumsName = trim($_POST['albums_name']);
    $releaseDate = $_POST['release_date'];
    $totalTracks = (int) $_POST['total_tracks'];
    $coverArtUrl = trim($_POST['cover_art_url']);

    if ($albumsName == '' || $releaseDate == '' || $totalTracks < 1) {
        $message = 'Please complete every required field.';
    } else {
        $albumsId = 'album_' . uniqid();

        $sql = 'INSERT INTO albums (albums_id, albums_name, release_date, total_tracks, cover_art_url, artist_id)
                VALUES (?, ?, ?, ?, ?, ?)';
        $statement = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($statement, 'sssiss', $albumsId, $albumsName, $releaseDate, $totalTracks, $coverArtUrl, $artistId);

        if (mysqli_stmt_execute($statement)) {
            header('Location: upload.php');
            exit;
        }

        $message = 'Could not save the album. Please try again.';
    }
}

$albumSql = 'SELECT * FROM albums WHERE artist_id = ? ORDER BY release_date DESC';
$albumStatement = mysqli_prepare($conn, $albumSql);
mysqli_stmt_bind_param($albumStatement, 's', $artistId);
mysqli_stmt_execute($albumStatement);
$albums = mysqli_stmt_get_result($albumStatement);

$pagePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$cssFile = ($pagePath ? $pagePath : '') . '/style.css?v=5';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add album · SoundSpace</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
</head>
<body class="upload-page">
    <main class="upload-box">
        <a class="logo" href="index.php"><span>♪</span> SoundSpace</a>

        <section class="upload-card">
            <a class="back-link" href="upload.php">← Back to upload</a>
            <p class="green-text">BUILD YOUR COLLECTION</p>
            <h1>Add an album</h1>
            <p class="muted">Create an album first, then upload songs into it.</p>

            <?php if ($message != '') { ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php } ?>

            <form method="POST">
                <label>Album name</label>
                <input type="text" name="albums_name" placeholder="e.g. Late Night Sessions" required>

                <label>Release date</label>
                <input type="date" name="release_date" required>

                <label>Total tracks</label>
                <input type="number" name="total_tracks" min="1" value="1" required>

                <label>Cover art URL (optional)</label>
                <input type="url" name="cover_art_url" placeholder="https://...">

                <button class="button" type="submit">Create album</button>
            </form>

            <div class="playlist-list">
                <?php if (mysqli_num_rows($albums) == 0) { ?>
                    <p class="muted">You haven't created an album yet.</p>
                <?php } else { ?>
                    <?php while ($album = mysqli_fetch_assoc($albums)) { ?>
                        <div class="playlist-item">
                            <span>♫</span><?= htmlspecialchars($album['albums_name']) ?>
                            <small style="display:block;color:#777;margin-top:4px">
                                <?= htmlspecialchars($album['release_date']) ?> · <?= (int) $album['total_tracks'] ?> tracks
                            </small>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>
        </section>
    </main>
</body>
</html>
