<?php
class Representante_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_representante';
		$this->pk = 'id_representante';

		$this->rules = array(
			array(
				'field' 	=> 'nome',
				'label' 	=> 'Nome',
				'rules' 	=> 'is_unique[tb_agendamento.codigo]'
            ),
            array(
                'field' 	=> 'cpf',
				'label' 	=> 'CPF',
				'rules' 	=> 'is_unique[tb_representante.cpf]|min_length[11]'
            )
		);
	}
}