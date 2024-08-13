<?php
class Cargo_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_cargo';
		$this->pk = 'id_cargo';

		$this->rules = array(
			array(
				'field' 	=> 'nome',
				'label' 	=> 'Nome',
				'rules' 	=> 'required'
			)
		);
	}
}