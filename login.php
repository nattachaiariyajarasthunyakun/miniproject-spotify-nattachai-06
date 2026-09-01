<?php
require_once __DIR__ . '/connect/spotify_db.php';
require_once __DIR__ . '/connect/auth.php';


if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    $sql = 'SELECT * FROM `user` WHERE email = ?';
    $statement = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($statement, 's', $email);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => $user['user_id'],
            'name' => $user['username'],
            'role' => $user['role'],
            'artist_id' => $user['artist_id']
        ];

        header('Location: index.php');
        exit;
    }

    $message = 'Email or password is incorrect.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in · SoundSpace</title>
    <link rel="stylesheet" href="style.css?v=4">
</head>
<body class="upload-page">
    <main class="upload-box">
        <a class="logo" href="index.php"><span>♪</span> SoundSpace</a>
        <section class="upload-card">
            <p class="green-text">WELCOME BACK</p>
            <h1>Log in</h1>
            <p class="muted">Users make playlists. Artists upload and delete their own music.</p>

            <?php if ($message != '') { ?>
                <p class="error"><?= htmlspecialchars($message) ?></p>
            <?php } ?>

            <form method="POST">
                <label>Email</label>
                <input type="email" name="email" required>

                <label>Password</label>
                <input type="password" name="password" required>

                <button class="button" type="submit">Log in</button>
            </form>

            <p class="account-link">New here? <a href="register.php">Create an account</a></p>
        </section>
    </main>
</body>
</html>
