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
			echo "Date format set: ", $format, "\n";
		}
	}

	public function setLogging($value)
	{
		$this->logging = $value;
	}

	public function addFile($filename)
	{
		$this->files[] = $filename;

		if ($this->logging) {
			echo "File added to download list: ", $filename, "\n";
		}
	}

	public function listFiles()
	{
		echo "Listing files queue: \n", print_r($this->files), "\n";
	}

	private function getFiles()
	{
		// Creates file counters
		foreach ($this->files as $file) {
			$count[$file] = 0;
		}

		$this->sftp->chdir($this->config['root_path']);
		// Filters all folders and iterate through them
		$folders = array_diff($this->sftp->nlist(), $this->config['ignore']);
		
		foreach ($folders as $folder) {
			// Check folder date
			$date = DateTime::createFromFormat($this->config['date_format'],substr($folder, 0, 8));
			$last_day = DateTime::createFromFormat('d-m-Y', $this->config['last_day']);

			// Folders older than last day checked will be ignored
			if ($date >= $last_day) {

				// Filters folders to iterate
				$subfolders = preg_grep($this->config['allow'], $this->sftp->nlist($folder));
				foreach ($subfolders as $subfolder) {

					// Download all files in the queue
					foreach ($this->files as $file) {
						if ($this->sftp->size("$folder/$subfolder/$file") != null) {
							$count[$file]++;
							$this->sftp->get("$folder/$subfolder/$file", $this->config['temp_path'] . md5("$folder/$subfolder/$file") . '.csv');
						}
					}
				}
			}
		}
		
		if ($this->logging) {
			foreach ($count as $file => $n) {
				echo "\n\t$file => $n files";
			}
		}

		return $count;
	}

	public function init()
	{
		echo "\nCleaning folders... ";
		$this->wipe();
		echo "\nGetting files... ";
		$this->getFiles();
		echo "\nMerging files... ";
		$this->readCSV();
		$this->close();
		echo "\nDone!\n\n";
	}

	public function readCSV()
	{
		$count = 0;
		$iter = new DirectoryIterator($this->config['temp_path']);
		$fp2 = fopen("{$this->config['file_path']}/{$this->config['final_file_name']}", 'w');
		foreach ($iter as $file) {
			if ($file->isFile()) {
				$fp = fopen($file->getPathname(), 'r');
				
				while (($data = fgetcsv($fp, null, ",")) !== FALSE) {
					$tmpEmail = preg_grep("/^[a-zA-Z0-9_.-]+@[a-zA-Z0-9-]+.[a-zA-Z0-9-.]+$/", $data);
					$tmpEmail = array_shift($tmpEmail);
					if ($tmpEmail != '') {
						fwrite($fp2, $tmpEmail . "\n");
						$count++;
					}
				}
				fclose($fp);
			}
		}
		fclose($fp2);

		if ($this->logging) {
			echo "$count lines merged.";
		}
	}

	private function wipe()
	{
		$cont = 0;
		foreach(glob("{$this->config['temp_path']}*") as $file) {
			$cont++;
			unlink($file);
		}
		
		if ($this->logging) {
			echo "$cont files deleted.";
		}
	}

	private function close()
	{
		// Update last day checked
		$data = parse_ini_file('config/config');
		$fh = fopen('config/config', 'w');
		foreach($data as $key => $value) {
			if($key == 'last_day') {
				$value = new DateTime();
				$value = $value->format('d-m-Y');
			}
			fwrite($fh, "{$key} = {$value}\n");
		}
		fclose($fh);
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


