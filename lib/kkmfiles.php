<?php
/**
 * kkm
 * File and upload handler
 * 
 * @author Attila Wagner
 */

class kkm_files {
	
	/**
	 * Maximum allowed size for documents.
	 * @var integer
	 */
	const MAX_DOCUMENT_SIZE = 10485760; //10M
	
	/**
	 * Saves an uploaded file as a document for a given composition.
	 * 
	 * @param array $file_arr A single item in $_FILES, containing the data for the uploaded document.
	 * @param integer $comp_id Compositiond ID in the database.
	 * @param integer $user_id Optional. If not provided, the current user's id is used.
	 * @param integer $previous_version Optional. If this upload is a newer version
	 * of an already stored document, its docid should be provided.
	 * @return boolean|integer The path (relative to the avatars dir) of the processed file
	 * on success, or false if there was an error.
	 */
	public static function save_document($file_arr, $comp_id, $user_id = null, $previous_version = null) {
		global $current_user, $wpdb;
		
		//Return if there was an error during the upload
		if ($file_arr['error'] != 0 || !@is_uploaded_file($file_arr['tmp_name'])) {
			return false;
		}
		
		//Get user ID
		if ($user_id == null) {
			$user_id = $current_user->ID;
		}
		
		$size = filesize($file_arr['tmp_name']);
		$safe_name = self::get_safe_name($file_arr['name']);
		
		//Insert into the DB an unfinished document
		$wpdb->flush();
		$wpdb->query(
			$wpdb->prepare(
				'insert into `kkm_documents` (`uploader`, `date`, `compid`, `filename`, `filesize`) values (%d, %s, %d, %s, %d);',
				$user_id,
				current_time('mysql'),
				$comp_id,
				$safe_name,
				$size
			)
		);
		$doc_id = $wpdb->insert_id;
		
		if ($doc_id == null) {
			kkm::redirect_to_error_page();
		}
		
		/*
		 * Set previous and first version for the document.
		 * If this is the first version, then set firstver to its own docid.
		 */
		if ($previous_version) {
			//Get first version from previous version
			$first_version = $wpdb->get_var(
				$wpdb->prepare(
					'select `firstver` from `kkm_documents` where `docid`=%d',
					$previous_version
				)
			);
			
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_documents` set `oldver`=%d, `firstver`=%d where `docid`=%d limit 1;',
					$previous_version,
					$first_version,
					$doc_id
				)
			);
			
			//Make the previous version inactive
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_documents` set `activever`=0 where `docid`=%d limit 1;',
					$previous_version
				)
			);
			
		} else {
			//This is the first version
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_documents` set `firstver`=%d where `docid`=%d limit 1;',
					$doc_id,
					$doc_id
				)
			);
		}
		
		//Move file
		self::create_dir_struct(self::get_dir_for_id($doc_id));
		$doc_path = WP_CONTENT_DIR . '/' . self::get_document_path($doc_id, $safe_name);
		
		if (@move_uploaded_file($file_arr['tmp_name'], $doc_path)) {
			$stat = @stat(WP_CONTENT_DIR);
			$mode = $stat['mode'] & 0000666;
			@chmod($doc_path, $mode);
			
			return $doc_id;
		} else {
			$wpdb->query(
				$wpdb->prepare(
					'delete from `kkm_documents` where `docid`=%d limit 1;',
					$doc_id
				)
			);
			//Restore previous version
			if ($previous_version) {
				$wpdb->query(
					$wpdb->prepare(
						'update `kkm_documents` set `activever`=1 where `docid`=%d limit 1;',
						$previous_version
					)
				);
			}
		}
		return false;
	}
	
	/**
	 * Creates the directory given in $path, and creates every
	 * parent if they do not already exist.
	 * @param string $path Path of the directory to create,
	 * relative to WP_CONTENT_DIR, without slash at the beginning.
	 * @return boolean True, if the target directory exists
	 * at the end of the function, false otherwise.
	 */
	private static function create_dir_struct($path) {
		//Load permissions from the wp-content directory
		$stat = @stat(WP_CONTENT_DIR);
		$mode = $stat['mode'] & 0000666;
	
		$abs_path = WP_CONTENT_DIR.'/'.$path;
		@mkdir($abs_path, $mode, true);
		return is_dir($abs_path);
	}
	
	/**
	 * Checks and escapes the filename to be safe to store in the filesystem.
	 * Longer filenames also get truncated.
	 * @param string $name Original file name.
	 * @return string Safe file name used to store the attachment.
	 */
	public static function get_safe_name($name) {
		//Remove accents
		$name = remove_accents($name);
		
		//Separate name and extension, extension starts after the last dot in the filename
		if (preg_match('/^(.*)\.(.*+)$/', $name, $regs)) {
			$name = $regs[1];
			$ext = $regs[2];
		} else {
			//The filename does not contain an extension
			$ext = '';
		}
	
		//Remove special chars
		$sane_name = sanitize_title_with_dashes($name, null, 'save');
		$sane_ext = sanitize_title_with_dashes($ext, null, 'save');
	
		//Truncate length if needed
		$max_len = 50;
		if (strlen($sane_name) + strlen($sane_ext) + 1 > $max_len) {
			//Extension truncated to 15 chars
			$sane_ext = substr($sane_ext, 0, 15);
			$sane_name = substr($sane_name, 0, $max_len-strlen($ext)-1);
		}
		
		if (!empty($sane_ext)) {
			//The filename has an extension
			return $sane_name.'.'.$sane_ext;
		} else {
			//Doesn't have an extension
			return $sane_name;
		}
	}
	
	/**
	 * Returns the path to the document.
	 * Documents are clustered into directories of 100,
	 * which dirs are further clustered.
	 * 
	 * Example paths for ID=1 and ID=15984:
	 * kkm/docs/00/00/000001-safe-name.ext
	 * kkm/docs/01/59/015984-safe-name.ext
	 * 
	 * @param integer $doc_id Document ID from the DB.
	 * @param string $safe_name The sanitized filename (as stored in the filesystem).
	 * @return string Path relative to WP_CONTENT_DIR or WP_CONTENT_URL.
	 * @see kkm_files::create_document_path
	 */
	public static function get_document_path($doc_id, $safe_name) {
		$file_id = sprintf('%06d', $doc_id);
		$name = $file_id . '-' . $safe_name;
		$dir = self::get_dir_for_id($doc_id);
		return $dir.$name;
	}
	
	/**
	 * Returns the path to the document, and creates the
	 * containing directory hierarchy if needed.
	 *
	 * @param integer $doc_id Document ID from the DB.
	 * @param string $safe_name The sanitized filename (as stored in the filesystem).
	 * @return string|boolean Path relative to WP_CONTENT_DIR or WP_CONTENT_URL pointing
	 * to the document or false if an error occurred.
	 * @see kkm_files::get_document_path
	 */
	public static function create_document_path($doc_id, $safe_name) {
		$dir = self::get_dir_for_id($doc_id);
		if (!self::create_dir_struct($dir)) {
			return false;
		}
		return self::get_document_path($doc_id, $safe_name);
	}
	
	/**
	 * Returns the path to the directory holding the given document.
	 * 
	 * @param integer $doc_id Document ID from the DB.
	 * @return string The path relative to WP_CONTENT_DIR.
	 */
	private static function get_dir_for_id($doc_id) {
		$file_id = sprintf('%06d', $doc_id);
		return 'kkm/docs/'.substr($file_id, 0, 2) . '/' . substr($file_id, 2, 2) . '/';
	}
	
	/**
	 * Deletes a document from the filesystem, and marks it as deleted in the database (when $purge is false).
	 * 
	 * When $purge is set to true, the complete DB record gets deleted too. This method should be
	 * used only when the entire document tree gets deleted (other versions, converted documents, etc).
	 * 
	 * 
	 * @param integer $doc_id Document ID.
	 * @param boolean $purge If false, the DB record is only marked as deleted but doesn't get removed.
	 * @return boolean True if the document was deleted successfully, false if an error occurred.
	 */
	public static function delete_document($doc_id, $purge = false) {
		global $wpdb;
		
		//Load filename
		$safe_name = $wpdb->get_var(
			$wpdb->prepare(
				'select `filename` from `kkm_documents` where `docid`=%d limit 1;',
				$doc_id
			)
		);
		if (empty($safe_name)) {
			return false;
		}
		
		//Delete file
		$file_path = WP_CONTENT_DIR.'/'.self::get_document_path($doc_id, $safe_name);
		if (is_file($file_path) && !@unlink($file_path)) {
			return false;
		}
		
		//Delete data from DB
		if ($purge) {
			//If this is the first version of a doc-tree, null out the field.
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_documents` set `firstver`=null where `docid`=%d and `firstver`=%d limit 1;',
					$doc_id,
					$doc_id
				)
			);
			//Delete
			$wpdb->query(
				$wpdb->prepare(
					'delete from `kkm_documents` where `docid`=%d limit 1;',
					$doc_id
				)
			);
			if (!empty($wpdb->last_error)) {
				return false;
			}
		} else {
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_documents` set `deleted`=1 where `docid`=%d limit 1;',
					$doc_id
				)
			);
		}
		return true;
	}
	
	/**
	 * Returns the absolute path to the temporary directory.
	 * Converters may use this directory to store intermediate files,
	 * and the framework may store output generated by a handler here.
	 * 
	 * @return string Absolute path to the temporary directory.
	 */
	public static function get_temp_dir() {
		return WP_CONTENT_DIR.'/kkm/temp/';
	}
	
	/**
	 * Streams the given file to the user.
	 * If http_send_file() is available (pre PHP 5.3 with PECL extension, or PHP 5.3+),
	 * it will be used, or a PHP 5.0 compatible script will handle it.
	 * @param string $path File path, should be absolute.
	 * @param string $name Original file name. The browser will suggest this name in the Save As dialog.
	 */
	public static function stream_file($path, $name) {
		if (function_exists('http_send_file')) {
			self::stream_file_with_http_send_file($path, $name);
		} else {
			self::stream_file_with_script($path, $name);
		}
	}
	
	/**
	 * Streams the file with the PECL extension.
	 * (Built in function for PHP 5.3+)
	 * @param string $path File path, should be absolute.
	 * @param string $name Original file name. The browser will suggest this name in the Save As dialog.
	 */
	private static function stream_file_with_http_send_file($path, $name) {
		$throttle_sleep = 0.1;
		$throttle_buffer = 4096;
	
		$file_type = wp_check_filetype($path);
		http_send_content_disposition($name);
		http_send_content_type($file_Type['type']);
		http_throttle($throttle_sleep, $throttle_buffer);
		http_send_file($path);
	}
	
	/**
	 * Streams the file with a custom handler script that
	 * provides the same functionality as the http_send_file() function.
	 * @param string $path File path, should be absolute.
	 * @param string $name Original file name. The browser will suggest this name in the Save As dialog.
	 */
	private static function stream_file_with_script($path, $name) {
		$multipart_boundary = "KKM_MULTIPART_DOCUMENT";
	
		$throttle_sleep = 0.1;
		$throttle_buffer = 4096;
	
		$file_size = filesize($path);
	
		/*
		 * Get range parameter
		 * (Used when continuing downloads.)
		 */
		$ranges = array();
		if (isset($_SERVER['HTTP_RANGE'])) {
			if (!preg_match('^bytes=\d*-\d*(,\d*-\d*)*$', $_SERVER['HTTP_RANGE'])) {
				//Invalid request header
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				header('Content-Range: bytes */' . $file_size); // Required in 416.
				die();
			}
				
			//Parse intervals
			$range_intervals = explode(',', substr($_SERVER['HTTP_RANGE'], 6));
			foreach ($range_intervals as $range) {
				list($start, $end) = explode('-', $range);
				if (empty($start)) {
					$start = 0;
				}
				if (empty($end) || $end > $file_size - 1) {
					$end = $file_size - 1;
				}
					
				if ($start > $end) {
					header('HTTP/1.1 416 Requested Range Not Satisfiable');
					header('Content-Range: bytes */' . $file_size); // Required in 416.
					die();
				}
	
				$ranges[] = array($start, $end);
			}
		}
	
		/*
		 * Send headers
		 */
		$file_type = wp_check_filetype($path);
		
		header('Cache-Control: max-age=30' );
		header('Content-Type: '.$file_type['type']);
		header("Content-Disposition: attachment; filename=\"{$name}\"");
		header('Content-Length: '.$file_size);
		header('Pragma: public');
		header('Accept-Ranges: bytes');
		
		if (!empty($ranges)) {
			header("HTTP/1.0 206 Partial Content");
			header("Status: 206 Partial Content");
			
			if (count($ranges) == 1) {
				//Single range
				$start = $ranges[0][0];
				$end = $ranges[0][1];
				header("Content-Range: bytes ${start}-${end}/${file_size}");
				
			} else {
				//Multiple ranges
				header('Content-Type: multipart/byteranges; boundary='.$multipart_boundary);
				
			}
		}
		
		/*
		 * Send file
		 */
		$file = @fopen($path, 'rb');
		if (empty($ranges)) {
			//Send the whole file
			self::stream_file_segment($file, 0, $file_size-1, $throttle_sleep, $throttle_buffer);
			
		} elseif (count($ranges) == 1) {
			//There's only one range, send it
			self::stream_file_segment($file, $ranges[0][0], $ranges[0][1], $throttle_sleep, $throttle_buffer);
			
		} else {
			//Multiple ranges, send as multipart
			foreach ($ranges as $range) {
				list($start, $end) = $range;
				//Part header
				echo("\n");
				echo('--'.$multipart_boundary."\n");
				echo('Content-Type: '.$file_type['type']);
				echo("Content-Range: bytes ${start}-${end}/${file_size}");
				
				//Send segment
				self::stream_file_segment($file, $start, $end, $throttle_sleep, $throttle_buffer);
				
				//Close part
				echo("\n");
				echo('--'.$multipart_boundary."--\n");
			}
			
		}
		@fclose($file);
		
	}
	
	/**
	 * Streams a part of the given file (script method).
	 * @param resource $file File handler.
	 * @param integer $start First byte to send (start of interval, inclusive).
	 * @param integer $end Last byte to send (end of interval, inclusive).
	 * @param integer $throttle_sleep Sleep time in seconds, as used in http_throttle().
	 * @param integer $throttle_buffer Buffer size in bytes, as used in http_throttle().
	 */
	private static function stream_file_segment($file, $start, $end, $throttle_sleep, $throttle_buffer) {
		@fseek($file, $start);
		$remaining = $end - $start;
		while (!connection_aborted() && $remaining > 0) {
			$read_size = min($throttle_buffer, $remaining);
			echo @fread($file, $read_size);
			$remaining -= $read_size;
			sleep($throttle_sleep);
		}
	}
}
?>