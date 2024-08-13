<?php
class Parceiro_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_parceiro';
		$this->pk = 'id_parceiro';

		$this->rules = array(
			array(
				'field' 	=> 'nome',
				'label' 	=> 'Nome',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'banco',
				'label' 	=> 'Banco',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'agencia',
				'label' 	=> 'Agência',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'conta',
				'label' 	=> 'Conta',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'horario_funcionamento',
				'label' 	=> 'Horário de Funcionamento',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'ciclo_pagamento',
				'label' 	=> 'Ciclo de Pagamento',
				'rules' 	=> 'required|in_list[7,15,30]'
			)
		);

		$this->rules_create = array(
			array(
				'field'		=> 'id_pessoa',
				'label'		=> 'Pessoa',
				'rules'		=> 'is_unique[tb_parceiro.id_pessoa]'
			)
		);

		$this->rules_usuario = array(
			array(
				'field' 	=> 'email',
				'label' 	=> 'E-mail',
				'rules' 	=> 'required|is_unique[tb_usuario.email]'
			),
		);

		$this->rules_senha = array(
			array(
				'field' 	=> 'senha',
				'label' 	=> 'Senha',
				'rules' 	=> 'required|min_length[6]'
			),
			array(
				'field' 	=> 'senha_confirma',
				'label' 	=> 'Senha Confirma',
				'rules' 	=> 'required|matches[senha]'
			)
		);
	}
}