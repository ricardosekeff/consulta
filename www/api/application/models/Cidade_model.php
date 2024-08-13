<?php
class Cidade_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_cidade';
		$this->pk = 'id_cidade';
	}
}