<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Parceiro extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('parceiro_model');

		$this->methods['index_get'] = array('limit' => 500, 'level' => 4);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['index_put'] = array('limit' => 60, 'level' => 5);
		$this->methods['index_delete'] = array('limit' => 60, 'level' => 5);
		$this->methods['financeiro_get'] = array('limit' => 500, 'level' => 5);
		/*$this->methods['cidade_delete']['limit'] = 50; // 50 requests per hour per user/key*/
	}

	public function index_get( $id=null, $field=null )
	{
		$options = array(
			'fields' => '*, tb_usuario.email AS usuario_email, tb_parceiro.nome AS parceiro_nome, tb_parceiro.telefone AS parceiro_telefone, tb_endereco.nome AS endereco_nome, tb_pessoa_fisica.nome AS pf_nome, tb_cidade.uf as estado, tb_cidade.nome as cidade, tb_pessoa_fisica.telefone as pf_telefone, tb_pessoa_fisica.telefone2 as pf_telefone2, tb_pessoa_fisica.email as pf_email, tb_pessoa_juridica.telefone as pj_telefone, tb_pessoa_juridica.telefone2 as pj_telefone2, tb_pessoa_juridica.email as pj_email',
			'join' => array(
				array('tb_endereco', 'tb_endereco.id_endereco = tb_parceiro.id_endereco', 'inner'),
				array('tb_cidade', 'tb_cidade.id_cidade = tb_endereco.id_cidade', 'inner'),
				array('tb_usuario', 'tb_usuario.id_pessoa = tb_parceiro.id_pessoa', 'left'),
				array('tb_pessoa', 'tb_pessoa.id_pessoa = tb_parceiro.id_pessoa', 'inner'),
				array('tb_pessoa_juridica', 'tb_pessoa_juridica.id_pessoa = tb_pessoa.id_pessoa', 'left'),
				array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa = tb_pessoa.id_pessoa', 'left')
			),
			'order' => 'asc',
			'sort' => 'tb_parceiro.nome'
		);

		if (!$id) {
			$options['where'] = array( 'tb_parceiro.status' => 1 );
		}

		// array_push($options['join'], );
		/*if ($data['pessoa_juridica'] == '1'){
		} else {
			array_push($options['join'], array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa = tb_pessoa.id_pessoa', 'inner'));
		}*/


		if ($this->rest->level > 45) {
			$options = array_merge($options, $_GET);
		} else if ($this->rest->level < 5) {
			$options['join'][] = array('tb_usuario', 'tb_usuario.id_pessoa = tb_pessoa.id_pessoa', 'inner');
			$options['where']['tb_usuario.id_usuario'] = $this->rest->user_id;
		}

		if (!$id || $field) {
			$options['fields'] = 'tb_parceiro.id_parceiro, tb_parceiro.nome, tb_parceiro.telefone AS parceiro_telefone, tb_parceiro.status, tb_parceiro.imagem, tb_pessoa.pessoa_juridica as tipo, tb_pessoa_juridica.cnpj, tb_pessoa_fisica.cpf';
			if ($field) {
				switch ($field) {
					case 'nome':
						$options['where'] = "tb_parceiro.nome LIKE '$id%'";
						break;
					case 'cpf':
						$options['where'] = "tb_pessoa_fisica.cpf LIKE '$id%'";
						break;
					case 'cnpj':
						$options['where'] = "tb_pessoa_juridica.cnpj LIKE '$id%'";
						break;
				}
			}
			$resultado = $this->parceiro_model->get(null, $options);
		} else {
			$resultado = $this->parceiro_model->get($id, $options);
		}

		if ($resultado) {

			if ($id && !$field) {
				$parceiro = array(
					'id_parceiro'			=> $resultado[0]['id_parceiro'],
					'nome'					=> $resultado[0]['parceiro_nome'],
					'horario_funcionamento'	=> $resultado[0]['horario_funcionamento'],
					'ciclo_pagamento'		=> $resultado[0]['ciclo_pagamento'],
					'data_fechamento_ciclo'	=> $resultado[0]['data_fechamento_ciclo'],
					'telefone'				=> $resultado[0]['parceiro_telefone'],
					'imagem'				=> $resultado[0]['imagem'],
					'banco'					=> $resultado[0]['banco'],
					'agencia'				=> $resultado[0]['agencia'],
					'conta'					=> $resultado[0]['conta'],
					'status'				=> $resultado[0]['status'],
					'tipo'					=> $resultado[0]['pessoa_juridica'],
					'id_pessoa'				=> $resultado[0]['id_pessoa']
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

				$usuario = array(
					'id_usuario'	=> $resultado[0]['id_usuario'],
					'email'			=> $resultado[0]['usuario_email']
				);

				if ($resultado[0]['pessoa_juridica'] == '1'){
					$pessoa_tipo = 'pj';

					$pessoa = array(
						'id_pessoa_juridica'	=> $resultado[0]['id_pessoa_juridica'],
						'razao_social'			=> $resultado[0]['razao_social'],
						'nome_fantasia'			=> $resultado[0]['nome_fantasia'],
						'cnpj'					=> $resultado[0]['cnpj'],
						'telefone'				=> $resultado[0]['pj_telefone'],
						'telefone2'				=> $resultado[0]['pj_telefone2'],
						'email'					=> $resultado[0]['pj_email'],
						'nome_responsavel'		=> $resultado[0]['nome_responsavel'],
						'cpf_responsavel'		=> $resultado[0]['cpf_responsavel'],
						'telefone_responsavel'	=> $resultado[0]['telefone_responsavel'],
						'email_responsavel'		=> $resultado[0]['email_responsavel']
					);
				} else {
					$pessoa_tipo = 'pf';

					$pessoa = array(
						'id_pessoa_fisica'	=> $resultado[0]['id_pessoa_fisica'],
						'nome'				=> $resultado[0]['pf_nome'],
						'nome_social'		=> $resultado[0]['nome_social'],
						'sexo'				=> $resultado[0]['sexo'],
						'email'				=> $resultado[0]['pf_email'],
						'rg'				=> $resultado[0]['rg'],
						'cpf'				=> $resultado[0]['cpf'],
						'data_nascimento'	=> $resultado[0]['data_nascimento'],
						'telefone'			=> $resultado[0]['pf_telefone'],
						'telefone2'			=> $resultado[0]['pf_telefone2'],
						'pai'				=> $resultado[0]['pai'],
						'mae'				=> $resultado[0]['mae'],
						'responsavel'		=> $resultado[0]['responsavel']
					);
				}

				$resultado = (object) array(
					'id_parceiro'			=> $parceiro['id_parceiro'],
					'nome'					=> $parceiro['nome'],
					'imagem'				=> $parceiro['imagem'],
					'banco'					=> $parceiro['banco'],
					'agencia'				=> $parceiro['agencia'],
					'conta'					=> $parceiro['conta'],
					'horario_funcionamento'	=> $parceiro['horario_funcionamento'],
					'ciclo_pagamento'		=> $parceiro['ciclo_pagamento'],
					'data_fechamento_ciclo'	=> $parceiro['data_fechamento_ciclo'],
					'telefone'				=> $parceiro['telefone'],
					'status'				=> $parceiro['status'],
					'tipo'					=> $parceiro['tipo'],
					'endereco'				=> $endereco,
					$pessoa_tipo			=> $pessoa,
					'email'					=> $usuario['email'],
					'id_usuario'			=> $usuario['id_usuario'],
					'id_pessoa'				=> $parceiro['id_pessoa']
				);
			}

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum parceiro encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);

		$validacao = $this->valida($data);

		if ( $validacao === TRUE ) {

			$pessoa = array(
				'pessoa_juridica' => $data['tipo']
			);

			$endereco = array(
				'nome'			=> 'Parceiro',
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

			if ($data['id_pessoa']) {
				$id_pessoa = $data['id_pessoa'];
			} else {
				$this->db->insert('tb_pessoa', $pessoa);
				$id_pessoa = $this->db->insert_id();

				$usuario = array(
					'email'			=> $data['email'],
					'senha'			=> $data['senha'],
					'id_pessoa'		=> $id_pessoa,
					'id_role'		=> 4
				);
				$this->db->insert('tb_usuario', $usuario);
				$id_usuario = $this->db->insert_id();
				
				if ( $data['tipo'] == '1' ) {
					$pessoa_juridica = array(
						'razao_social'			=> $data['pj']['razao_social'],
						'nome_fantasia'			=> $data['pj']['nome_fantasia'],
						'cnpj'					=> $data['pj']['cnpj'],
						'email'					=> $data['pj']['email'],
						'telefone'				=> $data['pj']['telefone'],
						'telefone2'				=> $data['pj']['telefone2'],
						'nome_responsavel'		=> $data['pj']['nome_responsavel'],
						'cpf_responsavel'		=> $data['pj']['cpf_responsavel'],
						'telefone_responsavel'	=> $data['pj']['telefone_responsavel'],
						'email_responsavel'		=> $data['pj']['email_responsavel'],
						'id_pessoa' 			=> $id_pessoa
					);
					$telefone = $data['pj']['telefone'];
					$this->db->insert('tb_pessoa_juridica', $pessoa_juridica);
					$id_pessoa_juridica = $this->db->insert_id();
				} else {
					$pessoa_fisica = array(
						'nome'				=> $data['pf']['nome'],
						'nome_social'		=> $data['pf']['nome_social'],
						'sexo'				=> $data['pf']['sexo'],
						'rg'				=> $data['pf']['rg'],
						'cpf'				=> $data['pf']['cpf'],
						'telefone'			=> $data['pf']['telefone'],
						'telefone2'			=> $data['pf']['telefone2'],
						'data_nascimento'	=> $data['pf']['data_nascimento'],
						'pai'				=> $data['pf']['pai'],
						'mae'				=> $data['pf']['mae'],
						'responsavel'		=> $data['pf']['responsavel'],
						'id_pessoa' 		=> $id_pessoa
					);
					$telefone = $data['pf']['telefone'];
					$this->db->insert('tb_pessoa_fisica', $pessoa_fisica);
					$id_pessoa_fisica = $this->db->insert_id();
				}
			}

			$parceiro = array(
				'nome' 					=> $data['nome'],
				'banco'					=> $data['banco'],
				'agencia'				=> $data['agencia'],
				'conta'					=> $data['conta'],
				'horario_funcionamento'	=> $data['horario_funcionamento'],
				'ciclo_pagamento'		=> $data['ciclo_pagamento'],
				'telefone'				=> $telefone,
				'id_pessoa'				=> (int) $id_pessoa,
				'id_endereco'			=> $id_endereco,
				'created_by'			=> $this->rest->user_id
			);

			$this->db->insert('tb_parceiro', $parceiro);
			$id_parceiro = $this->db->insert_id();

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao cadastrar o parceiro. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_OK);
			} else {
				$this->response($parceiro, REST_Controller::HTTP_CREATED);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		} 
	}

	public function index_put() {
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data, true);

		if ( $validacao === TRUE ) {
			if ( $data['tipo'] == '1' ) { 
				$sql = "UPDATE tb_parceiro AS parc
					INNER JOIN tb_usuario AS usr ON usr.id_pessoa = parc.id_pessoa
					INNER JOIN tb_endereco AS ende ON ende.id_endereco = parc.id_endereco
					INNER JOIN tb_pessoa_juridica AS pj ON pj.id_pessoa = parc.id_pessoa
					SET 
						parc.nome = ?,
						parc.status = ?,
						parc.banco = ?,
						parc.agencia = ?,
						parc.conta = ?,
						parc.horario_funcionamento = ?,
						parc.ciclo_pagamento = ?,
						parc.data_fechamento_ciclo = ?,
						parc.telefone = ?,
						pj.razao_social = ?,
						pj.nome_fantasia = ?,
						pj.cnpj = ?,
						pj.email = ?,
						pj.telefone = ?,
						pj.telefone2 = ?,
						pj.nome_responsavel = ?,
						pj.cpf_responsavel = ?,
						pj.telefone_responsavel = ?,
						pj.email_responsavel = ?,
						ende.logradouro = ?,
						ende.numero = ?,
						ende.bairro = ?,
						ende.complemento = ?,
						ende.cep = ?,
						ende.id_cidade = ?,
						usr.email = ?
					WHERE parc.id_parceiro = ?";
				$query = $this->db->query($sql, array(
					$data['nome'],
					$data['status'],
					$data['banco'],
					$data['agencia'],
					$data['conta'],
					$data['horario_funcionamento'],
					$data['ciclo_pagamento'],
					$data['data_fechamento_ciclo'],
					$data['pj']['telefone'],
					$data['pj']['razao_social'],
					$data['pj']['nome_fantasia'],
					$data['pj']['cnpj'],
					$data['pj']['email'],
					$data['pj']['telefone'],
					$data['pj']['telefone2'],
					$data['pj']['nome_responsavel'],
					$data['pj']['cpf_responsavel'],
					$data['pj']['telefone_responsavel'],
					$data['pj']['email_responsavel'],
					$data['endereco']['logradouro'],
					$data['endereco']['numero'],
					$data['endereco']['bairro'],
					$data['endereco']['complemento'],
					$data['endereco']['cep'],
					$data['endereco']['id_cidade'],
					$data['email'],
					$data['id_parceiro']
				));
			} else {
				$sql = "UPDATE tb_parceiro AS parc
					INNER JOIN tb_usuario AS usr ON usr.id_pessoa = parc.id_pessoa
					INNER JOIN tb_endereco AS ende ON ende.id_endereco = parc.id_endereco
					INNER JOIN tb_pessoa_fisica AS pf ON pf.id_pessoa = parc.id_pessoa
					SET 
						parc.nome = ?,
						parc.status = ?,
						parc.banco = ?,
						parc.agencia = ?,
						parc.conta = ?,
						parc.horario_funcionamento = ?,
						parc.ciclo_pagamento = ?,
						parc.data_fechamento_ciclo = ?,
						parc.telefone = ?,
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
						ende.id_cidade = ?,
						usr.email = ?
					WHERE parc.id_parceiro = ?";
				$query = $this->db->query($sql, array(
					$data['nome'],
					$data['status'],
					$data['banco'],
					$data['agencia'],
					$data['conta'],
					$data['horario_funcionamento'],
					$data['ciclo_pagamento'],
					$data['data_fechamento_ciclo'],
					$data['pf']['telefone'],
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
					$data['email'],
					$data['id_parceiro']
				));
			}

			if (!empty($data['senha'])) {
				$sql = 'UPDATE tb_usuario 
					INNER JOIN tb_parceiro ON tb_parceiro.id_pessoa = tb_usuario.id_pessoa
					SET tb_usuario.senha = ? 
					WHERE tb_parceiro.id_parceiro = ?';
				$this->db->query($sql, 
					array(
						$data['senha'],
						$data['id_parceiro']
					)
				);
			}

			if ($query) {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao atualizar o parceiro. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_OK);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_OK);
		}
	}

	public function index_delete( $id=null )
	{
		if ( $id ) {
			$this->db->trans_start();
			$where = array(
				'id_parceiro' 	=> $id,
				'status'		=> 1
			);
			$this->db->update('tb_parceiro', array('status' => 0), $where);
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Ocorreu um erro ao excluir o parceiro.'
				), REST_Controller::HTTP_OK);
			} else {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			}
		} else {
			$this->response(null, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function	financeiro_get( $id_parceiro=null )
	{
		$sql = "SELECT tb_parceiro.id_parceiro, tb_parceiro.nome, tb_pessoa_fisica.cpf, tb_pessoa_juridica.cnpj, SUM(tb_agendamento_procedimento.quantidade * tb_parceiro_procedimento.valor_parceiro) AS total_parceiro FROM tb_parceiro
			LEFT JOIN tb_pessoa_fisica ON tb_pessoa_fisica.id_pessoa = tb_parceiro.id_pessoa
			LEFT JOIN tb_pessoa_juridica ON tb_pessoa_juridica.id_pessoa = tb_parceiro.id_pessoa
			INNER JOIN tb_agendamento ON tb_agendamento.id_parceiro = tb_parceiro.id_parceiro
			INNER JOIN tb_agendamento_procedimento ON tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento
			INNER JOIN tb_parceiro_procedimento ON tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento
			WHERE tb_agendamento.status = 'realizado'
			GROUP BY tb_agendamento.id_parceiro";
		
		$query = $this->db->query($sql);

		if ($query->num_rows()) {
			$this->response($query->result_array(), REST_Controller::HTTP_OK);
		} else {
			$this->response(array(), REST_Controller::HTTP_OK);
		}
	}

	private function valida($data, $update=false)
	{
		$this->form_validation->set_data($data);
		$this->load->model('endereco_model');

		$rules = array_merge($this->parceiro_model->rules, $this->endereco_model->rules);

		if ($update) {
			if (!empty($data['senha'])) {
				$rules = array_merge($rules, $this->parceiro_model->rules_senha);
			}
		} else {
			$rules = array_merge($rules, $this->parceiro_model->rules_create, $this->parceiro_model->rules_usuario, $this->parceiro_model->rules_senha);
		}

		if (!$data['id_pessoa']) {
			if ( $data['tipo'] == '1' ) {
				$this->load->model('pessoaJuridica_model');
				$pessoaRules = $this->pessoaJuridica_model->rules;
			} else {
				$this->load->model('pessoaFisica_model');
				$pessoaRules = $this->pessoaFisica_model->rules;
			}
			$rules = array_merge($rules, $pessoaRules);
		}


		$this->form_validation->set_rules($rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}
}