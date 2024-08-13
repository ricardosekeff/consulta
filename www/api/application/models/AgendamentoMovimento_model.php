<?php
class AgendamentoMovimento_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_agendamento_movimento';
		$this->pk = 'id_agendamento_movimento';

		$this->rules = array(
			array(
				'field' 	=> 'id_agendamento',
				'label' 	=> 'Agendamento',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'id_movimento',
				'label' 	=> 'Movimento',
				'rules' 	=> 'required'
			)
		);
	}
}