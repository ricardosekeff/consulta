<?php
class ParceiroProcedimento_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_parceiro_procedimento';
		$this->pk = 'id_parceiro_procedimento';

		$this->rules = array(
			array(
				'field' 	=> 'comissao',
				'label' 	=> 'Comissão',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'valor_parceiro',
				'label' 	=> 'Valor Parceiro',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'horario_atendimento',
				'label' 	=> 'Horário de Atendimento',
				'rules' 	=> 'required'
			)
		);
	}
}