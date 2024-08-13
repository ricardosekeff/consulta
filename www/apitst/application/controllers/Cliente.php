<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Cliente extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('cliente_model');

		$this->methods['index_get'] = array('limit' => 500, 'level' => 5);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['index_put'] = array('limit' => 60, 'level' => 5);
		$this->methods['cpf_get'] = array('limit' => 500, 'level' => 5);
		$this->methods['procedimento_post'] = array('limit' => 120, 'level' => 5);
		/*$this->methods['cidade_delete']['limit'] = 50; // 50 requests per hour per user/key*/
	}

	public function index_get( $id=null, $field=null )
	{
		$options = array(
			'fields'	=> '*, tb_endereco.nome AS endereco_nome, tb_pessoa_fisica.nome as pf_nome, tb_cidade.uf as estado, tb_cidade.nome as cidade',
			'join'		=> array(
				array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'left'),
				array('tb_endereco', 'tb_endereco.id_endereco = tb_cliente.id_endereco', 'left'),
				array('tb_cidade', 'tb_cidade.id_cidade = tb_endereco.id_cidade', 'left')
			)
		);
		if ($this->rest->level > 45) {
			$options = array_merge($options, $_GET);
		}
		// $options = array_merge($options, $_GET);

		if (!$id || $field) {
			$options['fields'] = 'tb_cliente.id_cliente, tb_pessoa_fisica.id_pessoa_fisica, tb_pessoa_fisica.nome, tb_pessoa_fisica.cpf';
			if ($field) {
				$options['where'] = "tb_pessoa_fisica.$field LIKE '$id%'";
			}
			$resultado = $this->cliente_model->get(null, $options);
		} else {
			$resultado = $this->cliente_model->get($id, $options);
		}


		if ($resultado) {

			if ($id && !$field) {
				$cliente = array(
					'id_cliente'	=> $resultado[0]['id_cliente'],
					'foto'			=> $resultado[0]['foto'],
					'status'		=> $resultado[0]['status']
				);

				$endereco = array(
					'id_endereco'	=> $resultado[0]['id_endereco'],
					'nome'			=> $resultado[0]['endereco_nome'],
					'cep'			=> $resultado[0]['cep'],
					'logradouro' 	=> $resultado[0]['logradouro'],
					'numero' 		=> $resultado[0]['numero'],
					'complemento' 	=> $resultado[0]['complemento'],
					'bairro'		=> $resultado[0]['bairro'],
					'id_cidade'		=> $resultado[0]['id_cidade'],
					'estado'		=> $resultado[0]['estado'],
					'cidade'		=> $resultado[0]['cidade']
				);

				$pessoa_fisica = array(
					'id_pessoa_fisica'	=> $resultado[0]['id_pessoa_fisica'],
					'nome'				=> $resultado[0]['pf_nome'],
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
					'id_cliente'	=> $cliente['id_cliente'],
					'foto'			=> $cliente['foto'],
					'status'		=> $cliente['status'],
					'endereco'		=> $endereco,
					'pf'			=> $pessoa_fisica
				);
			}

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum cliente encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data);

		if ( $validacao === TRUE ) {
			$endereco = array(
				'nome'			=> 'Cliente',
				'cep'			=> $data['endereco']['cep'],
				'logradouro' 	=> $data['endereco']['logradouro'],
				'numero' 		=> $data['endereco']['numero'],
				'complemento' 	=> $data['endereco']['complemento'],
				'bairro'		=> $data['endereco']['bairro'],			
				'id_cidade'		=> $data['endereco']['id_cidade']
			);

			$this->db->trans_start();
			
			$this->db->insert('tb_endereco', $endereco);
			$id_endereco = $this->db->insert_id();

			if ($data['pf']['id_pessoa_fisica']) {
				$id_pessoa_fisica = $data['pf']['id_pessoa_fisica'];				
			} else {
				$pessoa = array(
					'pessoa_juridica' => 0
				);

				$pessoa_fisica = array(
					'nome'				=> $data['pf']['nome'],
					'nome_social'		=> $data['pf']['nome_social'],
					'sexo'				=> $data['pf']['sexo'],
					'rg'				=> $data['pf']['rg'],
					'cpf'				=> $data['pf']['cpf'],
					'data_nascimento'	=> $data['pf']['data_nascimento'],
					'telefone'			=> $data['pf']['telefone'],
					'telefone2'			=> $data['pf']['telefone2'],
					'email'				=> $data['pf']['email'],
					'pai'				=> $data['pf']['pai'],
					'mae'				=> $data['pf']['mae'],
					'responsavel'		=> $data['pf']['responsavel']
				);

				$this->db->insert('tb_pessoa', $pessoa);
				$id_pessoa = $this->db->insert_id();

				$pessoa_fisica['id_pessoa'] = $id_pessoa;
				$this->db->insert('tb_pessoa_fisica', $pessoa_fisica);
				$id_pessoa_fisica = $this->db->insert_id();
				
				// adiciona usuário se email for informado
				if (!empty($data['pf']['email'])) {
					$usuario = array(
						'email'			=> $data['pf']['email'],
						'senha'			=> substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil(12/strlen($x)) )),1,12),
						'status'		=> 0,
						'id_pessoa'		=> $id_pessoa,
						'id_role'		=> 5
					);
					$this->db->insert('tb_usuario', $usuario);
					$id_usuario = $this->db->insert_id();
				}
			}

			$cliente = array(
				'id_pessoa_fisica'	=> $id_pessoa_fisica,
				'id_endereco' 		=> $id_endereco,
				'created_by'		=> $this->rest->user_id
			);
			$this->db->insert('tb_cliente', $cliente);
			
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao cadastrar o cliente. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->response($cliente, REST_Controller::HTTP_CREATED);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function index_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data);

		if ( $validacao === TRUE ) {
			$sql = "UPDATE tb_cliente AS cli 
				INNER JOIN tb_endereco AS ende ON ende.id_endereco = cli.id_endereco
				INNER JOIN tb_pessoa_fisica AS pf ON pf.id_pessoa_fisica = cli.id_pessoa_fisica
				SET
					pf.nome = ?,
					pf.nome_social = ?,
					pf.data_nascimento = ?,
					pf.cpf = ?,
					pf.rg = ?,
					pf.sexo = ?,
					pf.email = ?,
					pf.telefone = ?,
					pf.telefone2 = ?,
					pf.pai = ?,
					pf.mae = ?,
					pf.responsavel = ?,
					ende.logradouro = ?,
					ende.numero = ?,
					ende.bairro = ?,
					ende.complemento = ?,
					ende.cep = ?,
					ende.id_cidade = ?
				WHERE cli.id_cliente = ?";

			$query = $this->db->query($sql, array(
				$data['pf']['nome'],
				$data['pf']['nome_social'],
				$data['pf']['data_nascimento'],
				$data['pf']['cpf'],
				$data['pf']['rg'],
				$data['pf']['sexo'],
				$data['pf']['email'],
				$data['pf']['telefone'],
				$data['pf']['telefone2'],
				$data['pf']['pai'],
				$data['pf']['mae'],
				$data['pf']['responsavel'],
				$data['endereco']['logradouro'],
				$data['endereco']['numero'],
				$data['endereco']['bairro'],
				$data['endereco']['complemento'],
				$data['endereco']['cep'],
				$data['endereco']['id_cidade'],
				$data['id_cliente']
			));

			if ($query) {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao atualizar o cliente. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_OK);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_OK);
		}

	}

	public function cpf_get( $cpf=null )
	{
		if ( !$cpf ) {
			$this->response(array(
				'status' 	=> FALSE
			), REST_Controller::HTTP_BAD_REQUEST);
		} else {
			$cpf = str_replace(array('.', '-'), '', $cpf);
			$options = array(
				'fields'	=> 'tb_cliente.id_cliente, tb_pessoa_fisica.id_pessoa_fisica, tb_pessoa_fisica.nome, tb_pessoa_fisica.nome_social, tb_pessoa_fisica.cpf',
				'join'		=> array(
					array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'inner')
				),
				'where'		=> "tb_pessoa_fisica.cpf = '$cpf'"
				// 'where'		=> array('tb_pessoa_fisica.cpf', $cpf)
			);

			$resultado = $this->cliente_model->get(null, $options);

			if ($resultado) {
				$this->response($resultado, REST_Controller::HTTP_OK);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum cliente encontrado'
				), REST_Controller::HTTP_OK);
			}
		}
	}

	public function procedimento_post() {
		$uid = $this->rest->user_id;

		$data = json_decode($this->input->raw_input_stream, true);
		$data['codigo'] = $uid .'.'. $data['id_parceiro_procedimento'] .'.'. time();
		$data['created_by'] = $uid;

		$validacao = $this->validaProcedimento($data);

		if ( $validacao === TRUE ) {
			if ($this->cliente_model->post($data)) {
				$this->response(array(
					'status'	=> TRUE,
					'message'	=> 'Procedimento cadastrado com sucesso.'
				), REST_Controller::HTTP_CREATED);
			} else {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao cadastrar o usuario'
				), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	private function valida($data)
	{
		$this->load->model('endereco_model');
		$rules = array_merge($this->cliente_model->rules, $this->endereco_model->rules);

		if (!$data['pf']['id_pessoa_fisica']) {
			$this->load->model('pessoaFisica_model');
			$rules = array_merge($rules, $this->pessoaFisica_model->rules);
		}

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}

	private function validaProcedimento($data)
	{
		$rules = array_merge($this->cliente_model->rules_procedimento);

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}

	private function _generateRandomString($length = 12) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
}