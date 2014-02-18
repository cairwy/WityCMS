<?php
/**
 * User Application - Admin Controller
 */

defined('WITYCMS_VERSION') or die('Access denied');

include_once 'helpers'.DS.'WForm'.DS.'WForm.php';

/**
 * UserAdminController is the Admin Controller of the User Application.
 * 
 * @package Apps\User\Admin
 * @author Johan Dufau <johan.dufau@creatiwity.net>
 * @version 0.4.0-26-04-2013
 */
class ConfigAdminController extends WController {
	/**
	* Config WityCMS
	**/	
	protected function witycms(array $params) {
			// Loading route config values		
		$config = WConfig::get('config');
		
		if (WRequest::hasData()) {
			$received = WRequest::getAssoc(array('save', 'base', 'site_name', 'theme', 'lang', 'timezone', 'email', 'debug'));
			
			$validate_data = $this->checkData($received);
			
			if($validate_data) {
				if($received['save']) {
					$this->model->saveWityCMS($received);
				} else {
					WNote::success('success', WLang::get('success'));
				}
			}
		}
		
		//Gett all data
		
		
		WConfig::load('database', SYS_DIR.'config'.DS.'database.php', 'php');
		$database = WConfig::get('database');
		
		WConfig::load('route', SYS_DIR.'config'.DS.'route.php', 'php');
		$route = WConfig::get('route');
		
		$themes = $this->model->getThemes();
		
		//Let's build the form !
		$base = array(
			'base' => array('label' => WLang::get('base'), 
							'type' => 'text', 
							'value' => $config['base']),
			'site_name' => array('label' => WLang::get('site_name'), 
							'type' => 'text', 
							'value' => $config['site_name']),
			'theme' => array('label' => WLang::get('theme'), 
							'type' => 'select', 
							'value' => $config['theme'],
							'options' => $themes),
			'lang' => array('label' => WLang::get('lang'), 
							'type' => 'select', 
							'value' => $config['lang'],
							'options' => array(
								array('value' => 'en-EN',
									'label' => 'English (en-EN)'),
								array('value' => 'fr-FR',
									'label' => 'Français (fr-FR)'))
							),		
			'timezone' => array('label' => WLang::get('timezone'), 
							'type' => 'text', 
							'value' => $config['timezone']),
			'email' => array('label' => WLang::get('email'), 
							'type' => 'text', 
							'value' => $config['email']),
			'debug' => array('label' => WLang::get('debug'), 
							'type' => 'checkbox', 
							'value' => $config['debug']?'true':null),
			'save' => array('label' => '', 
							'type' => 'hidden', 
							'value' => 'true')
		);
			
		$form = array(
			'action' => '/admin/config/witycms/',
			'change' => '/m/admin/config/witycms/',
			'method' => 'POST',
			'submit_text' => 'Submit',
			'nodes' => $base
		);
		
		WForm::assignForm($this->view, 'test', $form);
	}
	
	/**
	* Config Apps
	**/	
	protected function apps(array $params) {
		
	}
	
	private function checkData(array $received) {
		$isOK = true;
		
		if(!is_null($received['site_name']) && $this->isVerifiedString($received['site_name'])) {
			WNote::error('required', WLang::get('sitename_required'));
			$isOK = false;
		}
		
		if(!is_null($received['base']) && !$this->isUrl($received['base'])) {
			WNote::error('base', WLang::get('base_is_not_url'));
			$isOK = false;
		}
		return $isOK;
		
		/*		$site_name = WRequest::get('site_name', '', 'POST');
				if ($this->isVerifiedString($site_name, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Site name validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid site name", "The site name must be an alphanumeric string. (- and ' and spaces are allowed too)");
					return false;
				}
			
			case 'base_url':
				$base_url = WRequest::get('base', '', 'POST');
				if ($this->isURL($base_url, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Base URL validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid base url", "The base url must be a valid URL representing the constant part of your site URL.");
					return false;
				}
			
			case 'theme':
				$theme = WRequest::get('theme', '', 'POST');
				if ($this->isTheme($theme, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Theme validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid theme", "Theme parameter must be an existing front theme, in 'themes' directory.");
					return false;
				}
			
			case 'language':
				// TODO : auto-detect available languages and validate them
				$this->view->success('group', $data['group'], "Validated !", "Language validated.");
				return true;
			
			case 'timezone':
				// TODO : auto-detect available languages and validate them
				$this->view->success('group', $data['group'], "Validated !", "Timezone validated.");
				return true;
			
			case 'front_app':
				$front_app = WRequest::get('default', '', 'POST');
				if ($this->isFrontApp($front_app, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Front application validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid front application", "Starting front application parameter must an existing front application, in 'apps' directory.");
					return false;
				}
			
			case 'admin_app':
				$admin_app = WRequest::get('admin', '', 'POST');
				if ($this->isAdminApp($admin_app, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Admin application validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid admin application", "Starting admin application parameter must an existing admin application, in 'apps' directory.");
					return false;
				}
			
			case 'db_credentials':
				$r = WRequest::getAssoc(array('server', 'port', 'user', 'pw'), '', 'POST');
				if ($this->isSQLServer($r, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Database credentials validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid database credentials", "Unable to connect to the database with the credentials you've just provided.");
					return false;
				}
			
			case 'db_name':
				$r = WRequest::getAssoc(array('server', 'port', 'user', 'pw', 'dbname'), '', 'POST');
				if ($this->isDatabase($r, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Database name validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid database name", "Unable to find the database with the name you've just provided.");
					return false;
				}
			
			case 'tables_prefix':
				$r = WRequest::getAssoc(array('server', 'port', 'user', 'pw', 'dbname', 'prefix'), '', 'POST');
				if ($this->isPrefixNotExisting($r, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Tables prefix validated and not used.");
				} else if ($respond) {
					$this->view->warning('group', $data['group'], "Prefix already used", "Be careful, the prefix you provides is already used. Some existing tables will be overridden");
					
				}
				return true;
			
			case 'user_nickname':
				$user_nickname = WRequest::get('nickname', '', 'POST');
				if ($this->isVerifiedString($user_nickname, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Nickname validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid nickname", "Your nickname must be an alphanumeric string. (- and ' and spaces are allowed too)");
					return false;
				}
			
			case 'user_password':
				$user_password = WRequest::get('password', '', 'POST');
				$this->view->success('group', $data['group'], "Validated !", "Password validated.");
				return true;
			
			case 'user_email':
				$user_email = WRequest::get('email', '', 'POST');
				if ($this->isEmail($user_email, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Email validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid email", "This email is not valid.");
					return false;
				}
			
			case 'user_firstname':
				$user_firstname = WRequest::get('firstname', '', 'POST');
				if ($this->isVerifiedString($user_firstname, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Firstname validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid firstname", "Your firstname must be an alphanumeric string. (- and ' and spaces are allowed too)");
					return false;
				}
			
			case 'user_lastname':
				$user_lastname = WRequest::get('lastname', '', 'POST');
				if ($this->isVerifiedString($user_lastname, $data, $respond)) {
					$this->view->success('group', $data['group'], "Validated !", "Lastname validated.");
					return true;
				} else if ($respond) {
					$this->view->error('group', $data['group'], "Invalid lastname", "Your lastname must be an alphanumeric string. (- and ' and spaces are allowed too)");
					return false;
				}
			
			default:
				$this->view->error('installer', $data['installer'], 'Unknown group', "You're trying to validate an unknown group.");
				return false;*/
	}
	
	/**
	 * URL Validator
	 * Checks that a string is ...
	 * 
	 * @param string $string
	 * @param array $data
	 * @param $respond
	 * @return bool
	 */
	private function isVerifiedString($string) {
		return empty($string) || (!empty($string) && preg_match("/^[A-Z]?'?[- a-zA-Z]( [a-zA-Z])*$/i", $string));
	}
	
	/**
	 * URL Validator
	 * Checks that a string is a URL where WityCMS can be installed
	 * 
	 * @param string $url
	 * @param array $data
	 * @param $respond
	 * @return bool
	 */
	private function isURL($url) {
		return !empty($url) && preg_match("#^(http|https|ftp)\:\/\/[A-Z0-9][A-Z0-9_-]*(\.[A-Z0-9][A-Z0-9_-]*)*(:[0-9]+)?(\/[A-Z0-9~\._-]+)*\/?$#i", $url);
	}
}

?>
