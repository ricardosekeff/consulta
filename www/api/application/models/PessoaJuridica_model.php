<?php
class PessoaJuridica_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_pessoa_juridica';
		$this->pk = 'id_pessoa_juridica';

		$this->rules = array(
			array(
				'field' 	=> 'pj[razao_social]',
				'label' 	=> 'Razão Social',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[nome_fantasia]',
				'label' 	=> 'Nome Fantasia',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[cnpj]',
				'label' 	=> 'CNPJ',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[telefone]',
				'label' 	=> 'Telefone',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[email]',
				'label' 	=> 'E-mail',
				'rules' 	=> 'valid_email'
			),
			array(
				'field' 	=> 'pj[nome_responsavel]',
				'label' 	=> 'Nome do Responsável',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[cpf_responsavel]',
				'label' 	=> 'CPF do Responsável',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[telefone_responsavel]',
				'label' 	=> 'Telefone do Responsável',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'pj[email_responsavel]',
				'label' 	=> 'E-mail do Responsável',
				'rules' 	=> 'required|valid_email'
			)
		);
	}
}