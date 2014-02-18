<?php
/**
 * User Application - Admin View
 */

defined('WITYCMS_VERSION') or die('Access denied');

/**
 * UserAdminView is the Admin View of the User Application.
 *
 * @package Apps\User\Admin
 * @author Johan Dufau <johan.dufau@creatiwity.net>
 * @version 0.4.0-26-04-2013
 */
class ConfigAdminView extends WView {
	public function __construct() {
		parent::__construct();

	}
	
	public function witycms($model) {
		// CSS for all views
		$this->assign('require', 'form/form');
		$this->assign('css', '/apps/config/admin/css/form.css');
	}
}

?>
