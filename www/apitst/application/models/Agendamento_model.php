<?php
class Agendamento_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_agendamento';
		$this->pk = 'id_agendamento';

		$this->rules = array(
			array(
				'field' 	=> 'codigo',
				'label' 	=> 'Código',
				'rules' 	=> 'is_unique[tb_agendamento.codigo]'
			),
			array(
				'field' 	=> 'id_cliente',
				'label' 	=> 'Cliente',
				'rules' 	=> 'required'
			),
			array(
				'field'		=> 'status',
				'label'		=> 'Status',
				'rules'		=> 'in_list[pendente,cancelado,realizado,aguardando pagamento,pago]'
			),
			array(
				'field' 	=> 'created_by',
				'label' 	=> 'Criado por',
				'rules' 	=> 'required'
			)
		);
	}
}