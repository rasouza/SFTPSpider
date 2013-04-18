<?php
require 'vendor/autoload.php';
require 'Net/SFTP.php';

class SFTPSpider {
	private $config;
	private $logging = false;
	private $files;
	private $sftp;

	public function setDateFormat($format)
	{
		$this->config['date_format'] = $format;
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
			echo "\nFile added to download list: ", $filename;
		}
	}

	public function listFiles()
	{
		echo "\nListing files queue: \n", print_r($this->files);
	}

	private function getFiles()
	{
		// Filters all folders and iterate through them
		$folders = array_diff($this->sftp->nlist($this->config['root_path']), $this->config['ignore']);
		foreach ($folders as $folder) {
			// Check folder date
			$date = DateTime::createFromFormat($this->config['date_format'],substr($folder, 0, 8));
			$last_day = DateTime::createFromFormat('d-m-Y', $this->config['last_day']);

			// Folders older than last day checked will be ignored
			if ($date >= $last_day) {

				// Filters folders to iterate
				$subfolders = preg_grep($this->config['allow'], $this->sftp->nlist($this->config['root_path'] . $folder));
				foreach ($subfolders as $subfolder) {

					// Download all files in the queue
					foreach ($this->files as $file) {
						$this->sftp->get("{$this->config['root_path']}$folder/$subfolder/$file", 'ftp/' . md5("$folder/$subfolder/$file") . '.csv');
					}
				}
			}
		}
	}

	public function init()
	{
		$this->getFiles();
		$this->readCSV();
	}

	public function readCSV()
	{
		$iter = new DirectoryIterator('ftp/');
		$fp2 = fopen('csv/blacklist.csv', 'w');
		foreach ($iter as $file) {
			if ($file->isFile()) {
				$fp = fopen($file->getPathname(), 'r');
				
				while (($data = fgetcsv($fp, null, ",")) !== FALSE) {
					$tmpEmail = preg_grep("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $data);
					$tmpEmail = array_shift($tmpEmail);
					if ($tmpEmail != '') {
						fwrite($fp2, $tmpEmail . "\n");
					}
				}
				fclose($fp);
				
			}
		}
		fclose($fp2);
	}

	public function __construct()
 	{
 		try {
			$this->config = parse_ini_file('config/config');
			$this->config['ignore'] = explode(' ', $this->config['ignore']);
			$this->sftp = new NET_SFTP($this->config['host']);
			if (!$this->sftp->login($this->config['username'], $this->config['password']))
				throw new Exception('Login Failed');
		} catch(Exception $e) {
			echo 'ERROR: ', $e->getMessage();
		}
	}

}

$obj = new SFTPSpider();
$obj->setDateFormat('Ymd');
$obj->addFile('UNSUBSCRIBES.csv');
$obj->addFile('BOUNCES.csv');
$obj->addFile('COMPLAINTS.csv');
$obj->init();
