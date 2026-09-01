<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function isArtist() {
    return isLoggedIn() && $_SESSION['user']['role'] == 'artist';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireArtist() {
    if (!isArtist()) {
        header('Location: index.php');
        exit;
    }
}

function requireUser() {
    if (!isLoggedIn() || $_SESSION['user']['role'] != 'user') {
        header('Location: index.php');
        exit;
    }
}
