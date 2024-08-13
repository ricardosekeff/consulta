<?php
class Endereco_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_endereco';
		$this->pk = 'id_endereco';

		$this->rules = array(
			array(
				'field' 	=> 'endereco[logradouro]',
				'label' 	=> 'Logradouro',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'endereco[bairro]',
				'label' 	=> 'Bairro',
				'rules' 	=> 'required'
			),	
			array(
				'field' 	=> 'endereco[cep]',
				'label' 	=> 'CEP',
				'rules' 	=> 'required'
			)
		);
	}
}