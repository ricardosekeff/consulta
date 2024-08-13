<?php
class PessoaFisica_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_pessoa_fisica';
		$this->pk = 'id_pessoa_fisica';

		$this->rules = array(
			array(
				'field' 	=> 'pf[nome]',
				'label' 	=> 'Nome',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pf[cpf]',
				'label' 	=> 'CPF',
				'rules' 	=> 'is_unique[tb_pessoa_fisica.cpf]|min_length[11]'
			),
			array(
				'field' 	=> 'pf[sexo]',
				'label' 	=> 'Sexo',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pf[data_nascimento]',
				'label' 	=> 'Data de Nascimento',
				'rules' 	=> 'required|regex_match[/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/]'
			),
			array(
				'field' 	=> 'pf[telefone]',
				'label' 	=> 'Telefone',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pf[email]',
				'label' 	=> 'E-mail',
				'rules' 	=> 'valid_email'
			)
		);
	}
}