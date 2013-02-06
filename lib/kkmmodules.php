<?php
/**
 * kkm
 * Module handling
 * 
 * Modules are loaded automatically when needed.
 * 
 * @author Attila Wagner
 */

class kkm_modules {
	
	/**
	 * Holds parser module information arrays.
	 * @var array
	 */
	private static $parser_modules;
	
	/**
	 * Holds diff module information arrays.
	 * @var array
	 */
	private static $diff_modules;
	
	/**
	 * Holds converter module information arrays.
	 * @var array
	 */
	private static $converter_modules;
	
	/**
	 * Flag that indicates whether the modules have been loaded.
	 * @var boolean
	 */
	private static $modules_loaded;
	
	
	/**
	 * Loads modules from the kkm/modules directory and stores information
	 * about them into the static $*_modules class variables.
	 * 
	 * Modules are static classes, and we load them by comparing the declared classes
	 * list before and after the inclusion of the files.
	 */
	private static function load_modules() {
		if (!self::$modules_loaded) {
			//Load interfaces
			self::load_interfaces();
			
			//Store already declared classes so we can filter them out later.
			$unrelated = get_declared_classes();
			
			//Load parsers
			self::load_modules_from_dir(KKM_PLUGIN_DIR.'modules/parser/');
			$loaded_classes = get_declared_classes();
			self::$parser_modules = array_values(array_diff($loaded_classes, $unrelated));
			
			$unrelated = $loaded_classes;
			
			//Load converters
			self::load_modules_from_dir(KKM_PLUGIN_DIR.'modules/converter/');
			$loaded_classes = get_declared_classes();
			self::$converter_modules = array_values(array_diff($loaded_classes, $unrelated));
			
			$unrelated = $loaded_classes;
			
			//Load diffs
			self::load_modules_from_dir(KKM_PLUGIN_DIR.'modules/diff/');
			$loaded_classes = get_declared_classes();
			self::$diff_modules = array_values(array_diff($loaded_classes, $unrelated));
			
			self::$modules_loaded = true;
		}
	}
	
	/**
	 * Includes the interface definitions.
	 */
	private static function load_interfaces() {
		require_once(KKM_PLUGIN_DIR.'modules/parser-module-interface.php');
		require_once(KKM_PLUGIN_DIR.'modules/converter-module-interface.php');
		require_once(KKM_PLUGIN_DIR.'modules/diff-module-interface.php');
	}
	
	/**
	 * Loads modules from a directory.
	 * This is called for each module type separately.
	 * 
	 * A module must have a module.php file in its directory,
	 * as that will be included. In those files every module
	 * defines a static class implementing the proper interface.
	 * 
	 * @param string $path Absolute path of the directory to load.
	 * Trailing / needed.
	 */
	private static function load_modules_from_dir($path) {
		$dir = scandir($path);
		foreach ($dir as $f) {
			if (substr($f, 0, 1) != '.' && is_dir($path.$f)) {
				$module_file = $path.$f.'/module.php';
				if (is_readable($module_file)) {
					include_once($module_file);
				}
			}
		}
	}
	
	/**
	 * Returns parser classes for the given type.
	 * 
	 * @param string $format Document type, optional.
	 * If not set, parsers will be returned without filtering.
	 * @return array Array of class names for the parser modules.
	 */
	public static function get_parsers($format = null) {
		self::load_modules();
		if (is_null($format)) {
			//Return every module
			return self::$parser_modules;
		} else {
			//Give back only relevant modules
			$ret = array();
			foreach (self::$parser_modules as $module) {
				$accepted_formats = call_user_func(array($module, 'get_accepted_formats'));
				if (in_array($format, $accepted_formats)) {
					$ret[] = $module;
				}
			}
			return $ret;
		}
	}
	
	/**
	 * Returns converter classes with that support the given format conversion.
	 * Both parameters are optional. If one is null, no filtering will be done
	 * on that end of the conversion. If both parameters are null, every converter
	 * will be returned.
	 * 
	 * @param string $format_from Document type to convert from. Optional.
	 * @param string $format_to Document type to convert into. Optional.
	 */
	public static function get_converters($format_from = null, $format_to = null) {
		self::load_modules();
		if (is_null($format_from) && is_null($format_to)) {
			//Return every module without filtering
			return self::$converter_modules;
		} else {			
			/*
			 * Load conversion pairs for modules.
			 * Array key will be the module name, value will be the
			 * return value of get_conversion_formats.
			 */
			$module_pairs = array();
			foreach (self::$converter_modules as $module) {
				$conversion_formats = call_user_func(array($module, 'get_conversion_formats'));
				$module_pairs[$module] = $conversion_formats;
			}
			
			/*
			 * Filter.
			 */
			$ret = array();
			$from_supported = array();
			//Loop through modules.
			foreach ($module_pairs as $module =>$conversion_formats) {
				//Loop through the conversion format pairs supported by the module.
				foreach ($conversion_formats as $format_pair) {
					$from_ok = ($format_from == null || $format_from == $format_pair[0]);
					$to_ok = ($format_to == null || $format_to == $format_pair[1]);
					if ($from_ok && $to_ok) {
						$ret[] = $module;
					}
				}
			}
			
			return $ret;
		}
	}
	
	/**
	 * Returns diff classes for the given type.
	 *
	 * @param string $format Document type, optional.
	 * If not set, diff modules will be returned without filtering.
	 * @return array Array of class names for the diff modules.
	 */
	public static function get_differs($format = null) {
		self::load_modules();
		if (is_null($format)) {
			//Return every module
			return self::$diff_modules;
		} else {
			//Give back only relevant modules
			$ret = array();
			foreach (self::$diff_modules as $module) {
				$accepted_formats = call_user_func(array($module, 'get_accepted_formats'));
				if (in_array($format, $accepted_formats)) {
					$ret[] = $module;
				}
			}
			return $ret;
		}
	}
	
	/**
	 * Validates the file with the given parser modules.
	 * All modules will be called until one returns an error, or there's no more module.
	 * 
	 * @param string $path Absolute path of the document.
	 * @param array $parsers Array of parser classes to use. Should be the result of get_parsers().
	 * @return array Array of aggregated messages from all validators. If the file couldn't be validated,
	 * null will be returned. For clean and valid files an empty array is the result.
	 */
	public static function validate_document($path, $parsers) {
		$results = array(); //Aggregated results of the validators.
		$validated = false; //Was there at least one parser with an impleneted validate() function?
		
		//Call the validator method of each parser module
		foreach ($parsers as $parser) {
			$res = call_user_func(array($parser, 'validate'), $path);
				
			//For unimplemented validators $res will be null
			if (is_array($res)) {
				$validated = true;
		
				//Loop through messages
				$has_error = false;
				foreach ($res as $msg) {
					$results[] = $msg;
					if ($msg[0] == 'error') {
						$has_error = true;
					}
				}
		
				//If a validator reports an error, stop calling the others.
				if ($has_error) {
					break;
				}
			}
		}
		
		if ($validated) {
			return $results;
		} else {
			return null;
		}
	}
	
	/**
	 * Calls the parser modules to generate a preview.
	 * All modules will be called until one returns a non-empty string.
	 * 
	 * @param string $path Absolute path of the document.
	 * @param array $parsers Array of parser classes to use. Should be the result of get_parsers().
	 * @return string HTML fragment for preview. If a preview cannot be generated, an empty string will be returned.
	 */
	public static function get_preview($path, $parsers) {
		foreach ($parsers as $parser) {
			$result = call_user_func(array($parser, 'get_preview'), $path);
			if (!empty($result)) {
				return $result;
			}
		}
		
		return ''; //No preview was generated.
	}
	
	/**
	 * Calls the parser modules to load metadata from the document.
	 * The aggregated results of the parsers will be returned.
	 * 
	 * In the returned associative array, every value will be an array as opposed to the
	 * return value of the module where singe values for a given key may be returned as-is.
	 * An example of a valid return value:
	 * array(
	 *   'composer' => array('Jonathan Davis', 'Richard Gibbs'),
	 *   'title' => array('Not meant for me')
	 * )
	 * 
	 * @param string $path Absolute path of the document.
	 * @param array $parsers Array of parser classes to use. Should be the result of get_parsers().
	 * @return array Metadata loaded from the file.
	 * @see kkm_parser_module::get_metadata()
	 */
	public static function get_metadata($path, $parsers) {
		$aggregated = array();
		
		/*
		 * Query every parser module
		 */
		foreach ($parsers as $parser) {
			$result = call_user_func(array($parser, 'get_metadata'), $path);
			if (!empty($result)) {
				
				foreach ($result as $key => $value) {
					if (!array_key_exists($key, $aggregated)) {
						//Add the key to the array
						$aggregated[$key] = array();
					}
					
					//Cast atomic values into an array
					if (!is_array($value)) {
						$value = array($value);
					}
					
					//Append to the aggregated result
					$aggregated[$key] = array_merge($aggregated[$key], $value);
				}
				
			}
		}
		
		
		/*
		 * Clean up duplicates
		 */
		$clean = array();
		foreach ($aggregated as $key => $value) {
			$clean[$key] = array_unique($value);
		}
		
		return $clean;
	}
	
	/**
	 * Calls the save_metadata() function on each given parser to
	 * write the given meta into the file.
	 * 
	 * @param string $path Absolute path of the document.
	 * @param array $parsers Array of parser classes to use. Should be the result of get_parsers().
	 * @param array $meta Associative array containing the metadata.
	 * @see kkm_parser_module::save_metadata()
	 */
	public static function save_metadata($path, $parsers, $meta) {
		foreach ($parsers as $parser) {
			call_user_func($parser, 'save_metadata', $path, $meta);
		}
	}
	
	/**
	 * Returns the name and description of a module.
	 * 
	 * @param string $module Module class name.
	 */
	public static function get_module_name($module) {
		return call_user_func(array($module, 'get_name'));
	}
	
	/**
	 * Returns the result of the option validation of the given converter module.
	 * 
	 * @param string $module Converter module class name.
	 * @param string $path Absolute path of the document.
	 * @param array $options Options to validate (coming from the user).
	 * @return array Array of messages returned by the validator.
	 * If no errors occurred, an empty array is returned.
	 */
	public static function validate_options($module, $path, $options) {
		$ret = call_user_func(array($module, 'validate_conversion_options'), $path, $options);
		if (is_array($ret)) {
			return $ret;
		}
		return array();
	}
	
	/**
	 * Returns the file extension that belongs to the output file
	 * generated by the specified converter module.
	 *
	 * @param string $module Converter module class name.
	 */
	public static function get_target_extension($module) {
		return call_user_func(array($module, 'get_target_extension'));
	}
	
	/**
	 * Calls the conversion function of the given converter module.
	 * Messages generated by the converter will be saved into the database.
	 * The log id will be returned. If an error occurred, the destination file gets deleted.
	 *
	 * @param string $module Converter module class name.
	 * @param integer $source_doc_id Document ID of the source.
	 * @param string $source_path Absolute path of the source file.
	 * @param integer $dest_doc_id Document ID of the source.
	 * @param string $target_path Absolute path of the destination file.
	 * @param array $options Converter options.
	 * @param integer $user User ID, optional. If provided, the conversion will be logged as user action.
	 * @return integer ID of the log in the database.
	 */
	public static function convert_document($module, $source_doc_id, $source_path, $dest_doc_id, $target_path, $options, $user = null) {
		global $wpdb;
		$messages = call_user_func(array($module, 'convert'), $source_path, $target_path, $options);
		
		$max_message_level = 0;
		$message_levels = array('note'=>1, 'warning'=>2, 'error'=>3); //Lookup array
		if (is_array($messages)) {
			foreach ($messages as $msg) {
				$max_message_level = max(
					$max_message_level,
					$message_levels[$msg[0]]
				);
			}
		} else {
			$messages = array();
		}
		
		//Create log record
		$wpdb->query(
			$wpdb->prepare(
				'insert into `kkm_conversion_logs`
				(`source`,`target`,`date`,`options`,`messages`,`maxmsglvl`)
				values (%d, %d, %s, %s, %s, %d);',
				$source_doc_id,
				$dest_doc_id,
				current_time('mysql'),
				serialize($options),
				serialize($messages),
				$max_message_level
			)
		);
		$log_id = $wpdb->insert_id;
		
		if (!is_null($user) && !is_null($log_id)) {
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_conversion_logs` set `user`=%d where `logid`=%d limit 1;',
					$user,
					$log_id
				)
			);
		}
		
		/*
		 * Delete document if there was an error,
		 * or update the database record if the conversion was successfull.
		 */
		if ($max_message_level == $message_levels['error']) {
			kkm_files::delete_document($dest_doc_id);
		} else {
			
			/*
			 * Get format tag of the source document, and get
			 * the first conversion pair that has this format as the input.
			 * The output format will be saved to the new document.
			 */
			$source_format_tag = kkm_tags::get_format_tag_for_document($source_doc_id);
			$source_format = $source_format_tag['name'];
			
			$converter_format_pairs = call_user_func(array($module, 'get_conversion_formats'));
			$target_format = '';
			foreach ($converter_format_pairs as $pair) {
				if ($pair[0] == $source_format) {
					$target_format = $pair[1];
					break;
				}
			}
			
			/*
			 * Save tag
			 */
			//Create taglist structure like the one returned by the tagging panel
			$taglist_array = array(
				json_encode(array(kkm_tags::TYPE_FORMAT, $target_format))
			);
			$taglist = json_encode($taglist_array);
			kkm_tags::save_tags_for_doc($dest_doc_id, $taglist);
			
			//Update document record
			$wpdb->query(
				$wpdb->prepare(
					'update `kkm_documents` set `complete`=1, `filesize`=%d where `docid`=%d limit 1;',
					filesize($target_path),
					$dest_doc_id
				)
			);
		}
		
		return $log_id;
	}
}
?>