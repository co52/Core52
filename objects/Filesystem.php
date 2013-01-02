<?php

/**
 * Filesystem Class
 * 
 * Originally written for Glarity. It is definitely way too Glarity
 * specific right now, but it's possible it could be abstracted 
 * for a more general use. Maybe not. 
 *
 * --OLD NOTES--
 * fs:: is a static class holder for the file system functionality of the Glarity
 * website. Implemented anywhere using the fs:: with static :: scope resolution.
 *
 * @author "David Boskovic" <dboskovic@companyfiftytwo.com>
 * @package Core52
 * @version 1.0
 * @todo Evaluate for deletion.
 * 
 **/


class Filesystem {

	public static $errors = array();
	public static $artwork;				// Artwork object

	
	# set up the fs:: class
	public static function artwork()
	{
		if(!self::$artwork) self::$artwork = new Artwork();
		return self::$artwork;
	}
	
	
	# get an array of files in a folder
	public static function listfiles($dir, $clean = false)
	{	
		if($clean) $dir = self::safeName($dir);
		
		// Open a known directory, and proceed to read its contents
		if (is_dir($dir))
		{
			$files = array();
			
		    if ($dh = @opendir($dir))
		    {
		        while (($file = readdir($dh)) !== false)
		        {
					if(substr($file, 0, 1) != '.') $files[] = $file;
		        }
		        closedir($dh);
		        
		        return (count($files) > 0)? $files : false;
		    }
			else
			{
				self::$errors[] = "Could not open --$dir-- in fs::listfiles()";
				return false;
			}
		}
		else
		{
			self::$errors[] = "Not a directory: --$dir-- in fs::listfiles()";
			return false;
		}
	}

	
	# create a folder
	public static function mkdir($dir, $umask = 0770) {
		// Create a new directory
		if (!is_dir($dir)) {
			//echo($dir);
			$old_umask = umask(0);
			mkdir($dir, $umask);
			
			umask($old_umask);
			if(umask() != $old_umask){
				self::$errors[] = "Could not set umask of --$dir-- in fs::mkdir()";
				return false;
			}
			chmod($dir, 0770);
			
			return true;
		}
		else {
			self::$errors[] = "--$dir-- already exists in fs::make_dir()";
			return false;
		}
	}

	
	# copy a file
	public static function copy($from, $to)
	{
		if (!@copy($from, $to)) {
			self::$errors[] = "Failed to copy from --$from-- to --$to-- in fs::copy()";
			return false;
		} else {
			return true;
		}
	}

	
	# delete a file
	public static function delete($file)
	{
		if (!@unlink($file)) {
			self::$errors[] = "Failed to delete file --$file-- fs::delete()";
			return false;
		} else {
			return true;
		}
	}
	
	
	# get ftp key for an account
	public static function ftpkey($id)
	{
		// SQL Variable Cleaning
		$id = is_numeric($id) ? $id : 0;

		// Run Query
		$account = db::e("SELECT `key` FROM `ftp_keys` WHERE `acc` = $id LIMIT 1");

		$return = $account ? mysql_fetch_assoc($account) : false;
		
		// check to see if we have the ftp folder
		if($return && !is_dir(FTPBASEPATH."/".$return['key'])) {
			self::mkftpdir($return['key']);
		}
		// return false if there is no results and the row if there is one
		return $return ? $return['key'] : false;
	}
	
	
	# create an ftp key for an account
	public static function mkkey($id)
	{
		// SQL Variable Cleaning
		$id = is_numeric($id) ? $id : 0;

		$key = md5(uniqid(rand().$id, true));

		// Save the key
		db::e("INSERT INTO `ftp_keys` SET `key` = '$key', `acc` = $id");

		self::mkftpdir($key);

		return $key;
	}
	
	
	# create an ftp folder
	public static function mkftpdir($key, $base = null) {
		$base = (is_null($base))? FTPBASEPATH : $base;
		return self::mkdir("$base/$key") ? true : false;
	}
	
	
	# create media folder
	public static function mkmediadir($mediatype, $id, $acc)
	{
		// Validate media type
		if(!in_array($mediatype, os::$mediatypes) && $mediatype != 'music') return false;
		$cls = ($mediatype == 'music')? 'album' : $mediatype;
		$cls = ($mediatype == 'video')? 'film' : $mediatype;
		
		$item = db::findById($id, 'glarity_pending.'.$cls.'s');
		if(!$item) return false;
		
		// Get the account key
		$key = self::ftpkey($acc);
		if(!$key) return false;
		// try to create the parent folder if it doesn't exist
		$dir = FTPBASEPATH."/$key/{$cls}s";
		if(!is_dir($dir) && !self::mkdir($dir)) {
			self::$errors[] = "Could not create parent folder --$dir-- in fs::mkalbumdir()";
			return false;
		}
		
		// try to create the album folder
		$dir .= "/${item['ftpname']}";
		if(!is_dir($dir) && !self::mkdir($dir)) {
			self::$errors[] = "Could not create album folder --$dir-- in fs::mkalbumdir()";
			return false;
		}
		
		return true;
	}

	
	# get media folder
	public static function mediadir($mediatype, $id, $acc)
	{
		// Validate media type
		if(!in_array($mediatype, os::$mediatypes) && $mediatype != 'music') return false;
		$cls = ($mediatype == 'music')? 'album' : $mediatype;
		$cls = ($mediatype == 'video')? 'film' : $mediatype;
		
		$item = db::findById($id, 'glarity_pending.'.$cls.'s');
		
		// Validate media id
		if(!$item) return false;
		
		// Get the account key
		$key = self::ftpkey($acc);
		if(!$key) return false;
		
		// Try to open the folder
		$dir = FTPBASEPATH."$key/{$cls}s/${item['ftpname']}";
		return (is_dir($dir))? $dir : false;
	}
	

	# list files in a media folder
	public static function listmediafiles($mediatype, $id, $acc)
	{
		// Get the media folder
		$dir = self::mediadir($mediatype, $id, $acc);
		
		if(!$dir) return false;

		// Get all the media files
	    if ($fh = opendir($dir))
	    {
			$array = array();

		    /* This is the correct way to loop over the directory. */
			$cnt = 0;
		    while (false !== ($file = readdir($fh)))
		    {
		    	// skip the current and parent folders on UNIX systems
		        if ($file == "." || $file == "..") continue;

		        if(self::is_audio($file)) {
					$array['audio'][] = $file;
					$type = 'audio';
				}
				if(self::is_video($file)) {
					$array['video'][] = $file;
					$type = 'video';
				}
				if(stristr($file, '.pdf')) {
					$type = 'pdf';
				}
				if(stristr($file, '.zip')) {
					$type = 'zip';
				}
				if(!$type) {
					$array['other'][] = $file;
					$type = 'other';
				}
				
					$array['all'][] = array('name' => $file, 'type' => $type);
				$cnt++;
				$type = false;
		    }
		    closedir($fh);

		    // return the files
		    return ($cnt >= 1)? $array : false;
		}
		else
		{
			return false;
		}
	}
	

	# check if a filename is a music file
	public static function is_audio($filename) {
		$extensions = array(".mp3", ".mp4", ".m4a", ".aac", ".ogg", ".wav", ".wma");
		foreach($extensions as $ext) {
			if(stristr($filename, $ext)) {
				return $ext;
			}
		}
		return false;
	}
	
	
	# check if a filename is a video file
	public static function is_video($filename) {
		$extensions = array(".mov", ".mp4", ".mpg", ".m4v", ".avi");
		foreach($extensions as $ext) {
			if(stristr($filename, $ext)) {
				return $ext;
			}
		}
		return false;
	}
	
	
	# generate a random file name
	public static function randomfilename($type, $userid = 1) {
		return md5(uniqid(rand()+$userid, true)).$type;
	}

	
	# clean a potential file name of unsafe chars
	public static function safeName( $name ) { // stolen from wordpress
		$name = strtolower( $name );
		$name = preg_replace('/&.+?;/', '', $name); // kill entities
		$name = str_replace( '_', '-', $name );
		$name = preg_replace('/[^a-z0-9\s-.]/', '', $name);
		$name = preg_replace('/\s+/', '-', $name);
		$name = preg_replace('|-+|', '-', $name);
		$name = trim($name, '-');
		return $name;
	}
	
	public static function logFile($filename, $type, $id, $ref) {
		db::create('filesystem', array('server' => 2, 'location' => $filename, 'ref1' => $type, 'ref2' => $id, 'ref3' => $ref));
	}
	

# get the download filename by download key
	public static function download($key, $user)
	{
		// look up the key
		$key = addslashes($key);
		$dbh = db::find('downloads', array('conditions'=>"`key` = '$key'"));
		if(!$dbh) return false;
		//echo 'test';
		$row = mysql_fetch_assoc($dbh);
		//print_r($row);
		//print $user;
		if(!$row || $row['user'] != $user) return false;
		
		// look up the item
		if($row['section'] != 'video') {
			$audio_pref = os::user($user)->pref('download_format');
			$fmt = (strtolower($audio_pref) == 'aac')? 'm4a' : 'mp3';
			$where = "AND `ref3`='$fmt'";
		}
		if($row['section'] == 'audiobook_track') {$row['section'] = 'audiobook'; $where = "AND ref3 = 'mp3'"; }
		if($row['section'] == 'track') $row['section'] = 'music';
		
		$fdbh = db::e("SELECT * FROM `filesystem` WHERE `ref1`='${row['section']}' AND `ref2`='${row['item']}' $where;");
		$file = mysql_fetch_assoc($fdbh);
		if(!$file) return false;
		$file['name'] = fs::safeName($row['name']);
		return $file;
	}	
}