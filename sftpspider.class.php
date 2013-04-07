<?php
require 'vendor/autoload.php';
require 'Net/SFTP.php';

class SFTPSpider {
	private $config;
	private $dateFormat = 'Y-m-d';
	private $logging = false;
	private $files;

	public function setDateFormat($format)
	{
		$this->dateFormat = $format;
		if ($this->logging) {
			echo '\nDate format set: ', $format;
		}
	}

	public function setLogging($value)
	{
		try
		{
			if (!is_bool($value))
				throw new Exception('This method accepts only true or false');
			$this->logging = $value;
		} catch(Exception $e) {
			echo 'ERROR: ', $e->getMessage();
		}
	}

	public function addFile($filename)
	{
		$this->files[] = $filename;
	}

	public function listFiles()
	{
		print_r($this->files);
	}

	public function __construct() 
 	{
		$this->config = parse_ini_file('config/config');
		
	}

}

$obj = new SFTPSpider();
$obj->setLogging(true);
$obj->setDateFormat('Ymd');
$obj->addFile('UNSUBSCRIBE.csv');
$obj->listFiles();
