<?php
/**
 * X Business Grant - User Logout
 */

session_start();

unset($_SESSION['user_logged_in']);
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_email']);

session_destroy();

header('Location: index.php');
exit;
