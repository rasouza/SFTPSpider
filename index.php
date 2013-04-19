<?php
require 'sftpspider.class.php';

try
{
	$obj = new SFTPSpider();
	$obj->setLogging(true);
	$obj->setDateFormat('Ymd');
	$obj->addFile('UNSUBSCRIBES.csv');
	$obj->addFile('BOUNCES.csv');
	$obj->addFile('COMPLAINTS.csv');
	$obj->init();
} catch(Exception $e) {
	echo "Error", $e->getMessage();
}
?>
