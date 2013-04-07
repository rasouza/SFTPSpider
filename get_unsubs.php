<?php
require 'vendor/autoload.php';
//set_include_path(get_include_path() . PATH_SEPARATOR . 'vendor/phpsec/');

include('Net/SFTP.php');

// Remove folders
exec('rm csv/* ftp/*');
echo 'Folders cleaned\n\n';

// Connect to SFTP eMarsys
define('FTP_PATH', '/home/glossybox/exports/');
define('NET_SFTP_LOGGING', NET_SFTP_LOG_COMPLEX);
$sftp = new NET_SFTP('e3.emarsys.net');
if (!$sftp->login('glossybox', 'WG4Xr8rD')) {
	exit('Login Failed');
}

// Get all the folders where could exists blacklist files
$folders = preg_grep('/^[^\.]+/', $sftp->nlist(FTP_PATH));

// Init counters for blacklist files
$cont = 0;
$cont2 = 0;
$cont3 = 0;

// Get last day when blacklist were updated
$handle = fopen('last_day.txt', 'r');
$last_day = fgets($handle);
$last_day = DateTime::createFromFormat('d-m-Y', trim($last_day));
fclose($handle);

foreach($folders as $folder) {
	$date = DateTime::createFromFormat('Ymd',substr($folder, 0, 8));

	if($date >= $last_day) {

		$subfolders = preg_grep('/^[A-Za-z0-9]+BR$/', $sftp->nlist(FTP_PATH . $folder));
	
		foreach($subfolders as $subfolder) {
			$file = FTP_PATH . "$folder/$subfolder/UNSUBSCRIBES.csv";
			if ($sftp->size($file) != null) {
			
				echo "Getting: $file\n\n";
				$sftp->get($file, 'ftp/' . 'unsubs_' . $cont . '.csv');
				$cont = $cont + 1;
				$files[] = 'unsubs' . $cont . '.csv';
			}

			$file = FTP_PATH . "$folder/$subfolder/BOUNCES.csv";
			if ($sftp->size($file) != null) {
			
				echo "Getting: $file\n\n";
				$sftp->get($file, 'ftp/' . 'bounces_' . $cont2 . '.csv');
				$cont2 = $cont2 + 1;
				$files[] = 'bounces' . $cont2 . '.csv';
			}

			$file = FTP_PATH . "$folder/$subfolder/COMPLAINTS.csv";
			if ($sftp->size($file) != null) {
			
				echo "Getting: $file\n\n";
				$sftp->get($file, 'ftp/' . 'complaints_' . $cont3 . '.csv');
				$cont3 = $cont3 + 1;
				$files[] = 'complaints' . $cont3 . '.csv';
			}
		}
	}
}
$handle = fopen('last_day.txt', 'w');
$today = new DateTime();
fwrite($handle, $today->format('d-m-Y'));
fclose($handle);
$handle = null;

echo "Download complete.\n";

// Merge lists
exec('cat ftp/bounces*.csv | grep -v ts,bouncecode,email | grep -v ,1606, | grep -v ,1615, > csv/bounces.csv');
exec('cat ftp/complaints*.csv | grep -v ts,email > csv/complaints.csv');
exec('cat ftp/unsubs*.csv | grep -v ts,type,email > csv/unsubscribes.csv');

// TO-DO
// Update database
?>
