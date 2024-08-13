<?php
class Keys_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'keys';
		$this->pk = 'id';
	}
}