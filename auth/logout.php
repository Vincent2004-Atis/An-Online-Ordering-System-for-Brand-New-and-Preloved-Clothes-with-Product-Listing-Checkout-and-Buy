<?php
require_once '../includes/security.php';
session_start();
session_destroy();
header('Location: /Margaux_Collections/index.php');
exit;
