<?php
require_once 'config.php';
session_destroy();
header("Location: {$base_path}/login.php");
exit;
