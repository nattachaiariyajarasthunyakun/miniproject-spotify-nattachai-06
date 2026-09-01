<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';
requireArtist();

// Get artists and albums for the two dropdown menus.
$artistId = $_SESSION['user']['artist_id'];
$artistSql = 'SELECT artist_id, artist_name FROM artist WHERE artist_id = ?';
$artistStatement = mysqli_prepare($conn, $artistSql);
mysqli_stmt_bind_param($artistStatement, 's', $artistId);
mysqli_stmt_execute($artistStatement);
$artists = mysqli_stmt_get_result($artistStatement);

$albumSql = 'SELECT albums_id, albums_name FROM albums WHERE artist_id = ? ORDER BY albums_name';
$albumStatement = mysqli_prepare($conn, $albumSql);
mysqli_stmt_bind_param($albumStatement, 's', $artistId);
mysqli_stmt_execute($albumStatement);
$albums = mysqli_stmt_get_result($albumStatement);
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $selectedArtistId = $_POST['artist'];
    $albumId = $_POST['album'];
    $song = $_FILES['song'];

    if ($title == '' || $selectedArtistId != $artistId || $albumId == '' || $song['error'] != UPLOAD_ERR_OK) {
        $message = 'Please complete every field.';
    } elseif (strtolower(pathinfo($song['name'], PATHINFO_EXTENSION)) != 'mp3') {
        $message = 'Please choose an MP3 audio file.';
    } else {
        $folder = __DIR__ . '/songs';

        if (!is_dir($folder)) {
            mkdir($folder, 0755, true);
        }

        $filename = uniqid('song_') . '.mp3';
        $filePath = 'songs/' . $filename;

        if (move_uploaded_file($song['tmp_name'], $folder . '/' . $filename)) {
            // Your SQL file does not make song_id auto-increment, so make the next ID.
            $idResult = mysqli_query($conn, 'SELECT MAX(song_id) + 1 AS next_id FROM songs');
            $idRow = mysqli_fetch_assoc($idResult);
            $songId = $idRow['next_id'] ?? 1;
            $duration = 0;

            $sql = 'INSERT INTO songs (song_id, title, artist, albums, file_path, duration)
                    VALUES (?, ?, ?, ?, ?, ?)';
            $statement = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($statement, 'issssi', $songId, $title, $selectedArtistId, $albumId, $filePath, $duration);

            if (mysqli_stmt_execute($statement)) {
                header('Location: index.php');
                exit;
            }

            unlink($folder . '/' . $filename);
            $message = 'The song could not be saved to the database.';
        } else {
            $message = 'The file could not be uploaded. Please try again.';
        }
    }
}

$pagePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$cssFile = ($pagePath ? $pagePath : '') . '/style.css?v=4';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add music · SoundSpace</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
</head>
<body class="upload-page">
    <main class="upload-box">
        <a class="logo" href="index.php"><span>♪</span> SoundSpace</a>

        <section class="upload-card">
            <a class="back-link" href="index.php">← Back to library</a>
            <p class="green-text">BUILD YOUR COLLECTION</p>
            <h1>Add a new song</h1>
            <p class="muted">Choose an artist and album from your database.</p>

            <?php if ($message != '') { ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php } ?>

            <?php if (mysqli_num_rows($artists) == 0 || mysqli_num_rows($albums) == 0) { ?>
                <p class="error">Add at least one artist and album in phpMyAdmin before uploading a song.</p>
            <?php } else { ?>
                <form method="POST" enctype="multipart/form-data">
                    <label>Song title</label>
                    <input type="text" name="title" placeholder="e.g. Midnight Drive" required>

                    <label>Artist</label>
                    <select name="artist" required>
                        <option value="">Choose an artist</option>
                        <?php while ($artist = mysqli_fetch_assoc($artists)) { ?>
                            <option value="<?= htmlspecialchars($artist['artist_id']) ?>">
                                <?= htmlspecialchars($artist['artist_name']) ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label>Album</label>
                    <select name="album" required>
                        <option value="">Choose an album</option>
                        <?php while ($album = mysqli_fetch_assoc($albums)) { ?>
                            <option value="<?= htmlspecialchars($album['albums_id']) ?>">
                                <?= htmlspecialchars($album['albums_name']) ?>
                            </option>
                        <?php } ?>
                    </select>

                    <label>Audio file</label>
                    <input type="file" name="song" accept=".mp3,audio/mpeg" required>
                    <small>MP3 only</small>

                    <button class="button" type="submit">Upload song</button>
                </form>
            <?php } ?>
        </section>
    </main>
</body>
</html>
