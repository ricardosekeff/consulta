<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class PessoaJuridica extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('pessoaJuridica_model');

		$this->methods['index_get'] = array('limit' => 240, 'level' => 10);
		$this->methods['check_get'] = array('limit' => 240, 'level' => 2);
	}

	public function index_get( $cnpj=null )
	{
		$options = array(
			'fields'	=> 'razao_social, nome_fantasia, cnpj, telefone, email, nome_responsavel, cpf_responsavel, telefone_responsavel, email_responsavel, id_pessoa',
			'where'		=> array('cnpj' => $cnpj)
		);

		$resultado = $this->pessoaJuridica_model->get(null, $options);

		if ($resultado) {
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhuma pessoa juridica encontrada'
			), REST_Controller::HTTP_OK);
		}
	}

	public function check_get( $cnpj=null ) 
	{
		$options = array(
			'fields'	=> 'tb_pessoa_juridica.id_pessoa_juridica, tb_pessoa_juridica.id_pessoa, tb_pessoa_juridica.razao_social, tb_pessoa_juridica.nome_fantasia, tb_pessoa_juridica.cnpj, tb_pessoa_juridica.telefone, tb_pessoa_juridica.telefone2, tb_pessoa_juridica.email, tb_pessoa_juridica.nome_responsavel, tb_pessoa_juridica.cpf_responsavel, tb_pessoa_juridica.telefone_responsavel, tb_pessoa_juridica.email_responsavel, tb_parceiro.id_parceiro, tb_unidade.id_unidade, tb_usuario.id_usuario',
			'join'		=> array(
				array('tb_parceiro', 'tb_parceiro.id_pessoa = tb_pessoa_juridica.id_pessoa', 'left'),
				array('tb_unidade', 'tb_unidade.id_pessoa_juridica = tb_pessoa_juridica.id_pessoa_juridica', 'left'),
				array('tb_usuario', 'tb_usuario.id_pessoa = tb_pessoa_juridica.id_pessoa', 'left')
			),
			'where'		=> array('tb_pessoa_juridica.cnpj' => $cnpj)
		);

		$resultado = $this->pessoaJuridica_model->get(null, $options);

		if ($resultado) {
			$pessoa_juridica = array(
				'razao_social'			=> $resultado[0]['razao_social'],
				'nome_fantasia'			=> $resultado[0]['nome_fantasia'],
				'cnpj'					=> $resultado[0]['cnpj'],
				'telefone'				=> $resultado[0]['telefone'],
				'telefone2'				=> $resultado[0]['telefone2'],
				'email'					=> $resultado[0]['email'],
				'nome_responsavel'		=> $resultado[0]['nome_responsavel'],
				'cpf_responsavel'		=> $resultado[0]['cpf_responsavel'],
				'telefone_responsavel'	=> $resultado[0]['telefone_responsavel'],
				'email_responsavel'		=> $resultado[0]['email_responsavel']
			);

			$resultado = (object) array(
				'id_pessoa_juridica'=> $resultado[0]['id_pessoa_juridica'],
				'id_pessoa'			=> $resultado[0]['id_pessoa'],
				'id_parceiro'		=> $resultado[0]['id_parceiro'],
				'id_unidade'		=> $resultado[0]['id_unidade'],
				'id_usuario'		=> $resultado[0]['id_usuario'],
				'pj'				=> $pessoa_juridica
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