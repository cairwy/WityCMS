<?php
/**
 * User Application - Admin View - /apps/user/admin/view.php
 */

defined('IN_WITY') or die('Access denied');

/**
 * UserAdminView is the Admin View of the User Application
 * 
 * @package Apps
 * @author Johan Dufau <johandufau@gmail.com>
 * @version 0.3-06-02-2013
 */
class UserAdminView extends WView {
	private $model;
	
	public function __construct(UserAdminModel $model) {
		parent::__construct();
		$this->model = $model;
		// CSS for all views
		$this->assign('css', '/apps/user/admin/css/user.css');
	}
	
	/**
	 * Setting up the users listing view
	 */
	public function listing($sortBy, $sens, $currentPage, $filters) {
		$n = 30; // number of users per page
		
		// SortingHelper Helper
		$sortingHelper = WHelper::load('SortingHelper', array(array('id', 'nickname', 'email', 'date', 'groupe', 'last_activity'), 'date', 'DESC'));
		$sort = $sortingHelper->getSorting($sortBy, $sens);
		
		// Register sorting vars to be displayed in the head of the sorting table
		$this->assign($sortingHelper->getTplVars());
		
		// Treat filters
		$subURL = "";
		foreach ($filters as $k => $v) {
			// Cleanup filters
			if (!empty($v)) {
				$subURL .= $k."=".$v."&";
			}
		}
		if (!empty($subURL)) {
			$subURL = '?'.substr($subURL, 0, -1);
		}
		$this->assign('subURL', $subURL);
		$this->assign($filters);
		
		// Get the user groups
		$this->assign('groups', $this->model->getGroupsList());
		
		// Assign main data
		$data = $this->model->getUsersList(($currentPage-1)*$n, $n, $sort[0], $sort[1] == 'ASC', $filters);
		$this->assign('users', $data);
		
		// Generate the pagination to browse data
		$count = $this->model->countUsers($filters);
		$pagination = WHelper::load('pagination', array($count, $n, $currentPage, '/admin/user/'.$sort[0].'-'.strtolower($sort[1]).'-%d/'.$subURL));
		$this->assign('pagination', $pagination->getHTML());
		$this->assign('total', $count);
	}
	
	/**
	 * Setup the add/edit form
	 */
	private function fillForm($data) {
		$this->assign('js', '/apps/user/admin/js/access_form.js');
		$this->assign('groups', $this->model->getGroupsList());
		
		// Get admin apps
		$adminModel = new AdminController();
		$this->assign('admin_apps', $adminModel->getAdminApps());
		
		$model = array(
			'id' => 0,
			'nickname' => '', 
			'email' => '',
			'groupe' => 0,
			'access' => ''
		);
		foreach ($model as $item => $default) {
			$this->assign($item, isset($data[$item]) ? $data[$item] : $default);
		}
	}
	
	public function add($data = array()) {
		$this->fillForm($data);
		$this->assign('add_form', true);
	}
	
	public function edit($userid) {
		if ($userid == $_SESSION['userid']) {
			WNote::info('user_edit_own', 'Notice: you are editing your own account. Changes will immediately take effect.');
		}
		$this->fillForm($this->model->getUser($userid));
	}
	
	public function del($userid) {
		$data = $this->model->getUser($userid);
		$this->assign('nickname', $data['nickname']);
	}
	
	public function groups_listing($sortBy, $sens) {
		$this->assign('js', '/apps/user/admin/js/access_form.js');
		$this->assign('js', '/apps/user/admin/js/groups.js');
		
		// Get admin apps
		$adminModel = new AdminController();
		$this->assign('admin_apps', $adminModel->getAdminApps());
		
		// AdminStyle Helper
		$dispFields = array('name', 'users_count');
		$adminStyle = WHelper::load('SortingHelper', array($dispFields, 'name'));
		$sort = $adminStyle->getSorting($sortBy, $sens); // sorting vars
		
		// Enregistrement des variables de classement
		$this->assign($adminStyle->getTplVars());
		
		$data = $this->model->getGroupsListWithCount($sort[0], $sort[1] == 'ASC');
		$this->assign('groups', $data);
	}
}

?>