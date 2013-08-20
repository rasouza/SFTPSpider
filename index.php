<?php
require 'SFTPSpider.class.php';

try
{
	$obj = new SFTPSpider();
	$obj->setLogging(true);
	$obj->setDateFormat('Ymd');
	$obj->addFile('OPENS.csv');
	$obj->init('open.csv');
} catch(Exception $e) {
	echo "Error", $e->getMessage();
}
?>
