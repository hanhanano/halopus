<?php
session_start();

unset($_SESSION['member_logged_in']);
unset($_SESSION['member_id']);
unset($_SESSION['member_code']);
unset($_SESSION['member_name']);
unset($_SESSION['member_email']);

header('Location: login.php');
exit();
?>
