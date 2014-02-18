<?php
/**
 * Editor helps you with classical edition of stored data. Examples of use in users
 * 
 * @package System\WCore
 * @author Johan Dufau <johan.dufau@creatiwity.net>
 * @version 0.4.0-09-01-2013
 */
class Editor {
	private $notes = array();
	
	/**
	 * Edit the model according to values
	 * 
	 * @param  $controller the model that shall implement check as well as edit method
	 * @return array $values : 'name_of_param' => {'type' => type_of_the_value, 'value' => value}
	 * @return boolean $just_check if true, the editor only check if the data are consistent
	 */
	public function edit($controller, array $values, $id_of_record = null) {
		$check_errors = $controller->check($values, $this);
		if(count($check_errors) != 0) {
			$this->pushNotes($check_errors);
			$this->dumpNotes();
			return;
		} else if($id_of_record != null) {
			$id_errors = $controller->isValidRecordId($id_of_record, $this);
			if(count($id_errors) != 0) {
				$this->pushNotes($id_errors);
				$this->dumpNotes();
				return;
			} else {
				$save_msg = $controller->save($values, $id_of_record, $this);	
				if($this->anyError($save_msg)) {
					$this->pushNotes($save_msg);
				} else {
					$this->pushNotes($save_msg);
					$this->success('msg', WLang::get('save_success'));
				}
				
				$this->dumpNotes();
			}
		}
	}
	
	private function pushNotes(array $notes) {
		$this->notes = array_merge($this->notes, $notes);
	}
	
	private function anyError(array $notes) {
		foreach($notes as $note) {
			if($note['level'] == WNote::ERROR) return true;
		}
		return false;
	}
	
	private function dumpNotes() {
		foreach($this->notes as $note) {
			WNote::raise($note);
		}
	}
	
	/**
	 * Generate success record
	 * 
	 * @param  $controller the model that shall implement check as well as edit method
	 * @return array $values : 'name_of_param' => {'type' => type_of_the_value, 'value' => value}
	 * @return boolean $just_check if true, the editor only check if the data are consistent
	 */	
	public function generateSuccess($code, $message = null, $handlers = 'assign') {
		return array(
			'level'    => WNote::SUCCESS,
			'code'     => $code, 
			'message'  => ($message!=null)?$message:WLang::get($code), 
			'handlers' => $handlers
		);
	}
	
	/**
	 * Generate error record
	 * 
	 * @param  $controller the model that shall implement check as well as edit method
	 * @return array $values : 'name_of_param' => {'type' => type_of_the_value, 'value' => value}
	 * @return boolean $just_check if true, the editor only check if the data are consistent
	 */	
	public function generateError($code, $message = null, $handlers = 'assign') {
		return array(
			'level'    => WNote::ERROR,
			'code'     => $code, 
			'message'  => ($message!=null)?$message:WLang::get($code),
			'handlers' => $handlers
		);
	}
	
	/**
	 * Add success to the notes temp
	 * 
	 * @param  $controller the model that shall implement check as well as edit method
	 * @return array $values : 'name_of_param' => {'type' => type_of_the_value, 'value' => value}
	 * @return boolean $just_check if true, the editor only check if the data are consistent
	 */	
	public function success($code, $message = null, $handlers = 'assign') {
		array_push($this->notes, array(
			'level'    => WNote::SUCCESS,
			'code'     => $code, 
			'message'  => ($message!=null)?$message:WLang::get($code),
			'handlers' => $handlers
		));
	}
	
	/**
	 * Add error to the notes temp
	 * 
	 * @param  $controller the model that shall implement check as well as edit method
	 * @return array $values : 'name_of_param' => {'type' => type_of_the_value, 'value' => value}
	 * @return boolean $just_check if true, the editor only check if the data are consistent
	 */	
	public function error($code, $message = null, $handlers = 'assign') {
		array_push($this->notes, array(
			'level'    => WNote::ERROR,
			'code'     => $code, 
			'message'  => ($message!=null)?$message:WLang::get($code),
			'handlers' => $handlers
		));
	}
}

?>