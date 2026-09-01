<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $genre = trim($_POST['genre']);

    if ($username == '' || $email == '' || $password == '') {
        $message = 'Please complete the required fields.';
    } elseif ($role == 'artist' && $genre == '') {
        $message = 'Artists need to add a genre.';
    } else {
        $check = mysqli_prepare($conn, 'SELECT user_id FROM `user` WHERE email = ?');
        mysqli_stmt_bind_param($check, 's', $email);
        mysqli_stmt_execute($check);

        if (mysqli_num_rows(mysqli_stmt_get_result($check)) > 0) {
            $message = 'This email already has an account.';
        } else {
            mysqli_begin_transaction($conn);

            $nextUser = mysqli_query($conn, 'SELECT COALESCE(MAX(user_id), 0) + 1 AS next_id FROM `user`');
            $userId = mysqli_fetch_assoc($nextUser)['next_id'];
            $artistId = null;

            if ($role == 'artist') {
                $artistId = 'artist_' . uniqid();
                $artistSql = 'INSERT INTO artist (artist_id, artist_name, genre, profile_img_url)
                              VALUES (?, ?, ?, ?)';
                $artistStatement = mysqli_prepare($conn, $artistSql);
                $image = '';
                mysqli_stmt_bind_param($artistStatement, 'ssss', $artistId, $username, $genre, $image);
                mysqli_stmt_execute($artistStatement);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $userSql = 'INSERT INTO `user` (user_id, username, email, profile_img_url, password_hash, role, artist_id)
                        VALUES (?, ?, ?, ?, ?, ?, ?)';
            $userStatement = mysqli_prepare($conn, $userSql);
            $image = '';
            mysqli_stmt_bind_param($userStatement, 'issssss', $userId, $username, $email, $image, $passwordHash, $role, $artistId);

            if (mysqli_stmt_execute($userStatement)) {
                mysqli_commit($conn);
                $_SESSION['user'] = [
                    'id' => $userId,
                    'name' => $username,
                    'role' => $role,
                    'artist_id' => $artistId
                ];
                header('Location: index.php');
                exit;
            }

            mysqli_rollback($conn);
            $message = 'Could not create the account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create account · SoundSpace</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body class="upload-page">
    <main class="upload-box">
        <a class="logo" href="index.php"><span>♪</span> SoundSpace</a>
        <section class="upload-card">
            <p class="green-text">JOIN SOUNDSPACE</p>
            <h1>Create account</h1>

            <?php if ($message != '') { ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php } ?>

            <form method="POST">
                <label>Name</label>
                <input type="text" name="username" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" minlength="6" required>

                <label>Account type</label>
                <select name="role" id="role" required>
                    <option value="user">User — create playlists</option>
                    <option value="artist">Artist — upload and delete my songs</option>
                </select>

                <div id="genre-box" hidden>
                    <label>Music genre</label>
                    <input type="text" name="genre" placeholder="e.g. Pop">
                </div>

                <button class="button" type="submit">Create account</button>
            </form>

            <p class="account-link">Already have an account? <a href="login.php">Log in</a></p>
        </section>
    </main>
    <script>
        const role = document.getElementById('role');
        const genreBox = document.getElementById('genre-box');
        role.addEventListener('change', function () {
            genreBox.hidden = role.value !== 'artist';
        });
    </script>
</body>
</html>
