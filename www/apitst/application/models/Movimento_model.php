<?php
class Movimento_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_movimento';
		$this->pk = 'id_movimento';

		$this->rules = array(
			array(
				'field' 	=> 'descricao',
				'label' 	=> 'Descrição',
				'rules' 	=> 'required'
			)
		);
	}
}