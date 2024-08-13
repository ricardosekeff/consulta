<?php
class Menu_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_menu';
		$this->pk = 'id_menu';

		$this->rules = array(
			array(
				'field' 	=> 'nome',
				'label' 	=> 'Nome',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'Link',
				'label' 	=> 'link',
				'rules' 	=> 'required'
			),
		);
	}
}