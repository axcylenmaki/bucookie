<?php
session_start();
session_destroy();
header('Location: /bucookie/index.php');
exit;