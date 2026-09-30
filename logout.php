<?php
session_unset();
session_destroy();

header("Location: staff_login.php");
exit();
?>