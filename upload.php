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
$artist = mysqli_fetch_assoc($artists);

$albumSql = 'SELECT albums_id, albums_name FROM albums WHERE artist_id = ? ORDER BY albums_name';
$albumStatement = mysqli_prepare($conn, $albumSql);
mysqli_stmt_bind_param($albumStatement, 's', $artistId);
mysqli_stmt_execute($albumStatement);
$albums = mysqli_stmt_get_result($albumStatement);
$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title'] ?? '');
    $albumId = $_POST['album'] ?? '';
    $song = $_FILES['song'] ?? null;

    if ($title == '' || $albumId == '' || !$song) {
        $message = 'Please complete every field.';
    } elseif ($song['error'] != UPLOAD_ERR_OK) {
        $uploadErrors = [
            UPLOAD_ERR_INI_SIZE => 'The MP3 is larger than the server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'The MP3 is too large.',
            UPLOAD_ERR_PARTIAL => 'The MP3 was only partly uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'Please choose an MP3 file.',
        ];
        $message = $uploadErrors[$song['error']] ?? 'The MP3 upload failed. Please try again.';
    } elseif (strtolower(pathinfo($song['name'], PATHINFO_EXTENSION)) != 'mp3') {
        $message = 'Please choose an MP3 audio file.';
    } else {
        // Use the project's existing folder for uploaded MP3 files.
        $folder = __DIR__ . '/song';

        if (!is_dir($folder) && !mkdir($folder, 0777, true)) {
            $message = 'The song folder could not be created.';
        } elseif (!is_writable($folder)) {
            $message = 'The song folder is not writable. Check the folder permissions.';
        } else {
            $filename = uniqid('song_') . '.mp3';
            $filePath = 'song/' . $filename;
            $destination = $folder . '/' . $filename;

            // Make sure the chosen album belongs to this logged-in artist.
            $checkAlbum = mysqli_prepare($conn, 'SELECT albums_id FROM albums WHERE albums_id = ? AND artist_id = ?');
            mysqli_stmt_bind_param($checkAlbum, 'ss', $albumId, $artistId);
            mysqli_stmt_execute($checkAlbum);

            if (mysqli_num_rows(mysqli_stmt_get_result($checkAlbum)) == 0) {
            $message = 'Please choose one of your own albums.';
            } elseif (move_uploaded_file($song['tmp_name'], $destination)) {
                // Your SQL file does not make song_id auto-increment, so make the next ID.
                $idResult = mysqli_query($conn, 'SELECT COALESCE(MAX(song_id), 0) + 1 AS next_id FROM songs');
                $idRow = mysqli_fetch_assoc($idResult);
                $songId = $idRow['next_id'];
                $duration = 0;

                $sql = 'INSERT INTO songs (song_id, title, artist, albums, file_path, duration)
                        VALUES (?, ?, ?, ?, ?, ?)';
                $statement = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($statement, 'issssi', $songId, $title, $artistId, $albumId, $filePath, $duration);

                if (mysqli_stmt_execute($statement)) {
                    header('Location: index.php');
                    exit;
                }

                unlink($destination);
                $message = 'Database error: ' . mysqli_stmt_error($statement);
            } else {
                $message = 'The MP3 could not be moved into the song folder.';
            }
        }
    }
}

$pagePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$cssFile = ($pagePath ? $pagePath : '') . '/style.css?v=5';
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
            <p class="muted">Upload music as <?= htmlspecialchars($artist['artist_name'] ?? 'your artist account') ?>.</p>
            <a class="button small-button add-album-link" href="add_album.php">+ Create album</a>

            <?php if ($message != '') { ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php } ?>

            <?php if (!$artist || mysqli_num_rows($albums) == 0) { ?>
                <p class="error">You need an album before you can upload a song.</p>
                <a class="button" href="add_album.php">Create my first album</a>
            <?php } else { ?>
                <form method="POST" enctype="multipart/form-data">
                    <label>Song title</label>
                    <input type="text" name="title" placeholder="e.g. Midnight Drive" required>

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
