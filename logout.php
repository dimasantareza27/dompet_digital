<?php
session_start();
session_destroy();
header('Location: /Sistem_Keuangan_Mahasiswa/login.php');
exit;
