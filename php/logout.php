<?php
session_start();
$_SESSION["zalogowany"] = false;
session_destroy();
header('Location: ../index.php');
exit();