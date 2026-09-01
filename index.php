<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';


$songs = [];
$sql = "SELECT songs.*, artist.artist_name, albums.albums_name
        FROM songs
        JOIN artist ON songs.artist = artist.artist_id
        JOIN albums ON songs.albums = albums.albums_id
        ORDER BY songs.song_id DESC";
$result = mysqli_query($conn, $sql);
$pagePath = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$cssFile = ($pagePath ? $pagePath : '') . '/style.css?v=4';

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $songs[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SoundSpace</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($cssFile) ?>">
</head>
<body>
    <aside class="sidebar">
        <a class="logo" href="index.php">
            <span>♪</span> SoundSpace
        </a>

        <nav>
            <a class="active" href="index.php">⌂ Home</a>
            <a href="#library">☷ Your Library</a>
            <?php if (isLoggedIn() && $_SESSION['user']['role'] == 'user') { ?>
                <a href="playlist.php">☷ My Playlists</a>
            <?php } ?>
        </nav>

        <div class="side-message">
            <strong>Your music in one place.</strong>
            <?php if (isLoggedIn()) { ?>
                <p>Hi, <?= htmlspecialchars($_SESSION['user']['name']) ?>.</p>
                <a class="back-link" href="logout.php">Log out</a>
            <?php } else { ?>
                <p>Log in as a user or artist.</p>
                <a class="button small-button" href="login.php">Log in</a>
            <?php } ?>
        </div>
    </aside>

    <main>
        <header>
            <div class="arrows"><span>‹</span><span>›</span></div>
            <?php if (isArtist()) { ?>
                <a class="button outline-button" href="upload.php">+ Add music</a>
            <?php } elseif (!isLoggedIn()) { ?>
                <a class="button outline-button" href="login.php">Log in</a>
            <?php } ?>
        </header>

        <section class="hero">
            <p class="green-text">YOUR PERSONAL LIBRARY</p>
            <h1>Good music,<br><em>right here.</em></h1>
            <p class="muted">Listen to the tracks you love without the clutter.</p>
        </section>

        <section class="library" id="library">
            <div class="library-title">
                <div>
                    <h2>My Songs</h2>
                    <p class="muted">
                        <?= count($songs) ?> <?= count($songs) == 1 ? 'song' : 'songs' ?> in your library
                    </p>
                </div>

                <input id="search" type="search" placeholder="Search songs or artists">
            </div>

            <?php if (!$result) { ?>
                <p class="error">Could not load the music library.</p>
            <?php } elseif (count($songs) == 0) { ?>
                <div class="empty-state">
                    <div class="music-circle">♫</div>
                    <h3>Your library is waiting</h3>
                    <p>Upload your first MP3 to start listening.</p>
                    <a class="button" href="upload.php">Upload a song</a>
                </div>
            <?php } else { ?>
                <div class="song-list">
                    <div class="song header-row">
                        <span>#</span>
                        <span>Title</span>
                        <span>Artist</span>
                        <span>Play</span>
                        <?php if (isArtist()) { ?><span>Delete</span><?php } ?>
                    </div>

                    <?php foreach ($songs as $number => $song) { ?>
                        <article class="song song-row" data-search="<?= htmlspecialchars(strtolower($song['title'] . ' ' . $song['artist_name'])) ?>">
                            <span class="number"><?= $number + 1 ?></span>

                            <div class="title">
                                <span class="cover">♪</span>
                                <strong><?= htmlspecialchars($song['title']) ?></strong>
                            </div>

                            <span class="artist">
                                <?= htmlspecialchars($song['artist_name']) ?>
                                <small><?= htmlspecialchars($song['albums_name']) ?></small>
                            </span>

                            <audio controls preload="metadata">
                                <source src="<?= htmlspecialchars($song['file_path']) ?>" type="audio/mpeg">
                            </audio>

                            <?php if (isArtist() && $_SESSION['user']['artist_id'] == $song['artist']) { ?>
                                <form method="POST" action="delete_song.php" class="delete-form">
                                    <input type="hidden" name="song_id" value="<?= $song['song_id'] ?>">
                                    <button type="submit" class="delete-button">Delete</button>
                                </form>
                            <?php } elseif (isArtist()) { ?>
                                <span></span>
                            <?php } ?>
                        </article>
                    <?php } ?>
                </div>

                <p id="no-results" class="no-results" hidden>No songs match your search.</p>
            <?php } ?>
        </section>
    </main>

    <script>
        const search = document.getElementById('search');
        const songRows = document.querySelectorAll('.song-row');
        const noResults = document.getElementById('no-results');

        if (search) {
            search.addEventListener('input', function () {
                let visibleSongs = 0;
                let searchText = search.value.toLowerCase();

                songRows.forEach(function (song) {
                    let matches = song.dataset.search.includes(searchText);
                    song.hidden = !matches;

                    if (matches) {
                        visibleSongs++;
                    }
                });

                noResults.hidden = visibleSongs > 0;
            });
        }

        document.querySelectorAll('audio').forEach(function (player) {
            player.addEventListener('play', function () {
                document.querySelectorAll('audio').forEach(function (otherPlayer) {
                    if (otherPlayer !== player) {
                        otherPlayer.pause();
                    }
                });
            });
        });
    </script>
</body>
</html>
