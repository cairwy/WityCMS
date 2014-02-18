<?php 
/**
 * WConfig.php
 */

defined('IN_WITY') or die('Access denied');

/**
 * WConfig loads all configuration files, manages all configuration values.
 * 
 * @package System\WCore
 * @author xpLosIve
 * @author Johan Dufau <johan.dufau@creatiwity.net>
 * @version 0.4.0-22-11-2012
 */
class WConfig {
	const APPS_NODE = "apps";

	/**
	 * @var array Multidimensionnal array containing configurations sorted by type
	 * 
	 * No '.' in keys because it's a reserved character
	 */
	private static $configs = array();
	
	/**
	 * @var array List of loaded configuration files
	 */
	private static $files = array();

	/**
	 * @var array Stores modified configurations 
	 */
	private static $modified = array();
	
	/**
	 * Returns a configuration value.
	 * 
	 * @param  string $path     configuration path
	 * @param  mixed  $default  optional default value
	 * @return mixed  configuration value related to $path
	 */
	public static function get($path, $default = null) {
		$result = $default;
		
		// Config nodes path
		if ($nodes = explode('.', $path)) {
			$config = &self::$configs;
			$path_count = count($nodes) - 1;
			
			//In case of apps, we use SQL
			if($nodes[0] == self::APPS_NODE) {		
				if(isset($nodes[1]) && isset($nodes[2])) {
					$sql = self::getAppVar($nodes[1], $nodes[2], $default);
					return is_null($sql)?$default:$sql;
				}				
			}
			
			// Running through configs
			for ($i = 0; $i < $path_count; $i++) {
				if (isset($config[$nodes[$i]])) {
					$config = &$config[$nodes[$i]];
				} else {
					break;
				}
			}
			
			if (isset($config[$nodes[$i]])) {
				$result = $config[$nodes[$i]];
			}
		}
		
		return $result;
	}
	
	/**
	 * Assign a configuration value to a path.
	 * 
	 * 
	 * @param  string $path   configuration path
	 * @param  mixed  $value  configuration value
	 * @return mixed  configuration value
	 */
	public static function set($path, $value) {
		$nodes = explode('.', $path);
		
		$config = &self::$configs;
		$path_count = sizeof($nodes)-1;
		for ($i = 0; $i < $path_count; $i++) {
			if (!isset($config[$nodes[$i]])) {
				$config[$nodes[$i]] = array();
			}
			$config = &$config[$nodes[$i]];
		}
		
		$config[$nodes[$i]] = $value;
		
		// Notify configuration modification
		array_push(self::$modified, $nodes[0]);
	}
	
	/**
	 * Loads configuration's values from a file.
	 * 
	 * @param  string  $field configuration name
	 * @param  string  $file  configuration file
	 * @param  string  $type  file type
	 * @return boolean true if successful, false otherwise
	 */
	public static function load($field, $file, $type = '') {
		if (!is_file($file) || isset(self::$files[$field]) || strpos($field, '.') !== false) {
			return false;
		}
		
		// Find type using file extension
		if (empty($type)) {
			$type = substr($file, strrpos($file, '.') + 1);
		}
		
		switch(strtolower($type)) {
			case 'ini':
				self::$configs[$field] = parse_ini_file($file, true);
				break;
			
			case 'php':
				include_once $file;
				if (isset(${$field})) {
					self::$configs[$field] = ${$field};
				}
				unset(${$field});
				break;
			
			case 'xml':
				self::$configs[$field] = simplexml_load_file($file);
				break;
			
			case 'json':
				self::$configs[$field] = json_decode(file_get_contents($file), true);
				break;
			
			default:
				return false;
		}
		// Saving the file
		self::$files[$field] = array($file, $type);
		return true;
	}
	
	/**
	 * Destroys a configuration value.
	 * 
	 * @param string $path Configuration's path
	 */
	public static function clear($path) {
		$nodes = explode('.', $path);
		
		$config = &self::$configs;
		$path_count = sizeof($nodes)-1;
		$exists = true;
		for ($i = 0; $i < $path_count; $i++) {
			if (isset($config[$nodes[$i]])) {
				$config = &$config[$nodes[$i]];
			} else {
				$exists = false;
				break;
			}
		}
		
		if ($exists) {
			unset($config[$nodes[$i]]);
		}
		
		// Notifying configuration modification
		array_push(self::$modified, $nodes[0]);
	}
	
	/**
	 * Saves the specified configuration.
	 * 
	 * @param string $field Configuration's name
	 */
	public static function save($field, $file = '') {
		if (in_array($field, self::$modified)) {
			if($field == self::APPS_NODE) {
				$this->saveAppConfig();
			}
			
			if (isset(self::$files[$field])) { // File already defined
				list($file, $type) = self::$files[$field];
			} else if (!empty($file)) { // File is given by argument
				$type = substr($file, strrpos($file, '.')+1);
			}
			
			if (empty($file) || !is_writable(dirname($file))) {
				return false;
			}
			
			// Opening
			if (!($handle = fopen($file, 'w'))) {
				return false;
			}
			
			$data = "";
			switch (strtolower($type)) {
				case 'ini':
					foreach(self::$configs[$field] as $name => $value) {
						$data .= $name . ' = ' . $value ."\n";
					}
					break;
				
				case 'php':
					$data = "<?php\n"
						. "\n"
						. "/**\n"
						. " * ".$field." configuration file\n"
						. " */\n"
						. "\n"
						. "$".$field." = ".var_export(self::$configs[$field], true).";\n"
						. "\n"
						. "?>\n";
					break;
				
				case 'xml':
					$data = '<?xml version="1.0" encoding="utf-8"?>' ."\n"
						  . '<configs>' ."\n";
					
					foreach(self::$configs[$field] as $name => $value) {
						$data .= '	<config name="' . $name . '">' . $value . '</config>' ."\n";
					}
					
					$data = '</configs>' ."\n";
					break;
				
				case 'json':
					$data = json_encode(self::$configs[$field]);
					break;
			}
			
			// Writing
			fwrite($handle, $data);
			fclose($handle);
			
			// Change Modification rights
			$chmod = @chmod($file, 0777);
		}
	}
	
	/**
	 * Unloads a configuration.
	 * 
	 * @param string $field Configuration's name
	 */
	public static function unload($field) {
		unset(self::$configs[$field], self::$files[$field]);
	}
	
	private static $db;
	private static $stmt_add;
	private static $stmt_select;
	private static $stmt_update;
	/**
	 * Unloads a configuration.
	 * 
	 * @param string $field Configuration's name
	 */
	public static function getAppVar($app, $key, $default = null) {		
		//init DB if required
		if(self::$db == null) {
			self::init_db();
		}
		
		$db 				= self::$db;
		$stmt_select = self::$stmt_select;
		$config 			= self::$configs;
		
		//check in temp values
		if(isset($config[self::APPS_NODE][$app])) {
			if(isset($config[self::APPS_NODE][$app][$key])) {
				return is_null($config[self::APPS_NODE][$app][$key])?$default:$config[self::APPS_NODE][$app][$key];
			} else {
				self::$config[APPS_NODE][$app][$key] = null;
				return $default;
			}
		}
		
		//Try to do the request
		$stmt_select->bindParam(':app', $app);
		$stmt_select->execute();
		
		if($stmt_select->rowCount() != 0) {	
			$result =  $stmt_select->fetchAll();
			foreach($result as $values) {
				self::$configs[self::APPS_NODE][$app][$values['key']] = $values['value'];
 			}
		}
		
		//if found in the database, we retrun the value
		if(isset($config[self::APPS_NODE][$app][$key])) {
			return $config[self::APPS_NODE][$app][$key];
		}
		
		//In case no variable were found, check the manifest
		$controller = WRetriever::getController($app, true);
		if ($controller instanceof WController) {
			$manifest = $controller->getManifest();
			self::$configs[self::APPS_NODE][$app] = $manifest['app_config'];
			self::saveAppConfig();
			if(isset($manifest['app_config'][$key])) {			
				return $manifest['app_config'][$key];
			} else {
				self::$configs[self::APPS_NODE][$app][$key] = null;
				return $default;
			}
		} else {
			self::$configs[self::APPS_NODE][$app][$key] = null;
			return $default;
		}
	}
	
	/**
	 * Unloads a configuration.
	 * 
	 * @param string $field Configuration's name
	 */
	public static function saveAppConfig() {		
		//init DB if required
		if(self::$db == null) {
			self::init_db();
		}
		
		$stmt_update 	= self::$stmt_update;
		$stmt_add 		= self::$stmt_add;
		$stmt_select 	= self::$db->prepare('SELECT id, `key`, app FROM apps_config');
		$stmt_select->execute();
		$present_variable = $stmt_select->fetchAll();
		foreach($present_variable as $var) {
			$id_per_key[$var['app']][$var['key']] = $var['id'];
		}

		foreach(self::$configs[self::APPS_NODE] as $app => $array) {
			$stmt_add->bindParam(':app', $app);
			
			foreach($array as $key => $value) {
				if(isset($id_per_key[$app][$key])) {
					$stmt_update->bindParam(':id', $id);
					$id = $id_per_key[$app][$key];
					$stmt_update->bindParam(':value', $value);
					$stmt_update->execute();
				} else {
					$stmt_add->bindParam(':key', $key);
					$stmt_add->bindParam(':value', $value);
					$stmt_add->execute();
				}
			}
		}
	}
	
	private static function init_db() {
		self::$db = WSystem::getDB();
		self::$db->declareTable('apps_config');
		self::$stmt_select = self::$db->prepare('
			SELECT value,`key`,app FROM apps_config WHERE app = :app');
		self::$stmt_update = self::$db->prepare('UPDATE apps_config SET value = :value WHERE id = :id');
		self::$stmt_add = self::$db->prepare('INSERT INTO apps_config(app, `key`, value) VALUES(:app, :key, :value)');
	}
}

?>
