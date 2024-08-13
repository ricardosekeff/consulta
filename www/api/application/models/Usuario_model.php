<?php
class Usuario_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_usuario';
		$this->pk = 'id_usuario';

		$this->rules = array(
			array(
				'field' 	=> 'email',
				'label' 	=> 'E-mail',
				'rules' 	=> 'required|is_unique[tb_usuario.email]'
			),
			array(
				'field' 	=> 'senha',
				'label' 	=> 'Senha',
				'rules' 	=> 'required|min_length[6]'
			),
			array(
				'field' 	=> 'senha_confirma',
				'label' 	=> 'Senha Confirma',
				'rules' 	=> 'required|matches[senha]'
			),
			array(
				'field' 	=> 'tipo',
				'label' 	=> 'Tipo',
				'rules' 	=> 'required'
			)
		);

		$this->senha_rules = array(
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