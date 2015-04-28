<?php

class AdminOrderBulkAction
{
	protected $_target;
	protected $_backup_time_stamp;
	private $_backup_prefix = '_backup_';
	private $replace_array = array();
	public $error_msg = array();
	private $file_content = null;

	public function __construct($backup = true)
	{
		$this->setTarget("/controllers/admin/AdminOrdersController.php");
		if($backup){
			if(!$this->makeBackup()) return false;
		}
	}

	public function setTarget($target)
	{
		$this->_target = _PS_ROOT_DIR_.$target;
	}

	public function makeBackup($target = null)
	{
		if(empty($target)) $target = $this->_target;
		$this->_backup_time_stamp = time();
		$_parent = $target;
		$_children = $target.$this->_backup_prefix.$this->_backup_time_stamp;
		if(!@copy( $_parent , $_children ))
		{
			$this->error_msg[] = error_get_last();
			return false;
		} else {
		    if( filesize($_parent) === filesize($_children) ){
		    	return true;
		    } else {
		    	return false;
		    }
		}
	}

	/* $action: replace, before, after
	 * $count: how much can be found
	 * $index: if found more that one, with nead to be change
	 */
	public function addToReplace($search,$replace,$action = "replace", $count = 1, $index = 1)
	{
		if(count($this->searchInFile($search))){
			if($this->searchInFile($search) > $count){
				$this->error_msg[] = "Found more that ".$count." when search:".htmlspecialchars($search);
				return false;
			}

			//Hear nead more condition to test
			//Register who nead to be change
			$this->replace_array[] = array("search" => $search, "replace" => $replace, "action" => $action, "count" => $count, "index" => $index);

		} else {
			$this->error_msg[] = "Not found this string:".htmlspecialchars($search);
			return false;
		}
	}

	private function searchInFile($search_string)
	{
		if(strlen($search_string))
		{
			$content = file_get_contents($this->_target);
			return substr_count($content,$search_string);
		}
		return 0;
	}


	public function run()
	{

		if(!count($this->error_msg))
		{
			$this->file_content = file_get_contents($this->_target);
			foreach ($this->replace_array as $line => $replace) {
				$replace_to = null;
				switch($replace['action']){
					case "replace":
						$replace_to = $replace['replace'];
					break;
					case "before":
						$replace_to = $replace['replace'].$replace['search'];
					break;
					case "after":
						$replace_to = $replace['search'].$replace['replace'];
					break;
				}
				$this->file_content = str_replace($replace['search'], $replace_to, $this->file_content);
			}
			return $this->save();
		} else {
			$this->error_msg[] = "Found some errors!";	
			return false;
		}
	}

	private function save()
	{
		return file_put_contents($this->_target, $this->file_content);
	}

	public function getErrors($clear = true)
	{
		$errors = $this->error_msg; //get all errors messages
		$this->error_msg = array(); //clear error list
		return $errors; //return erros
	}
}
?>