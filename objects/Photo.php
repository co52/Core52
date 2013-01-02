<?php

    /* Image Class 0.1 -- Alex King (copyright 2007)
	*****************************************************

	Provides a reusable wrapper for the PHP GD image handling functions.
    Includes various ways to load images, a versioning system, and supports
    all import and export image types allowed by your installation.

    * open: creates the images from whatever is passed to it, automatically
        determining which function should be used.
    * openFile: creates image from a file, whether on disk or remotely.
	* openUpload: creates image from the $_FILES array entry provided.
	* openFTP: creates image using the FTP url wrapper format used by PHP
        (ftp://user:pass@site.com/file.jpg)
	* setVersion: saves the current image as a version under the name provided..
	* currentVersion: loads a previously saved version. Asking for "original"
        will return the image that the class was first loaded with.
    * resize: shrinks or enlarges the image to the specified size either on the
        x or y axis. Pixels are assumed by default, although percentages may be
        used as well (such as "50%").
	* save: exports the image to filename provided. The export type is automatically
        detected from the file extention, but can be also be specified.

	Version Log
	==========
	[0.1 - March 10th] Created basic class with open, resize, and save functions.
	[0.2 - May 2nd 2008] Added reflect() method (Mod by Jonathon Hill <jonathon@compwright.com>)
	
	
	@TODO: Move comments into standardized DocBlock format

*/

    class Photo {

        // Our version control system variable.
        protected $versions;
        protected $current;
        
        // Settings defaults - settings passed-in will be merged over defaults
        protected $settings = array(
            'upload_max_size' => 0
        );

		protected $filepath;

        // Class constructor
        public function __construct($input = false, array $settings = array()) {

            // Save settings, overwriting keys that are passed in
            $this->settings = array_merge($this->settings, $settings);
            
            if (!$input) {
                return;
            }
            
            if(is_string($input)) {
                // Identify what type of data we have been sent to instanciate this
                // image, route it to the correct creation function, and return the result.
                $parsedUrl = @parse_url($input);
                if (($parsedUrl["scheme"] == ("ftp" || "ftps"))) {
                    $this->createFromFTP($input);
                } elseif(strlen($input) < 255 && file_exists($input)) {
                    $this->createFromFile($input);
                } else {
                    $this->createFromValue($input);
                }
            } elseif(is_array($input)) {
                $this->createFromUpload($input);
            }
        }
        
        public function createFromValue($input) {
        	$tmp = tempnam('', '');
        	file_put_contents($tmp, $input);
        	$this->createFromFile($tmp);
        }

        // Creates an image from an FTP or FTPS path in the following format:
        // ftps://username:password@example.com/images/file.jpg
        public function createFromFTP ($path) {

            // Attempt to open this using URL wrappers and the createFromFile
            // function.
            return $this->createFromFile($path);

        }

        // Creates an image from a file that has been uploaded, when provided with
        // an entry from a $_FILES variable (such as $_FILES["thefile"])
        public function createFromUpload ($filesEntry) {

            // Make sure we have a real file.
            if (is_uploaded_file($filesEntry["tmp_name"])) {
                // Check image size
                if($this->settings['upload_max_size'] > 0) {
                    // Enforce size limit
                    if($filesEntry["size"] > $this->settings['upload_max_size']) {
                        throw new ErrorException("The uploaded file is too large.");
                    }
                }
            
                // This is a real upload, attempt to create the image using the
                // createFromFile function on the temporary file.
                $result = $this->createFromFile ($filesEntry["tmp_name"], $filesEntry["name"]);

                // Delete the temporary file.
                unlink($filesEntry["tmp_name"]);

                // Return the result of the image creation function.
                return $result;
            }
			else { return false; }
        }

        // Creates an image from a file or URL source.
        public function createFromFile ($path, $realname = false) {
			
			// Save filepath
			$this->filepath = $path;
			
            // Make sure we were not sent a blank string.
            if (!$path) { return false; }

            // Parse out the file extension.
            $fileExtension = $realname ? end(explode('.', $realname)) : end(explode('.', $path));

            // Choose the image creation function to match with the guessed
            // image type.
            switch (strtolower($fileExtension)) {
                case "gif":
                    $this->current = imagecreatefromgif($path);
                    break;
                case "png":
                    $this->current = imagecreatefrompng($path);
                    break;
                case "wbmp":
                    $this->current = imagecreatefromwbmp($path);
                    break;
                case "xpm":
                    $this->current = imagecreatefromxpm($path);
                    break;
                case "xbm":
                    $this->current = imagecreatefromxbm($path);
                    break;
                default:
                case "jpg":
                case "jpeg":
                    $this->current = @imagecreatefromjpeg($path);
                    break;
            }

            // Make sure that one of these functions was successful.
            if (!$this->current) {

                // We could not create the image, the image type may have been
                // guessed wrong, or URL wrappers are disabled. Attempt to grab
                // the URL and process it.
                $imageData = file_get_contents($path);
                $this->current = @imagecreatefromstring($imageData);
            }

            // Set the current image as the original version if we were successful,
            // and return the result of the function.
            if ($this->current) {
                $this->setVersion("original");
                return true;
            } else {
                throw new ErrorException("Uploaded file is not a supported image format");
            }
        }

        // Returns an earlier version of the current image
        public function getVersion($name) {
			return $this->versions[$name];
        }

        // Allows the setting of the current image as a version that can later
        // be refered back to as a backup.
        public function setVersion($name) {

            // Take what is the $current variable and copy it into an entry in
            // the versions variable.
            $this->versions[$name] = $this->current;
        }

        // Allows the setting of a set version as the current version.
        public function currentVersion($name = NULL) {
        	if(is_null($name)) {
        		return $this->current;
        	} elseif($name && array_key_exists($name, $this->versions)) {
                $this->current = $this->versions[$name];
                return ($this->current) ? true : false;
            } else {
                return false;
            }
        }
        
		public function crop($x, $y) {

			$cropped_image = imagecreatetruecolor($x, $y);
			$white = imagecolorallocate($cropped_image, 255, 255, 255);
			imagefill($cropped_image, 0, 0, $white);
			
			$cs = $this->getSize();
			$offsetx = ($cs['x'] - $x) / 2;
			$offsety = ($cs['y'] - $y) / 2;

			imagecopyresampled($cropped_image, $this->current, ($offsetx < 0 ? $offsetx*-1 : 0), ($offsety < 0 ? $offsety*-1 : 0), ($offsetx >=0 ? $offsetx : 0), ($offsety >=0 ? $offsety : 0), $cs['x'], $cs['y'], $cs['x'], $cs['y']);
			$this->current = $cropped_image;
		}
		

        // Resizes the image.
        public function resize ($newSize, $side = false) {

            
            // Load the dimensions for the current image.
            $currentSize = $this->getSize();

            

			if (is_array($newSize))
			{
				// Both sides specified
				foreach($newSize as $side => $size)
				{
					// Check if we recognize the unit.
		        	if (is_numeric($size)) {
		                // No unit specified, assume pixels. Do nothing.
		            } elseif (strpos($size, "%") !== false) {
		                // Calculate dimensions for the new image with the percentage.
		                $newSize[$side] = $currentSize[$side] * ($newSize[$side]/100);
		            } else {
		                // Not a recognized unit.
		                return false;
		            }
		        }
			}
			else
			{

				// Just one side specified
				
	            // Check if we recognize the unit.
	        	if (is_numeric($newSize) || strpos($newSize, 'px') !== false) {
	
	                // No unit specified, assume pixels.
	                $unit = "px";
	            } elseif (strpos($newSize, "%") !== false) {
	
	                // Unit specified as a percentage.
	                $unit = "%";
	            } else {
	
	                // Not a recognized unit.
	                return false;
	            }
	

	            // Decide what type of operation we are doing based on the unit.
				
	            if ($unit == "%") {
	
	                // Calculate dimensions for the new image with the percentage.
	                $newSize = array("x" => $currentSize["x"] * ($newSize/100), "y" => $currentSize["y"] * ($newSize/100));
	
	            } elseif ($unit == "px") {
	                // Calculate dimensions for the new image, depending on which axis
	                // we are resizing on.
	                if ($side == "x") {
	                    $newSize = array("x" => $newSize, "y" => $currentSize["y"] * ($newSize / $currentSize["x"]));
	                } elseif ($side == "y") {
	                    $newSize = array("x" => $currentSize["x"] * ($newSize / $currentSize["y"]), "y" => $newSize);
	                } else {
	                    return false;
	                }
	            }

			}

			// var_dump($newSize); exit;
			
            // Create the canvas for the resized image to go on.
            $newImage = imagecreatetruecolor($newSize["x"], $newSize["y"]);

            // Resize the image and place it on the canvas.
            $result = imagecopyresampled ($newImage, $this->current, 0, 0, 0, 0, $newSize["x"], $newSize["y"], $currentSize["x"], $currentSize["y"]);

            // If the resize was successful, set as current image and return.
            if ($result) {
                $this->current = $newImage;
                return true;
            } else {
                return false;
            }

        }

        // Returns the size of the current image as an x/y array.
        public function getSize() {
            return array("x" => imagesx($this->current), "y" => imagesy($this->current));
        }

        // Return the image file
        public function save($filename, $type = false, $quality = 75) {

            // Check if we have been sent a file type.
            if (!$type) {

                // Attempt to detect the file type from the filename extention.
                $type = substr($filename, -3, 3);
            }

            // Detect which type of image we are being asked to output.
            switch (strtolower($type)) {
                case "png":
                    $result = imagepng($this->current, $filename);
                    break;
                case "gif":
                    $result = imagegif($this->current, $filename);
                    break;
                case "wbmp":
                    $result = imagewbmp($this->current, $filename);
                    break;
                case "xbm":
                    $result = imagexbm($this->current, $filename);
                    break;
                case "jpg":
                case "jpeg":
                default:
                	imageinterlace($this->current, 1);
                    $result = imagejpeg($this->current, $filename, $quality);
                    break;
            }

            // Report on our success.
            return $result;
        }


        public function show ($type = false, $quality = 75) {
            // Check if we have been sent a file type.
            if (!$type) {

                // Attempt to detect the file type from the filename extention.
                $type = substr($this->filepath, -3, 3);
            }

            // Detect which type of image we are being asked to output.
            switch (strtolower($type)) {
                case "png":
					header('Content-type: image/png');
                    $result = imagepng($this->current);
                    break;
                case "gif":
					header("Content-type: image/gif");
                    $result = imagegif($this->current);
                    break;
                case "wbmp":
					header("Content-type: image/gif");
                    $result = imagewbmp($this->current);
                    break;
                case "xbm":
					header("Content-type: image/gif");
                    $result = imagexbm($this->current);
                    break;
                case "jpg":
                case "jpeg":
                default:
					header("Content-type: image/jpeg");
                	imageinterlace($this->current, 1);
                    $result = imagejpeg($this->current, NULL, $quality);
                    break;
            }

            // Report on our success.
            return $result;
        }

		// Create a reflection
		public function reflect($height = null, $fade_start = null, $fade_end = null, $tint = null)
		{
		
			/*
				-------------------------------------------------------------------
				Easy Reflections v3 by Richard Davey, Core PHP (rich@corephp.co.uk)
				Released 13th March 2007
		        Includes code submissions from Monte Ohrt (monte@ohrt.com)
		        -------------------------------------------------------------------
				You are free to use this in any product, or on any web site.
		        I'd appreciate it if you email and tell me where you use it, thanks.
				Latest builds at: http://reflection.corephp.co.uk
		        -------------------------------------------------------------------
				Converted to a method of Alex King's Image class by Jonathon Hill
				(jonathon@compwright.com), 05/02/2008
				-------------------------------------------------------------------
		
				This script accepts the following parameters:
				
				height	        optional	Height of the reflection (% or pixel value)
		        fade_start      optional    Start the alpha fade from whch value? (% value)
		        fade_end        optional    End the alpha fade from whch value? (% value)
		        tint            optional    Tint the reflection with this colour (hex)
			*/

			
			//	img (the image to reflect)
			$source_image = $this->current;
			
			
		    //    tint (the colour used for the tint, defaults to white if not given)
		    if (! ($tint))
		    {
		        $red = 127;
		        $green = 127;
		        $blue = 127;
		    }
		    else
		    {
		        //    Extract the hex colour
		        $hex_bgc = $tint;
		        
		        //    Does it start with a hash? If so then strip it
		        $hex_bgc = str_replace('#', '', $hex_bgc);
		        
		        switch (strlen($hex_bgc))
		        {
		            case 6:
		                $red = hexdec(substr($hex_bgc, 0, 2));
		                $green = hexdec(substr($hex_bgc, 2, 2));
		                $blue = hexdec(substr($hex_bgc, 4, 2));
		                break;
		                
		            case 3:
		                $red = substr($hex_bgc, 0, 1);
		                $green = substr($hex_bgc, 1, 1);
		                $blue = substr($hex_bgc, 2, 1);
		                $red = hexdec($red . $red);
		                $green = hexdec($green . $green);
		                $blue = hexdec($blue . $blue);
		                break;
		                
		            default:
		                //    Wrong values passed, default to white
		                $red = 127;
		                $green = 127;
		                $blue = 127;
		        }
		    }

		    
			//	height (how tall should the reflection be?)
			if (! is_null($height))
			{
				$output_height = $height;
				
				//	Have they given us a percentage?
				if (substr($output_height, -1) == '%')
				{
					//	Yes, remove the % sign
					$output_height = (int) substr($output_height, 0, -1);
		
					//	Gotta love auto type casting ;)
					if ($output_height == 100)
					{
		                $output_height = "0.99";
					}
					elseif ($output_height < 10)
		            {
		                $output_height = "0.0$output_height";
		            }
		            else
					{
						$output_height = "0.$output_height";
					}
				}
				else
				{
					$output_height = (int) $output_height;
				}
			}
			else
			{
				//	No height was given, so default to 50% of the source images height
				$output_height = 0.50;
			}
			
			
			// Fade start
			if (! is_null($fade_start))
			{
				if (strpos($fade_start, '%') !== false)
				{
					$alpha_start = str_replace('%', '', $fade_start);
					$alpha_start = (int) (127 * $alpha_start / 100);
				}
				else
				{
					$alpha_start = (int) $fade_start;
				
					if ($alpha_start < 1 || $alpha_start > 127)
					{
						$alpha_start = 80;
					}
				}
			}
			else
			{
				$alpha_start = 80;
			}
		
			
			
			// Fade end
			if (! is_null($fade_end))
			{
				if (strpos($fade_end, '%') !== false)
				{
					$alpha_end = str_replace('%', '', $fade_end);
					$alpha_end = (int) (127 * $alpha_end / 100);
				}
				else
				{
					$alpha_end = (int) $fade_end;
				
					if ($alpha_end < 1 || $alpha_end > 0)
					{
						$alpha_end = 0;
					}
				}
			}
			else
			{
				$alpha_end = 0;
			}
		
			
			
			
			/*
				----------------------------------------------------------------
				Ok, let's do it ...
				----------------------------------------------------------------
			*/
			
			//	How big is the image?
			$sz = $this->getSize();
			$width = $sz['x'];
			$height = $sz['y'];
			
			//	Calculate the height of the output image
			if ($output_height < 1)
			{
				//	The output height is a percentage
				$new_height = $height * $output_height;
			}
			else
			{
				//	The output height is a fixed pixel value
				$new_height = $output_height;
			}
		

			
			$source = $this->current;

			
			/*
				----------------------------------------------------------------
				Build the reflection image
				----------------------------------------------------------------
			*/
		
			//	We'll store the final reflection in $output. $buffer is for internal use.
			$output = imagecreatetruecolor($width, $new_height);
			$buffer = imagecreatetruecolor($width, $new_height);
		    
		    //  Save any alpha data that might have existed in the source image and disable blending
		    imagesavealpha($source, true);
		
		    imagesavealpha($output, true);
		    imagealphablending($output, false);
		
		    imagesavealpha($buffer, true);
		    imagealphablending($buffer, false);
		
			//	Copy the bottom-most part of the source image into the output
			imagecopy($output, $source, 0, 0, 0, $height - $new_height, $width, $new_height);
			
			//	Rotate and flip it (strip flip method)
		    for ($y = 0; $y < $new_height; $y++)
		    {
		       imagecopy($buffer, $output, 0, $y, 0, $new_height - $y - 1, $width, 1);
		    }
		
			$output = $buffer;
		
			/*
				----------------------------------------------------------------
				Apply the fade effect
				----------------------------------------------------------------
			*/
			
			//	This is quite simple really. There are 127 available levels of alpha, so we just
			//	step-through the reflected image, drawing a box over the top, with a set alpha level.
			//	The end result? A cool fade.
		
			//	There are a maximum of 127 alpha fade steps we can use, so work out the alpha step rate
		
			$alpha_length = abs($alpha_start - $alpha_end);
		
		    imagelayereffect($output, IMG_EFFECT_OVERLAY);
		
		    for ($y = 0; $y <= $new_height; $y++)
		    {
		        //  Get % of reflection height
		        $pct = $y / $new_height;
		
		        //  Get % of alpha
		        if ($alpha_start > $alpha_end)
		        {
		            $alpha = (int) ($alpha_start - ($pct * $alpha_length));
		        }
		        else
		        {
		            $alpha = (int) ($alpha_start + ($pct * $alpha_length));
		        }
		        
		        //  Rejig it because of the way in which the image effect overlay works
		        $final_alpha = 127 - $alpha;
		
		        //imagefilledrectangle($output, 0, $y, $width, $y, imagecolorallocatealpha($output, 127, 127, 127, $final_alpha));
		        imagefilledrectangle($output, 0, $y, $width, $y, imagecolorallocatealpha($output, $red, $green, $blue, $final_alpha));
		    }
		    
			/*
				----------------------------------------------------------------
				Save our final PNG
				----------------------------------------------------------------
			*/

		    
		    $this->current = $output;

		    return true;
			
		}

    }