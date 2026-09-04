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
$cssFile = ($pagePath ? $pagePath : '') . '/style.css?v=9';

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
                <div class="song-list" id="song-list">
                    <div class="song header-row">
                        <span>#</span>
                        <span>Title</span>
                        <span>Artist</span>
                        <span></span>
                        <?php if (isArtist()) { ?><span>Actions</span><?php } ?>
                    </div>

                    <?php foreach ($songs as $number => $song) { ?>
                        <article class="song song-row"
                                 data-search="<?= htmlspecialchars(strtolower($song['title'] . ' ' . $song['artist_name'])) ?>"
                                 data-src="<?= htmlspecialchars($song['file_path']) ?>"
                                 data-title="<?= htmlspecialchars($song['title']) ?>"
                                 data-artist="<?= htmlspecialchars($song['artist_name']) ?>"
                                 data-song-id="<?= $song['song_id'] ?>">

                            <span class="number">
                                <span class="track-number"><?= $number + 1 ?></span>
                                <button type="button" class="row-play-btn" aria-label="Play">
                                    <svg class="icon-play" viewBox="0 0 16 16"><path d="M3 2l11 6-11 6V2z"/></svg>
                                    <svg class="icon-pause" viewBox="0 0 16 16"><path d="M3 2h4v12H3V2zm6 0h4v12H9V2z"/></svg>
                                </button>
                            </span>

                            <div class="title">
                                <span class="cover">♪</span>
                                <strong class="song-title-text"><?= htmlspecialchars($song['title']) ?></strong>
                            </div>

                            <span class="artist">
                                <?= htmlspecialchars($song['artist_name']) ?>
                                <small><?= htmlspecialchars($song['albums_name']) ?></small>
                            </span>

                            <span class="row-duration">--:--</span>

                            <?php if (isArtist() && $_SESSION['user']['artist_id'] == $song['artist']) { ?>
                                <div class="row-actions">
                                    <button type="button" class="edit-toggle-btn">Edit</button>
                                    <form method="POST" action="delete_song.php" class="delete-form">
                                        <input type="hidden" name="song_id" value="<?= $song['song_id'] ?>">
                                        <button type="submit" class="delete-button">Delete</button>
                                    </form>
                                </div>
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

    <?php if (isArtist()) { ?>
    <div class="modal-overlay" id="edit-modal-overlay" hidden>
        <div class="modal-box">
            <h3>Edit song title</h3>
            <form method="POST" action="edit_song.php" id="edit-title-form">
                <input type="hidden" name="song_id" id="edit-song-id">
                <input type="text" name="title" id="edit-title-input" required autocomplete="off">
                <div class="modal-actions">
                    <button type="button" id="edit-cancel-btn" class="outline-button button small-button">Cancel</button>
                    <button type="submit" class="button small-button">Save</button>
                </div>
            </form>
        </div>
    </div>
    <?php } ?>

    <?php if (count($songs) > 0) { ?>
    <footer class="player-bar" id="player-bar">
        <audio id="global-audio" preload="metadata"></audio>

        <div class="player-now-playing">
            <span class="player-cover" id="player-cover">♪</span>
            <div class="player-track-info">
                <strong id="player-title">Select a song</strong>
                <small id="player-artist">&nbsp;</small>
            </div>
        </div>

        <div class="player-center">
            <div class="player-controls">
                <button type="button" id="prev-btn" class="control-btn" aria-label="Previous">
                    <svg viewBox="0 0 16 16"><path d="M4 2v12H2V2h2zm10 0v12L5 8l9-6z"/></svg>
                </button>
                <button type="button" id="play-pause-btn" class="control-btn play-main" aria-label="Play">
                    <svg class="icon-play" viewBox="0 0 16 16"><path d="M3 2l11 6-11 6V2z"/></svg>
                    <svg class="icon-pause" viewBox="0 0 16 16"><path d="M3 2h4v12H3V2zm6 0h4v12H9V2z"/></svg>
                </button>
                <button type="button" id="next-btn" class="control-btn" aria-label="Next">
                    <svg viewBox="0 0 16 16"><path d="M12 2v12h2V2h-2zM2 2v12l9-6-9-6z"/></svg>
                </button>
            </div>

            <div class="player-progress">
                <span id="current-time" class="time">0:00</span>
                <input type="range" id="seek-bar" value="0" min="0" max="100" step="1">
                <span id="total-time" class="time">0:00</span>
            </div>
        </div>

        <div class="player-volume">
            <span>🔊</span>
            <input type="range" id="volume-bar" value="80" min="0" max="100" step="1">
        </div>
    </footer>
    <?php } ?>

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

        // ---- Edit song title modal ----
        const editOverlay = document.getElementById('edit-modal-overlay');

        if (editOverlay) {
            const editSongIdField = document.getElementById('edit-song-id');
            const editTitleInput = document.getElementById('edit-title-input');
            const editCancelBtn = document.getElementById('edit-cancel-btn');

            function openEditModal(row) {
                editSongIdField.value = row.dataset.songId;
                editTitleInput.value = row.dataset.title;
                editOverlay.hidden = false;
                editTitleInput.focus();
                editTitleInput.select();
            }

            function closeEditModal() {
                editOverlay.hidden = true;
            }

            document.querySelectorAll('.edit-toggle-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    openEditModal(btn.closest('.song-row'));
                });
            });

            editCancelBtn.addEventListener('click', closeEditModal);

            editOverlay.addEventListener('click', function (e) {
                if (e.target === editOverlay) closeEditModal();
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && !editOverlay.hidden) closeEditModal();
            });
        }

        // ---- Global player bar ----
        const audio = document.getElementById('global-audio');

        if (audio) {
            const rows = Array.from(document.querySelectorAll('.song-row'));
            const playPauseBtn = document.getElementById('play-pause-btn');
            const prevBtn = document.getElementById('prev-btn');
            const nextBtn = document.getElementById('next-btn');
            const seekBar = document.getElementById('seek-bar');
            const volumeBar = document.getElementById('volume-bar');
            const currentTimeEl = document.getElementById('current-time');
            const totalTimeEl = document.getElementById('total-time');
            const playerTitle = document.getElementById('player-title');
            const playerArtist = document.getElementById('player-artist');

            let currentIndex = -1;

            function formatTime(seconds) {
                if (!isFinite(seconds) || seconds < 0) return '0:00';
                const m = Math.floor(seconds / 60);
                const s = Math.floor(seconds % 60);
                return m + ':' + (s < 10 ? '0' : '') + s;
            }

            function setRowIcons(row, playing) {
                if (!row) return;
                row.classList.toggle('is-playing', playing);
                const btn = row.querySelector('.row-play-btn');
                if (btn) btn.classList.toggle('is-playing', playing);
            }

            function clearAllRowIcons() {
                rows.forEach(function (row) { setRowIcons(row, false); });
            }

            function setMainButtonIcon(playing) {
                playPauseBtn.classList.toggle('is-playing', playing);
            }

            function loadTrack(index, autoplay) {
                if (index < 0 || index >= rows.length) return;
                currentIndex = index;
                const row = rows[index];

                audio.src = row.dataset.src;
                playerTitle.textContent = row.dataset.title;
                playerArtist.textContent = row.dataset.artist;

                clearAllRowIcons();

                if (autoplay) {
                    audio.play();
                }
            }

            function playCurrent() {
                if (currentIndex === -1) {
                    loadTrack(0, true);
                } else {
                    audio.play();
                }
            }

            function goToOffset(offset) {
                if (currentIndex === -1) return;
                let nextIndex = currentIndex + offset;
                if (nextIndex < 0) nextIndex = rows.length - 1;
                if (nextIndex >= rows.length) nextIndex = 0;
                loadTrack(nextIndex, true);
            }

            // Row play buttons
            rows.forEach(function (row, index) {
                const btn = row.querySelector('.row-play-btn');
                btn.addEventListener('click', function () {
                    if (currentIndex === index && !audio.paused) {
                        audio.pause();
                    } else if (currentIndex === index && audio.paused) {
                        audio.play();
                    } else {
                        loadTrack(index, true);
                    }
                });
            });

            playPauseBtn.addEventListener('click', function () {
                if (currentIndex === -1) {
                    playCurrent();
                } else if (audio.paused) {
                    audio.play();
                } else {
                    audio.pause();
                }
            });

            prevBtn.addEventListener('click', function () { goToOffset(-1); });
            nextBtn.addEventListener('click', function () { goToOffset(1); });

            audio.addEventListener('play', function () {
                setMainButtonIcon(true);
                if (currentIndex !== -1) setRowIcons(rows[currentIndex], true);
            });

            audio.addEventListener('pause', function () {
                setMainButtonIcon(false);
                if (currentIndex !== -1) setRowIcons(rows[currentIndex], false);
            });

            audio.addEventListener('ended', function () {
                goToOffset(1);
            });

            audio.addEventListener('loadedmetadata', function () {
                seekBar.max = Math.floor(audio.duration) || 0;
                totalTimeEl.textContent = formatTime(audio.duration);

                if (currentIndex !== -1) {
                    rows[currentIndex].querySelector('.row-duration').textContent = formatTime(audio.duration);
                }
            });

            audio.addEventListener('timeupdate', function () {
                seekBar.value = Math.floor(audio.currentTime);
                currentTimeEl.textContent = formatTime(audio.currentTime);
                updateFill(seekBar);
            });

            seekBar.addEventListener('input', function () {
                audio.currentTime = seekBar.value;
                updateFill(seekBar);
            });

            volumeBar.addEventListener('input', function () {
                audio.volume = volumeBar.value / 100;
                updateFill(volumeBar);
            });

            function updateFill(rangeInput) {
                const min = Number(rangeInput.min) || 0;
                const max = Number(rangeInput.max) || 100;
                const pct = max > min ? ((rangeInput.value - min) / (max - min)) * 100 : 0;
                rangeInput.style.setProperty('--fill', pct + '%');
            }

            audio.volume = volumeBar.value / 100;
            updateFill(volumeBar);
            updateFill(seekBar);
        }
    </script>
</body>
</html>