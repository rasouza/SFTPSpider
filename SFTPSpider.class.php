<?php
require 'vendor/autoload.php';
require 'Net/SFTP.php';

class SFTPSpider {
	private $config;
	private $logging = FALSE ;
	private $files;
	private $sftp;
	private $_log_msg; // Log Messages for each method

	/** 
	* Constructor responsible for connection setup and config
	**/
	public function __construct()
 	{
 		try {
			$this->config = require_once('config/config.php');
			
			$this->sftp = new NET_SFTP($this->config['host']);
			if (!$this->sftp->login($this->config['username'], $this->config['password']))
				throw new Exception('Login Failed');
		} catch(Exception $e) {
			echo 'ERROR: ', $e->getMessage();
		}
	}

	/**
	* VERBOSE MODE
	**/
	public function setLogging($value) { $this->logging = $value; }	


	
	/**
	* List all files in the queue.
	**/
	public function listFiles()	{ echo "Listing files queue: \n", print_r($this->files), "\n"; }



	/** 
	* Retrieves date format defined in CONFIG file
	*
	* @param string $format PHP Date format string
	**/
	public function setDateFormat($format)
	{
		$this->config['date_format'] = $format;
		$this->printLogAction("Date format set: {$format}");
	}



	/** 
	* Add files to the queue
	*
	* @param string $filename File name to be retrieved in data extractor
	**/
	public function addFile($filename)
	{
		$this->files[] = $filename;
		$this->printLogAction("File added to download list: {$filename}");
	}



	/**
	* LOG Action in console if VERBOSE MODE is enabled
	*
	* @param string $msg Message to be printed in LOG console
	**/
	private function printLogAction($msg) {	if ($this->logging) echo "[LOG] $msg\n";	}



	/** 
	* Iterates through the whole SFTP retriving files in the queue
	*
	* @return Array $count number of files retrived
	**/
	private function getFiles()
	{
		// File counters
		foreach ($this->files as $file) {
			$count[$file] = 0;
		}

		$this->sftp->chdir($this->config['root_path']); // Set path to root folder in SFTP

		// Filters all initial folders for iterating through them
		$folders = array_diff($this->sftp->nlist(), $this->config['ignore']);
		
		foreach ($folders as $folder) {
			// Check folder date
			$date = DateTime::createFromFormat($this->config['date_format'], substr($folder, 0, 8));
			$last_day = DateTime::createFromFormat('d-m-Y', $this->config['last_day']);

			// Folders older than last day checked will be ignored
			if ($date >= $last_day) {
				// Bypass allowed folders
				$subfolders = preg_grep($this->config['allow'], $this->sftp->nlist($folder));

				foreach ($subfolders as $subfolder) {
					// Download all files in the queue
					foreach ($this->files as $file) {
						if ($this->sftp->size("$folder/$subfolder/$file") != NULL) {
							$count[$file]++;
							$this->sftp->get("$folder/$subfolder/$file", $this->config['temp_path'] . md5("$folder/$subfolder/$file") . '.csv');
						}
					}
				}
			}
		}
		
		foreach ($count as $file => $n) $this->printLogAction("\t$file => $n files");
		return $count;
	}



	/** 
	* Wipe tmp folder
	**/
	private function wipe()
	{
		$cont = 0;
		foreach(glob("{$this->config['temp_path']}*") as $file) {
			$cont++;
			unlink($file);
		}
		
		$this->printLogAction("\t$cont files deleted");
		
	}	



	/** 
	* Merge all files
	*
	* @param string $filename File name to be retrieved in data extractor
	**/
	public function mergeCSV($filename)
	{
		$count = 0;
		$iter = new DirectoryIterator($this->config['temp_path']);
		$fp2 = fopen("{$this->config['file_path']}/{$filename}", 'w');
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

		$this->printLogAction("\t$count lines merged");
	}



	/** 
	* Update date in config file so that extractor don't need to look for older days
	**/
	private function close()
	{
		// Update last day checked
		$this->config['last_day'] = (new DateTime)->format('d-m-Y');
		file_put_contents('config/config.php', '<?php return ' . var_export($this->config, true) . ';');

		$this->printLogAction("DONE!\n");
	}

	

	/** 
	* CORE method which handles all actions needed to be done
	*
	* @param string $filename File name to be saved in the end of the process
	**/

	public function init($filename)
	{
		// Create folders if they don't exist
		if(!file_exists('csv'))
			mkdir('csv');
		if(!file_exists('ftp'))
			mkdir('ftp');

		$this->printLogAction("Getting files... ");
		$this->getFiles();
		$this->printLogAction("Merging files... ");
		$this->mergeCSV($filename);
		$this->printLogAction("Cleaning folders... ");
		$this->wipe();
		$this->close();
	}

}


