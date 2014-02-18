<?php
/**
 * User Application - Admin Model
 */

defined('WITYCMS_VERSION') or die('Access denied');

/**
 * Include Front Model for inheritance
 */
include_once APPS_DIR.'user'.DS.'front'.DS.'model.php';

/**
 * UserAdminModel is the Admin Model of the User Application.
 * 
 * @package Apps\User\Admin
 * @author Johan Dufau <johan.dufau@creatiwity.net>
 * @version 0.4.0-15-02-2013
 */
class ConfigAdminModel {
	private $THEMES_DIR;
	private $APPS_DIR;
	private $CONFIG_DIR;
	
	private $EXCLUDED_THEMES = array('system', 'admin-bootstrap');
	private $EXCLUDED_APPS = array('admin');
	private $EXCLUDED_DIRS = array('.', '..');
	
	public function __construct() {
		$this->THEMES_DIR = "themes";
		$this->APPS_DIR = "apps";
		$this->CONFIG_DIR = "system".DS."config";
	}
	
	public function getThemes() {
		if ($result = scandir($this->THEMES_DIR)) {
			foreach ($result as $key => $value) {
				if (in_array($value, $this->EXCLUDED_THEMES) || !is_dir($this->THEMES_DIR.DS.$value) || in_array($value, $this->EXCLUDED_DIRS)) {
					unset($result[$key]);
				}
			}
			$result[] = "_blank";
		}
		
		return $result;
	}
	
	public function saveWityCMS(array $params) {
		WConfig::set('config.base', trim($params['base'], '/'));
		WConfig::set('config.site_name', $params['site_name']);
		WConfig::set('config.theme', $params['theme']);
		WConfig::set('config.lang', $params['lang']);
		WConfig::set('config.timezone', 'Europe/Paris');
		WConfig::set('config.email', $params['email']);
		WConfig::set('config.debug', false);
		WConfig::save('config', CONFIG_DIR.'config.php');
	}
}

?>
