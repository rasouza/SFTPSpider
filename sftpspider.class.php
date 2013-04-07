<?php
require 'vendor/autoload.php';
require 'Net/SFTP.php';

class SFTPSpider {
	private $config;
	private $dateFormat = 'Y-m-d';
	private $logging = false;
	private $files;
	private $sftp;

	public function setDateFormat($format)
	{
		$this->dateFormat = $format;
		if ($this->logging) {
			echo "\nDate format set: ", $format;
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
			echo "\nERROR: ", $e->getMessage();
		}
	}

	public function addFile($filename)
	{
		$this->files[] = $filename;
		if ($this->logging) {
			echo "\n\rFile added to download list: ", $filename;
		}
	}

	public function listFiles()
	{
		echo "\nListing files queue: \n", print_r($this->files);
	}

	public function getFile($filepath)
	{
		
	}

	public function __construct()
 	{
 		try {
			$this->config = parse_ini_file('config/config');
			$this->sftp = new NET_SFTP($this->config['host']);
			if (!$this->sftp->login($this->config['username'], $this->config['password']))
				throw new Exception('Login Failed');
		} catch(Exception $e) {
			echo 'ERROR: ', $e->getMessage();
		}
	}

}

$obj = new SFTPSpider();
$obj->setLogging(true);
$obj->setDateFormat('Ymd');
$obj->addFile('UNSUBSCRIBE.csv');
$obj->listFiles();
