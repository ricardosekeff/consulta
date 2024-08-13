<?php
class Cliente_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_cliente';
		$this->pk = 'id_cliente';

		$this->rules = array();

		$this->rules_procedimento = array(
			array(
				'field' 	=> 'codigo',
				'label' 	=> 'Código',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'id_cliente',
				'label' 	=> 'Cliente',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'id_parceiro_procedimento',
				'label' 	=> 'Procedimento',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'created_by',
				'label' 	=> 'Usuário Criador',
				'rules' 	=> 'required'
			)
		);
	}
}