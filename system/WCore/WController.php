<?php 
/**
 * WController.php
 */

defined('IN_WITY') or die('Access denied');

/**
 * WController is the base class that will be inherited by all the applications.
 * 
 * @package System\WCore
 * @author Johan Dufau <johan.dufau@creatiwity.net>
 * @version 0.4.0-12-10-2013
 */
abstract class WController {
	/**
	 * @var array Context of the application describing app's name, app's directory and app's main class
	 */
	private $context = array();
	
	/**
	 * @var array Manifest of the application
	 */
	private $manifest;
	
	/**
	 * @var mixed Model class to retrieve application's data from the database
	 */
	protected $model;
	
	/**
	 * @var WView The view object linked to the application
	 */
	protected $view;
	
	 /**
	 * @var string Action that was performed in this application
	 */
	protected $action = '';
	
	/**
	 * @var array Parameters to forward to the application's action
	 */
	private $params;
	
	/**
	 * @var array List of headers for this view
	 */
	private $headers = array();
	
	/**
	 * Application initialization
	 * 
	 * @param array $context Context of the application describing app's name, app's directory and app's main class
	 */
	public function init(array $context) {
		$this->context = $context;
		
		// Initialize view if the app's constructor did not do it
		if (is_null($this->view)) {
			$this->setView(new WView());
		}
		
		// Forward the context to the View
		$this->view->setContext($this->context);
		
		// Parse the manifest
		$this->manifest = $this->loadManifest($this->getAppName());
		if (empty($this->manifest)) {
			WNote::error('app_no_manifest', 'The manifest of the application "'.$this->getAppName().'" cannot be found.');
		}
		
		// Automatically declare the language directory
		if (is_dir($this->context['directory'].'lang')) {
			WLang::declareLangDir($this->context['directory'].'lang', $this->manifest['default_lang']);
		}
	}
	
	/**
	 * Default Launch method
	 * 
	 * Launch method determines the method that must be triggered in the application.
	 * Most of the time, the action given in URL is used.
	 */
	public function launch($action, array $params = array()) {
		// Trigger the corresponding method for the action given in URL
		return $this->forward($action, $params);
	}
	
	/**
	 * Calls the application's method which is associated to the $action value
	 * 
	 * @param string $action Action to execute
	 * @param array  $params Parameters to forward to the app's controller (given in URL or retriever)
	 * @return array The model generated by the action
	 */
	public final function forward($action, array $params = array()) {
		if (!empty($action)) {
			$access_result = $this->hasAccess($this->getAppName(), $action);
			
			if ($access_result === true) {
				$this->action = $action;
				
				// Theme configuration for admin
				if ($this->getAdminContext()) {
					WConfig::set('config.theme', 'admin-bootstrap');
					
					// These are template variables => direct assign in WTemplate
					$tpl = WSystem::getTemplate();
					$tpl->assign('appsList', $this->getAdminApps());
					$tpl->assign('userNickname', @$_SESSION['nickname']);
					
					$manifest = $this->getManifest();
					$action_asked = $this->getTriggeredAction();
					
					$tpl->assign(array(
						'appSelected' => $this->getAppName(),
						'actionsList' => $manifest['admin'],
						'actionAsked' => $action_asked
					));
					$this->view->assign('page_title', sprintf('Admin &raquo; %s%s',
						ucwords($manifest['name']),
						isset($manifest['admin'][$action_asked]) ? ' &raquo; '.WLang::get($manifest['admin'][$action_asked]['desc']) : ''
					));
				}
				
				// Execute action
				if (method_exists($this, $action)) {
					return $this->$action($params);
				} else {
					WNote::error('app_no_method', 'The method corresponding to the action "'.$action.'" cannot be found in '.$this->getAppName().' application.', 'debug');
					return array();
				}
			} else if (is_array($access_result)) {
				return $access_result; // hasAccess returned a note
			} else {
				// Display login form if not connected
				if (!WSession::isConnected()) {
					WNote::info('user_login_required', 'This area requires to be logged in.', 'assign');
					$userView = WRetriever::getView('user', array('login'));
					$this->setView($userView);
				} else {
					return WNote::error('app_no_access', 'You do not have access to the application '.$this->getAppName().'.');
				}
			}
		} else {
			return WNote::error('app_no_suitable_action', 'No suitable action to trigger was found in the application '.$this->getAppName().'.');
		}
	}
	
	/**
	 * Returns the application's name
	 * 
	 * @return string application's name
	 */
	public function getAppName() {
		return $this->context['name'];
	}
	
	/**
	 * Returns the application's context
	 * 
	 * @return array Application's context
	 */
	public function getContext($field = '') {
		if (!empty($field)) {
			return (isset($this->context[$field])) ? $this->context[$field] : '';
		}
		
		return $this->context;
	}

	/**
	 * Returns true if there is a parent in the context, false otherwise
	 *
	 * @return bool true if there is a parent in the context, false otherwise
	 */
	public function hasParent() {
		return $this->context['parent'];
	}
	
	/**
	 * Returns if the application is in admin mode or not
	 * 
	 * @return bool true if admin mode defined in context, false otherwise
	 */
	public function getAdminContext() {
		return $this->context['admin'] === true;
	}
	
	/**
	 * Sets the private view property to $view
	 * 
	 * @param WView $view the view that will be associated to this instance of the controller
	 */
	public function setView(WView $view) {
		unset($this->view);
		$this->view = $view;
	}
	
	/**
	 * Returns the current view
	 * 
	 * @return WView the current view
	 */
	public function getView() {
		return $this->view;
	}
	
	/**
	 * Defines a new model for this controller
	 */
	public function setModel($model) {
		unset($this->model);
		$this->model = $model;
	}
	
	/**
	 * Get the model defined for this application
	 * 
	 * @return Object
	 */
	public function getModel() {
		return $this->model;
	}
	
	/**
	 * Returns action's name which is the second parameter given in the URL, right after the app's name.
	 * 
	 * This method will remove the first item in $params if it is used to determine the action to trigger.
	 * 
	 * @param array $params
	 * @return string action's name asked in the URL
	 */
	public function getAskedAction(&$params) {
		$action = isset($params[0]) ? $params[0] : '';
		
		if ($this->getAdminContext()) {
			$actions_key = 'admin';
			$alias_prefix = 'admin-';
			$default = 'default_admin';
		} else {
			$actions_key = 'actions';
			$alias_prefix = '';
			$default = 'default';
		}
		
		// $action exists ? Otherwise, check alias and finally, use default action if exists?
		if (!empty($action) && !isset($this->manifest[$actions_key][$action])) {
			// check alias
			if (isset($this->manifest['alias'][$alias_prefix.$action])) {
				$action = $this->manifest['alias'][$alias_prefix.$action];
			} else { // try to guess
				$action = str_replace('-', '_', $action);
				if (!isset($this->manifest[$actions_key][$action])) {
					$action = '';
				}
			}
		}
		
		if (!empty($action)) {
			// Remove the first item in $params because it is the action name
			array_shift($params);
		} else if (isset($this->manifest[$default])) {
			$action = $this->manifest[$default];
		}
		
		return $action;
	}
	
	 /**
	 * Returns the real executed action
	 * 
	 * @return string real executed action name
	 */
	public function getTriggeredAction() {
		return $this->action;
	}
	
	/**
	 * Loads the manifest file of a given application
	 * 
	 * @param string $app_name name of the application owning the manifest
	 * @return array manifest asked
	 */
	public function loadManifest($app_name) {
		$manifest = WConfig::get('manifest.'.$app_name);
		if (is_null($manifest)) {
			$manifest_file = APPS_DIR.$app_name.DS.'manifest.php';
			if (!file_exists($manifest_file)) {
				// WNote::error('controller_no_manifest', 'Unable to find the manifest "'.$manifest_file.'".', 'debug');
				return null;
			}
			
			// Checks cache directory
			if (!is_dir(CACHE_DIR.'manifests')) {
				@mkdir(CACHE_DIR.'manifests', 0777);
			}
			
			// Is there a manifest parsed in cache?
			$cache_file = CACHE_DIR.'manifests'.DS.$app_name.'.php';
			if (file_exists($cache_file) && @filemtime($cache_file) > @filemtime($manifest_file)) {
				include $cache_file;
			}
			if (!isset($manifest)) { // cache failed
				$manifest = $this->parseManifest($manifest_file);
				
				if (is_writable(CACHE_DIR.'manifests')) {
					// Opening
					if (!($handler = fopen($cache_file, 'w'))) {
						WNote::error('controller_create_manifest_cache', 'Unable to open the cache target "'.$cache_file.'".', 'debug');
					}
					
					// Writing
					fwrite($handler, "<?php\n\n\$manifest = ".var_export($manifest, true).";\n\n?>");
					fclose($handler);
				}
			}
			WConfig::set('manifest.'.$app_name, $manifest);
		}
		return $manifest;
	}
	
	/**
	 * Retrieves the manifest of the application running
	 * 
	 * @return array manifest
	 */
	public function getManifest() {
		return $this->manifest;
	}
	
	/**
	 * Parses a manifest file
	 * 
	 * @param string $manifest_href Href of the manifest file desired
	 * @return array manifest parsed into an array representation
	 */
	private function parseManifest($manifest_href) {
		if (!file_exists($manifest_href)) {
			return null;
		}
		
		$manifest_string = file_get_contents($manifest_href);
		$manifest_string = trim(preg_replace('#<\?php.+\?>#U', '', $manifest_string));
		
		$xml = simplexml_load_string($manifest_string);
		$manifest = array();
		
		// Nodes to look for
		$nodes = array('name', 'version', 'date', 'icone', 'action', 'admin', 'permission', 'default_lang');
		foreach ($nodes as $node) {
			switch ($node) {
				case 'action':
					$manifest['actions'] = array();
					if (property_exists($xml, 'action')) {
						foreach ($xml->action as $action) {
							$attributes = $action->attributes();
							$key = strtolower((string) $action);
							if (!empty($key)) {
								if (!isset($manifest['actions'][$key])) {
									$manifest['actions'][$key] = array(
										'desc' => isset($attributes['desc']) ? (string) $attributes['desc'] : $key,
										'requires' => isset($attributes['requires']) ? array_map('trim', explode(',', $attributes['requires'])) : array()
									);
								}
								if (isset($attributes['default']) && empty($manifest['default'])) {
									$manifest['default'] = $key;
								}
								if (isset($attributes['alias']) && !empty($attributes['alias'])) {
									$alias = explode(',', $attributes['alias']);
									foreach ($alias as $al) {
										$al = strtolower(trim($al));
										if (!empty($al)) {
											$manifest['alias'][$al] = $key;
										}
									}
								}
							}
						}
					}
					break;
				
				case 'admin':
					$manifest['admin'] = array();
					if (property_exists($xml, 'admin') && property_exists($xml->admin, 'action')) {
						foreach ($xml->admin->action as $action) {
							if (!empty($action)) {
								$attributes = $action->attributes();
								$key = strtolower((string) $action);
								if (!empty($key)) {
									if (!isset($manifest['admin'][$key])) {
										$manifest['admin'][$key] = array(
											'desc' => isset($attributes['desc']) ? (string) $attributes['desc'] : $key,
											'menu' => isset($attributes['menu']) ? (string) $attributes['menu'] == 'true' : true,
											'requires' => isset($attributes['requires']) ? array_map('trim', explode(',', $attributes['requires'])) : array()
										);
									}
								}
								if (isset($attributes['default']) && empty($manifest['default_admin'])) {
									$manifest['default_admin'] = $key;
								}
								if (isset($attributes['alias']) && !empty($attributes['alias'])) {
									$alias = explode(',', $attributes['alias']);
									foreach ($alias as $al) {
										$al = strtolower(trim($al));
										if (!empty($al)) {
											$manifest['alias']['admin-'.$al] = $key;
										}
									}
								}
							}
						}
					}
					break;
				
				case 'permission':
					$manifest['permissions'] = !empty($manifest['admin']) ? array('admin') : array();
					if (property_exists($xml, 'permission')) {
						foreach ($xml->permission as $permission) {
							if (!empty($permission)) {
								$attributes = $permission->attributes();
								if (!empty($attributes['name'])) {
									$manifest['permissions'][] = (string) $attributes['name'];
								}
							}
						}
					}
					break;
				
				case 'name':
					$manifest['name'] = property_exists($xml, 'name') ? (string) $xml->name : basename(dirname($manifest_href));
					break;
				
				default:
					$manifest[$node] = property_exists($xml, $node) ? (string) $xml->$node : '';
					break;
			}
		}
		return $manifest;
	}
	
	/**
	 * Checks whether the user has access to an application, or a precise action of an application
	 * hasAccess('news') = does the user have access to news app?
	 * hasAccess('news', 'detail') = access to action detail in news app?
	 * 
	 * @param string  $app    Name of the app
	 * @param string  $action Action in the app to be checked (can be empty '' to check overall app access)
	 * @param boolean $admin  Admin context (default to Wity admin context)
	 * @return boolean|array
	 */
	public function hasAccess($app, $action = '', $admin = null) {
		if (is_null($admin)) {
			$admin = $this->getAdminContext();
		}
		
		// Check manifest
		$manifest = $this->loadManifest($app);
		if (is_null($manifest)) {
			return false;
		}
		
		if ($admin) { // Admin mode ON
			if (empty($_SESSION['access'])) {
				return false;
			}
			
			if ($_SESSION['access'] == 'all') {
				return true;
			} else if (isset($_SESSION['access'][$app]) && is_array($_SESSION['access'][$app]) && in_array('admin', $_SESSION['access'][$app])) {
				if (empty($action)) { // Asking for application access
					return true;
				} else if (isset($manifest['admin'][$action])) {
					// Check permissions
					foreach ($manifest['admin'][$action]['requires'] as $req) {
						switch ($req) {
							case 'connected':
							case 'admin':
								break;
							
							default:
								if (!isset($_SESSION['access'][$app]) || !in_array($req, $_SESSION['access'][$app])) {
									return WNote::error('app_no_access', 'You need more privileges to access the action '.$action.' in the application '.$app.'.');
								}
								break;
						}
					}
					
					return true;
				}
			}
		} else { // Admin mode OFF
			if (empty($action)) { // Asking for application access
				return true;
			} else if (isset($manifest['actions'][$action])) {
				// Check permissions
				foreach ($manifest['actions'][$action]['requires'] as $req) {
					switch ($req) {
						case 'not-connected':
							if (WSession::isConnected()) {
								return WNote::error('app_logout_required', 'The '.$action.' action of the application '.$app.' requires to be loged out.');
							}
							break;
						
						case 'connected':
							if (!WSession::isConnected()) {
								return false;
							}
							break;
						
						default:
							if (!isset($_SESSION['access'][$app]) || !in_array($req, $_SESSION['access'][$app])) {
								return WNote::error('app_no_access', 'You need more privileges to access the action '.$action.' in the application '.$app.'.');
							}
							break;
					}
				}
				
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Returns the list of admin apps according to the user's access.
	 */
	public function getAdminApps() {
		static $admin_apps = array();
		if (empty($admin_apps)) {
			$apps = WRetriever::getAppsList();
			foreach ($apps as $app) {
				if (substr($app, 0, 5) == 'admin') {
					$app_name = substr($app, 6);
					if ($this->hasAccess($app_name, '', true)) {
						$admin_apps[$app_name] = $this->loadManifest($app_name);
					}
				}
			}
		}
		
		return $admin_apps;
	}
	
	/**
	 * Set a new header for the response
	 * Will be assigned in WResponse::render()
	 * 
	 * @param string $name Header's name
	 * @param string $value
	 */
	public function setHeader($name, $value) {
		$this->headers[strtolower($name)] = $value;
	}
	
	/**
	 * Get the headers for this app
	 * 
	 * @return array
	 */
	public function getHeaders() {
		$headers = $this->headers;
		$this->headers = array();
		return $headers;
	}
}

?>
