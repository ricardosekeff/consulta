<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class PessoaFisica extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('pessoaFisica_model');

		$this->methods['index_get'] = array('limit' => 240, 'level' => 2);
		$this->methods['check_get'] = array('limit' => 240, 'level' => 2);
		/*
		$this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_
        ']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50; // 50 requests per hour per user/key
        */
	}

	public function index_get( $cpf=null )
	{
		$options = array(
			'fields'	=> 'nome, nome_social, data_nascimento, cpf, rg, sexo, email, telefone, telefone2, pai, mae, responsavel, id_pessoa',
			'where'		=> array('cpf' => $cpf)
		);

		$resultado = $this->pessoaFisica_model->get(null, $options);

		if ($resultado) {
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhuma pessoa encontrada'
			), REST_Controller::HTTP_OK);
		}
	}

	public function check_get( $cpf=null ) 
	{
		$options = array(
			'fields'	=> 'tb_pessoa_fisica.id_pessoa_fisica, tb_pessoa_fisica.id_pessoa, tb_pessoa_fisica.nome, tb_pessoa_fisica.nome_social, tb_pessoa_fisica.data_nascimento, tb_pessoa_fisica.cpf, tb_pessoa_fisica.rg, tb_pessoa_fisica.sexo, tb_pessoa_fisica.email, tb_pessoa_fisica.telefone, tb_pessoa_fisica.telefone2, tb_pessoa_fisica.pai, tb_pessoa_fisica.mae, tb_pessoa_fisica.responsavel, tb_cliente.id_cliente, tb_usuario.id_usuario, tb_funcionario.id_funcionario, tb_parceiro.id_parceiro',
			'join'		=> array(
				array('tb_parceiro', 'tb_parceiro.id_pessoa = tb_pessoa_fisica.id_pessoa', 'left'),
				array('tb_funcionario', 'tb_funcionario.id_pessoa_fisica = tb_pessoa_fisica.id_pessoa_fisica', 'left'),
				array('tb_cliente', 'tb_cliente.id_pessoa_fisica = tb_pessoa_fisica.id_pessoa_fisica', 'left'),
				array('tb_usuario', 'tb_usuario.id_pessoa = tb_pessoa_fisica.id_pessoa', 'left')
			),
			'where'		=> array('tb_pessoa_fisica.cpf' => $cpf)
		);

		$resultado = $this->pessoaFisica_model->get(null, $options);

		if ($resultado) {
			$pessoa_fisica = array(
				'nome'				=> $resultado[0]['nome'],
				'nome_social'		=> $resultado[0]['nome_social'],
				'sexo'				=> $resultado[0]['sexo'],
				'email'				=> $resultado[0]['email'],
				'rg'				=> $resultado[0]['rg'],
				'cpf'				=> $resultado[0]['cpf'],
				'data_nascimento'	=> $resultado[0]['data_nascimento'],
				'telefone'			=> $resultado[0]['telefone'],
				'telefone2'			=> $resultado[0]['telefone2'],
				'pai'				=> $resultado[0]['pai'],
				'mae'				=> $resultado[0]['mae'],
				'responsavel'		=> $resultado[0]['responsavel']
			);

			$resultado = (object) array(
				'id_pessoa_fisica'	=> $resultado[0]['id_pessoa_fisica'],
				'id_funcionario'	=> $resultado[0]['id_funcionario'],
				'id_parceiro'		=> $resultado[0]['id_parceiro'],
				'id_pessoa'			=> $resultado[0]['id_pessoa'],
				'id_cliente'		=> $resultado[0]['id_cliente'],
				'id_usuario'		=> $resultado[0]['id_usuario'],
				'pf'				=> $pessoa_fisica
			);

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhuma pessoa encontrada'
			), REST_Controller::HTTP_OK);
		}
	}
}