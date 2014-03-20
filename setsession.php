<?php
session_start();


$_SESSION['host'] = $_REQUEST['host'];
$_SESSION['port'] = $_REQUEST['port'];
$_SESSION['passwd'] = $_REQUEST['passwd'];
$_SESSION['id'] = session_id();
$_SESSION['socket'] = '/tmp/vnc_' . $_SESSION['id'];
$_SESSION['username'] = $_REQUEST['username'];

// para shm
$tmpfile = tempnam("/tmp", "PHPVNC");
file_put_contents($tmpfile, "");
$shid = sprintf("%u", ftok($tmpfile, "U"));
@unlink($tmpfile);

$_SESSION['shid'] = $shid;

header('Location: vncview.php');
