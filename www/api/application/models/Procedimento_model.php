<?php
class Procedimento_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_procedimento';
		$this->pk = 'id_procedimento';

		$this->rules = array(
			array(
				'field' 	=> 'nome',
				'label' 	=> 'Nome',
				'rules' 	=> 'trim|required'
			),
			array(
				'field' 	=> 'tipo',
				'label' 	=> 'Tipo',
				'rules' 	=> 'required|in_list[consulta,exame,procedimento]'
			),
			array(
				'field' 	=> 'codigo_tuss',
				'label' 	=> 'Código TUSS',
				'rules' 	=> 'required'
			)
		);
	}
}