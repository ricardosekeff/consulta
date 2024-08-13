<?php
class Financeiro_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_financeiro';
		$this->pk = 'id_financeiro';

		$this->rules = array(
			array(
				'field'		=> 'status',
				'label'		=> 'Status',
				'rules'		=> 'in_list[pendente,pago,cancelado]'
			),
			array(
				'field' 	=> 'id_parceiro',
				'label' 	=> 'Parceiro',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'created_by',
				'label' 	=> 'Criado por',
				'rules' 	=> 'required'
			)
		);
	}
}