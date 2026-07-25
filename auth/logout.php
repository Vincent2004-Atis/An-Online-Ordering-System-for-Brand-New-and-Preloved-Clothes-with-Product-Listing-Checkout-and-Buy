<?php
require_once '../includes/security.php';
session_start();
session_destroy();
header('Location: /Marguax_Collection/index.php');
exit;
