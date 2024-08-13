<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Agendamento extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('agendamento_model');

		$this->methods['index_get'] = array('limit' => 500, 'level' => 2);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['cliente_get'] = array('limit' => 300, 'level' => 5);
		$this->methods['representante_get'] = array('limit' => 300, 'level' => 5);
		$this->methods['realiza_get'] = array('limit' => 300, 'level' => 2);
		$this->methods['cancelar_put'] = array('limit' => 150, 'level' => 2);
		$this->methods['parceiro_get'] = array('limit' => 300, 'level' => 4);
		$this->methods['realizados_get'] = array('limit' => 300, 'level' => 5);
		$this->methods['procedimento_delete'] = array('limit' => 60, 'level' => 5);
		/*
		$this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50; // 50 requests per hour per user/key
        */
	}

	public function index_get( $id = null, $field = null, $id_parceiro = null )
	{
		$options = array(
			'fields'	=> 'tb_agendamento.id_agendamento, 
				tb_agendamento.codigo,
				tb_agendamento.status,
				tb_agendamento.representante_pago,
				tb_agendamento.id_representante,
				tb_agendamento.id_parceiro,
				tb_parceiro.nome AS nome_parceiro,
				tb_parceiro.telefone AS parceiro_telefone,
				tb_agendamento.id_cliente,
				tb_pessoa_fisica.nome,
				tb_pessoa_fisica.nome_social,
				tb_pessoa_fisica.cpf,
				tb_pessoa_fisica.telefone as cliente_telefone,
				SUM((tb_parceiro_procedimento.valor_total * tb_agendamento_procedimento.quantidade) - tb_agendamento_procedimento.desconto) AS total,
				tb_agendamento.data_criacao',
			'join'		=> array(
				array('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento', 'inner'),
				array('tb_parceiro', 'tb_parceiro.id_parceiro = tb_agendamento.id_parceiro', 'inner'),
				array('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner'),
				array('tb_cliente', 'tb_cliente.id_cliente = tb_agendamento.id_cliente', 'inner'),
				array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'inner'),
			),
			'group_by'	=> 'tb_agendamento.id_agendamento'
		);

		if ($this->rest->level < 5) {
			$parceiroUsuario = $this->getParceiroUsuario();
			if (!$parceiroUsuario) {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Parceiro não encontrado'
				), REST_Controller::HTTP_OK);
				return false;
			}
			$options['where'] = array(
				'tb_agendamento.id_parceiro' => $parceiroUsuario['id_parceiro'],
				'tb_agendamento.status !=' => 'pendente' 
			);
			if ($id) {
				$options['where']['tb_agendamento.id_agendamento'] = $id;
			}
			$resultado = $this->agendamento_model->get(null, $options);
		} else {
			if ($this->rest->level > 45) {
				$options = array_merge($options, $_GET);
			}
			if ($id_parceiro) {
				$options['where']['tb_agendamento.id_parceiro'] = $id_parceiro;
			}
			if (isset($_GET['data_criacao']) && $_GET['data_criacao']) {
				$options['where']["DATE(tb_agendamento.data_criacao)"] = $_GET['data_criacao'];
			}
			if (isset($_GET['status']) && $_GET['status']) {
				$options['where']['tb_agendamento.status'] = $_GET['status'];
			}
			if ($id && $field) {
				switch ($field) {
					case 'numero':
						$options['where']['tb_agendamento.codigo LIKE'] = "'$id%'";
						break;
					case 'cpf':
						$options['where']['tb_pessoa_fisica.cpf LIKE'] = "'$id%'";
						break;
					case 'id_cliente':
						$options['where']['tb_agendamento.id_cliente'] = $id;
						break;
					case 'id_representante':
						$options['where']['tb_agendamento.id_representante'] = $id;
						break;
				}
				$resultado = $this->agendamento_model->get(null, $options);
			} else {
				$resultado = $this->agendamento_model->get($id, $options);
			}
		}


		if ($resultado[0]['total']) {

			foreach ($resultado as $key => $agendamento) {
				$resultado[$key] = array(
					'id_agendamento'	=> $agendamento['id_agendamento'],
					'codigo'			=> $agendamento['codigo'],
					'status'			=> $agendamento['status'],
					'total'				=> $agendamento['total'],
					'data_criacao'		=> $agendamento['data_criacao'],
					'representante_pago'=> $agendamento['representante_pago'],
					'id_representante'	=> $agendamento['id_representante'],
					'cliente'			=> array(
						'id_cliente'	=> $agendamento['id_cliente'],
						'nome'			=> $agendamento['nome'],
						'nome_social'	=> $agendamento['nome_social'],
						'cpf'			=> $agendamento['cpf'],
						'telefone'		=> $agendamento['cliente_telefone']
					),
					'parceiro'			=> array(
						'id_parceiro'	=> $agendamento['id_parceiro'],
						'nome'			=> $agendamento['nome_parceiro'],
						'telefone'		=> $agendamento['parceiro_telefone']
					)
				);
			}

			if ($id) {
				$this->db->reset_query();
				$this->db->select('tb_parceiro.nome as parceiro_nome, 
					tb_parceiro.horario_funcionamento, 
					tb_parceiro.telefone AS parceiro_telefone, 
					tb_endereco.logradouro, 
					tb_endereco.bairro, 
					tb_endereco.numero, 
					tb_endereco.complemento, 
					tb_endereco.cep, 
					tb_endereco.id_cidade, 
					tb_agendamento_pagamento.valor_representante, 
					tb_agendamento_pagamento.valor_adicional_cartao, 
					tb_agendamento_procedimento.quantidade, 
					tb_agendamento_procedimento.desconto, 
					tb_parceiro_procedimento.id_parceiro_procedimento, 
					tb_parceiro_procedimento.valor_total, 
					tb_parceiro_procedimento.valor_parceiro, 
					tb_parceiro_procedimento.comissao, 
					tb_parceiro_procedimento.horario_atendimento, 
					tb_parceiro_procedimento.observacao, 
					tb_parceiro_procedimento.status,
					tb_procedimento.nome, 
					tb_procedimento.codigo_tuss, 
					tb_procedimento.tipo');
				$this->db->where('tb_agendamento_procedimento.id_agendamento', $resultado[0]['id_agendamento']);
				$this->db->join('tb_agendamento', 'tb_agendamento.id_agendamento = tb_agendamento_procedimento.id_agendamento', 'inner');
				$this->db->join('tb_agendamento_pagamento', 'tb_agendamento_pagamento.id_agendamento_pagamento = tb_agendamento.id_agendamento_pagamento', 'inner');
				$this->db->join('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner');
				$this->db->join('tb_procedimento', 'tb_parceiro_procedimento.id_procedimento = tb_procedimento.id_procedimento', 'inner');
				$this->db->join('tb_parceiro', 'tb_parceiro_procedimento.id_parceiro = tb_parceiro.id_parceiro', 'inner');
				$this->db->join('tb_endereco', 'tb_parceiro.id_endereco = tb_endereco.id_endereco', 'inner');
				$procedimentos = $this->db->get('tb_agendamento_procedimento')->result_array();

				$resultado[0]['procedimentos'] = array();
				$resultado[0]['valor_representante'] = $procedimentos[0]['valor_representante'];
				$resultado[0]['valor_adicional_cartao'] = $procedimentos[0]['valor_adicional_cartao'];

				$resultado[0]['parceiro'] = array(
					'nome' => $procedimentos[0]['parceiro_nome'],
					'horario_funcionamento' => $procedimentos[0]['horario_funcionamento'],
					'telefone' => $procedimentos[0]['parceiro_telefone'],
					'endereco' => array(
						'logradouro' => $procedimentos[0]['logradouro'],
						'bairro' => $procedimentos[0]['bairro'],
						'numero' => $procedimentos[0]['numero'],
						'complemento' => $procedimentos[0]['complemento'],
						'cep' => $procedimentos[0]['cep'],
						'id_cidade' => $procedimentos[0]['id_cidade']
					)
				);

				foreach ($procedimentos as $procedimento) {
					array_push($resultado[0]['procedimentos'], array(
						'id_parceiro_procedimento' => $procedimento['id_parceiro_procedimento'],
						'nome' => $procedimento['nome'],
						'codigo_tuss' => $procedimento['codigo_tuss'],
						'tipo' => $procedimento['tipo'],
						'valor_total' => $procedimento['valor_total'],
						'valor_parceiro' => $procedimento['valor_parceiro'],
						'desconto' => $procedimento['desconto'],
						'comissao' => $procedimento['comissao'],
						'quantidade' => $procedimento['quantidade'],
						'horario_atendimento' => $procedimento['horario_atendimento'],
						'observacao' => $procedimento['observacao'],
						'status' => $procedimento['status']
					));
				}
			}

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum agendamento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);

		// Código criado diretamente pelo banco
		// $codigo = $data['id_cliente'] . sprintf("%014d", time());
		// 'codigo'		=> $codigo,
		$agendamento = array(
			'status'		=> 'pendente',
			'id_cliente'	=> $data['id_cliente'],
			'created_by'	=> $this->rest->user_id
		);

		if (isset($data['id_representante'])) {
			$agendamento['id_representante'] = $data['id_representante'];
		}

		$validacao = $this->valida($agendamento);

		if ( $validacao === TRUE ) {	
			// Transaction para cadastrar o agendamento e seus procedimentos
			// Será cadastrado um agendamento por parceiro
			$this->db->trans_start();

			$this->load->model('caixa_model');
			$id_caixa = $this->caixa_model->_get_caixa_aberto($this->rest->user_id);

			if (!$id_caixa) {
				$this->db->trans_complete();
				return $this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Usuário não possui caixa aberto'
				), REST_Controller::HTTP_OK);
			}
			
			$id_agendamento = array();
			$agendamento_procedimentos = array();
			$agendamento_pagamento = array(
				'valor_pago_especie'		=> $data['valor_pago_especie'],
				'valor_pago_cartao'			=> $data['valor_pago_cartao'],
				'valor_adicional_cartao'	=> $data['valor_adicional_cartao'],
				'valor_representante'		=> $data['valor_representante'],
				'valor_troco'				=> $data['valor_troco'],
				'id_caixa'					=> $id_caixa
			);
			
			// Cadastra pagamento para os agendamentos
			$this->db->insert('tb_agendamento_pagamento', $agendamento_pagamento);
			$id_agendamento_pagamento = $this->db->insert_id();
			
			foreach ($data['procedimentos'] as $procedimento) {
				// Verifica se ja existe agendamento para o parceiro
				if (!isset($id_agendamento[$procedimento['id_parceiro']])) {
					$agendamento['id_agendamento_pagamento'] = $id_agendamento_pagamento;
					$agendamento['id_parceiro'] = $procedimento['id_parceiro'];
					// $agendamento['codigo']++;
					$this->db->insert('tb_agendamento', $agendamento);
					$id_agendamento[$procedimento['id_parceiro']] = $this->db->insert_id();
					$this->db->set('codigo', str_pad($id_agendamento[$procedimento['id_parceiro']], 9, '0', STR_PAD_LEFT))
						->where('id_agendamento', $id_agendamento[$procedimento['id_parceiro']])
						->update('tb_agendamento');
				}
				array_push($agendamento_procedimentos, array(
					'id_agendamento' 			=> $id_agendamento[$procedimento['id_parceiro']],
					'id_parceiro_procedimento'	=> $procedimento['id_parceiro_procedimento'],
					'quantidade' 				=> $procedimento['quantidade'],
					'desconto'					=> $procedimento['desconto']
				));
			}

			$this->db->insert_batch('tb_agendamento_procedimento', $agendamento_procedimentos);
			
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao cadastrar o agendamento. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->response(array_values($id_agendamento), REST_Controller::HTTP_CREATED);
			}

		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function cliente_get($id_cliente = null)
	{
		if ($id_cliente) {
			$this->index_get($id_cliente, 'id_cliente');
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum agendamento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function representante_get($id_representante = null)
	{
		if ($id_representante) {
			$this->index_get($id_representante, 'id_representante');
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum agendamento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function parceiro_get( $id_parceiro = null, $id = null, $field = null )
	{
		if ($id_parceiro) {
			$this->index_get($id, $field, $id_parceiro);
		} else {
			$parceiroUsuario = $this->getParceiroUsuario();

			if (!$parceiroUsuario) {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum agendamento encontrado'
				), REST_Controller::HTTP_OK);
				return false;
			}

			$options = array(
				'fields'	=> 'tb_agendamento.id_agendamento, tb_agendamento.codigo, tb_agendamento.status, tb_agendamento.id_parceiro, tb_parceiro.nome AS nome_parceiro, tb_parceiro.telefone AS parceiro_telefone, tb_agendamento.id_cliente, tb_pessoa_fisica.nome, tb_pessoa_fisica.nome_social, tb_pessoa_fisica.cpf, tb_pessoa_fisica.telefone AS cliente_telefone, SUM(tb_parceiro_procedimento.valor_parceiro * tb_agendamento_procedimento.quantidade) AS total_parceiro, tb_agendamento.data_criacao',
				'join'		=> array(
					array('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento', 'inner'),
					array('tb_parceiro', 'tb_parceiro.id_parceiro = tb_agendamento.id_parceiro', 'inner'),
					array('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner'),
					array('tb_cliente', 'tb_cliente.id_cliente = tb_agendamento.id_cliente', 'inner'),
					array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'inner'),
				),
				'where'		=> array(
					'tb_agendamento.id_parceiro' => $parceiroUsuario['id_parceiro'],
					'tb_agendamento.status !=' => 'pendente' 
				),
				'group_by'	=> 'tb_agendamento.id_agendamento'
			);
			
			if ($id) {
				$options['where']['tb_agendamento.id_agendamento'] = $id;
			}
			$resultado = $this->agendamento_model->get(null, $options);

			if ($resultado[0]['total_parceiro']) {

				foreach ($resultado as $key => $agendamento) {
					$resultado[$key] = array(
						'id_agendamento'	=> $agendamento['id_agendamento'],
						'codigo'			=> $agendamento['codigo'],
						'status'			=> $agendamento['status'],
						'total_parceiro'	=> $agendamento['total_parceiro'],
						'data_criacao'		=> $agendamento['data_criacao'],
						'cliente'			=> array(
							'id_cliente'	=> $agendamento['id_cliente'],
							'nome'			=> $agendamento['nome'],
							'nome_social'	=> $agendamento['nome_social'],
							'cpf'			=> $agendamento['cpf'],
							'telefone'		=> $agendamento['cliente_telefone']
						),
						'parceiro'			=> array(
							'id_parceiro'	=> $agendamento['id_parceiro'],
							'nome'			=> $agendamento['nome_parceiro'],
							'telefone'		=> $agendamento['parceiro_telefone']
						)
					);
				}
	
				if ($id) {
					$this->db->reset_query();
					$this->db->select('tb_parceiro.nome as parceiro_nome, tb_parceiro.horario_funcionamento, tb_parceiro.telefone AS parceiro_telefone, tb_endereco.logradouro, tb_endereco.bairro, tb_endereco.numero, tb_endereco.complemento, tb_endereco.cep, tb_endereco.id_cidade, tb_agendamento_procedimento.quantidade, tb_parceiro_procedimento.id_parceiro_procedimento, tb_parceiro_procedimento.valor_total, tb_parceiro_procedimento.valor_parceiro, tb_parceiro_procedimento.comissao, tb_parceiro_procedimento.observacao, tb_parceiro_procedimento.status,
						tb_procedimento.nome, tb_procedimento.codigo_tuss, tb_procedimento.tipo');
					$this->db->where('tb_agendamento_procedimento.id_agendamento', $resultado[0]['id_agendamento']);
					$this->db->join('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner');
					$this->db->join('tb_procedimento', 'tb_parceiro_procedimento.id_procedimento = tb_procedimento.id_procedimento', 'inner');
					$this->db->join('tb_parceiro', 'tb_parceiro_procedimento.id_parceiro = tb_parceiro.id_parceiro', 'inner');
					$this->db->join('tb_endereco', 'tb_parceiro.id_endereco = tb_endereco.id_endereco', 'inner');
					$procedimentos = $this->db->get('tb_agendamento_procedimento')->result_array();
	
					$resultado[0]['procedimentos'] = array();
	
					$resultado[0]['parceiro'] = array(
						'nome' => $procedimentos[0]['parceiro_nome'],
						'horario_funcionamento' => $procedimentos[0]['horario_funcionamento'],
						'telefone' => $procedimentos[0]['parceiro_telefone'],
						'endereco' => array(
							'logradouro' => $procedimentos[0]['logradouro'],
							'bairro' => $procedimentos[0]['bairro'],
							'numero' => $procedimentos[0]['numero'],
							'complemento' => $procedimentos[0]['complemento'],
							'cep' => $procedimentos[0]['cep'],
							'id_cidade' => $procedimentos[0]['id_cidade']
						)
					);
	
					foreach ($procedimentos as $procedimento) {
						array_push($resultado[0]['procedimentos'], array(
							'id_parceiro_procedimento' => $procedimento['id_parceiro_procedimento'],
							'nome' => $procedimento['nome'],
							'codigo_tuss' => $procedimento['codigo_tuss'],
							'tipo' => $procedimento['tipo'],
							'valor_total' => $procedimento['valor_total'],
							'valor_parceiro' => $procedimento['valor_parceiro'],
							'comissao' => $procedimento['comissao'],
							'quantidade' => $procedimento['quantidade'],
							'observacao' => $procedimento['observacao'],
							'status' => $procedimento['status']
						));
					}
				}
	
				$this->response($resultado, REST_Controller::HTTP_OK);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum agendamento encontrado'
				), REST_Controller::HTTP_OK);
			}
		} 
	}

	public function realizados_get( $id_parceiro = null )
	{
		if ($id_parceiro) {
			$options = array(
				'fields'	=> 'tb_agendamento.id_agendamento, tb_agendamento.codigo, tb_agendamento.status, tb_agendamento.id_parceiro, tb_parceiro.nome AS nome_parceiro, tb_parceiro.telefone AS parceiro_telefone, tb_agendamento.id_cliente, tb_pessoa_fisica.nome, tb_pessoa_fisica.nome_social, tb_pessoa_fisica.cpf, SUM(tb_parceiro_procedimento.valor_parceiro * tb_agendamento_procedimento.quantidade) AS total_parceiro, SUM(tb_parceiro_procedimento.comissao * tb_agendamento_procedimento.quantidade) AS total_comissao, tb_agendamento.data_criacao',
				'join'		=> array(
					array('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento', 'inner'),
					array('tb_parceiro', 'tb_parceiro.id_parceiro = tb_agendamento.id_parceiro', 'inner'),
					array('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner'),
					array('tb_cliente', 'tb_cliente.id_cliente = tb_agendamento.id_cliente', 'inner'),
					array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'inner'),
				),
				'group_by'	=> 'tb_agendamento.id_agendamento',
				'where'		=> array(
					'tb_agendamento.id_parceiro'	=> $id_parceiro,
					'tb_agendamento.status'			=> 'realizado',
					'tb_agendamento.id_financeiro'	=> null
				)
			);
			
			$resultado = $this->agendamento_model->get(null, $options);

			if ($resultado) {

				foreach ($resultado as $key => $agendamento) {
					$resultado[$key] = array(
						'id_agendamento'	=> $agendamento['id_agendamento'],
						'codigo'			=> $agendamento['codigo'],
						'status'			=> $agendamento['status'],
						'total_parceiro'	=> $agendamento['total_parceiro'],
						'total_comissao'	=> $agendamento['total_comissao'],
						'data_criacao'		=> $agendamento['data_criacao'],
						'cliente'			=> array(
							'id_cliente'	=> $agendamento['id_cliente'],
							'nome'			=> $agendamento['nome'],
							'nome_social'	=> $agendamento['nome_social'],
							'cpf'			=> $agendamento['cpf']
						),
						'parceiro'			=> array(
							'id_parceiro'	=> $agendamento['id_parceiro'],
							'nome'			=> $agendamento['nome_parceiro'],
							'telefone'		=> $agendamento['parceiro_telefone']
						)
					);
				}

				$this->response($resultado, REST_Controller::HTTP_OK);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum agendamento encontrado'
				), REST_Controller::HTTP_OK);	
			}
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum parceiro encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function realiza_get($codigo = null)
	{
		if ($codigo && ($this->rest->level >= 4)) {
			$this->db->trans_start();
			$parceiroUsuario = $this->getParceiroUsuario();

			$where = array(
				'codigo'				=> $codigo,
				'tb_agendamento.status'	=> 'pendente',
				'id_parceiro'			=> $parceiroUsuario['id_parceiro']
			);

			if ($this->agendamento_model->put($where, array('status' => 'realizado', 'modified_by' => $this->rest->user_id))) {
				$where['tb_agendamento.status'] = 'realizado';
				$agendamento = $this->agendamento_model->get(null, array('where' => $where));
					
				$this->db->trans_complete();
	
				if ($this->db->trans_status() === FALSE) {
					$this->response(array(
						'status' 	=> FALSE,
						'message' 	=> 'Agendamento não encontrado'
					), REST_Controller::HTTP_OK);
				} else {
					$this->response(array('codigo' => $codigo), REST_Controller::HTTP_OK);
				}
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Agendamento não encontrado'
				), REST_Controller::HTTP_OK);
			}
		} else {
			$this->response(null, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function cancelar_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		if ($data['id_agendamento']) {
			// Só permite cancelamento se agendamento ainda não foi pago,
			// uma vez que o mesmo somente é pago se foi realizado
			$where = array(
				'id_agendamento'			=> $data['id_agendamento'],
				'tb_agendamento.status !='	=> 'pago',
				'tb_agendamento.status !='	=> 'cancelado'
			);

			$options = array(
				'fields' => 'tb_agendamento.id_agendamento_pagamento, valor_pago_cartao, valor_adicional_cartao, valor_pago_especie, valor_troco',
				'join' => array('tb_agendamento_pagamento', 'tb_agendamento_pagamento.id_agendamento_pagamento = tb_agendamento.id_agendamento_pagamento', 'inner'),
				'where' => $where
			);

			// Se o cancelamento for feito pelo parceiro, verifica se o agendamento pertence ao mesmo
			if ($this->rest->level < 5) {
				$parceiroUsuario = $this->getParceiroUsuario();
				if ($parceiroUsuario) {
					$where['id_parceiro'] = $parceiroUsuario['id_parceiro'];
				}
			} else {
				$this->load->model('caixa_model');
				$id_caixa = $this->caixa_model->_get_caixa_aberto($this->rest->user_id);

				if (!$id_caixa) {
					$this->db->trans_complete();
					return $this->response(array(
						'status' 	=> FALSE,
						'message' 	=> 'Usuário não possui caixa aberto'
					), REST_Controller::HTTP_OK);
				}
			}
			$agendamento = $this->agendamento_model->get(null, $options);

			// Realiza o lançamento de um estorno no caixa
			if ($agendamento) {
				$this->agendamento_model->put($where, array('status' => 'cancelado', 'modified_by' => $this->rest->user_id));
				
				$this->db->insert('tb_agendamento_pagamento', array(
					'tipo'							=> 'saida',
					'descricao'						=> 'Estorno',
					'valor_pago_cartao'				=> $agendamento[0]['valor_pago_cartao'] * -1,
					'valor_adicional_cartao'		=> $agendamento[0]['valor_adicional_cartao'] * -1,
					'valor_pago_especie'			=> ($agendamento[0]['valor_pago_especie'] - $agendamento[0]['valor_troco']) * -1,
					'valor_representante'			=> 0,
					'valor_troco'					=> 0,
					'id_caixa'						=> $id_caixa,
					'parent_agendamento_pagamento'	=> $agendamento[0]['id_agendamento_pagamento']
				));
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum agendamento encontrado'
				), REST_Controller::HTTP_OK);
			}
		} else {
			$this->response(null, REST_Controller::HTTP_BAD_REQUEST);
			return false;
		}
	}

	public function relatorio_get()
	{
		$this->load->dbutil();
		$this->load->helper('file');
		/* get the object */
		// $report = $this->agendamento_model->index();
		$options = array(
			'fields'	=> 'tb_agendamento.codigo,
			tb_agendamento.status,
			tb_parceiro.nome AS nome_parceiro,
			tb_parceiro.telefone AS parceiro_telefone,
			tb_pessoa_fisica.nome AS nome_cliente,
			tb_pessoa_fisica.nome_social,
			tb_pessoa_fisica.cpf,
			tb_pessoa_fisica.telefone as cliente_telefone,
			SUM((tb_parceiro_procedimento.valor_total * tb_agendamento_procedimento.quantidade) - tb_agendamento_procedimento.desconto) AS total,
			tb_agendamento.data_criacao',
			'join'		=> array(
				array('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento', 'inner'),
				array('tb_parceiro', 'tb_parceiro.id_parceiro = tb_agendamento.id_parceiro', 'inner'),
				array('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner'),
				array('tb_cliente', 'tb_cliente.id_cliente = tb_agendamento.id_cliente', 'inner'),
				array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'inner'),
			),
			'group_by'	=> 'tb_agendamento.id_agendamento'
		);
		$report = $this->agendamento_model->get(null, $options, false);
		/*  pass it to db utility function  */
		$new_report = $this->dbutil->xml_from_result($report);
		/*  Now use it to write file. write_file helper function will do it */
		if ( write_file('xml_file.xml', $new_report) ) {
			$this->response(read_file('xml_file.xml'), REST_Controller::HTTP_OK);
		} else {
			echo 'Deu erro';
		}
	}

	public function procedimento_delete( $id_agendamento=null, $id_parceiro_procedimento=null )
	{
		if ( $id_agendamento && $id_parceiro_procedimento ) {
			$this->db->trans_start();

			$this->load->model('caixa_model');
			$id_caixa = $this->caixa_model->_get_caixa_aberto($this->rest->user_id);

			if (!$id_caixa) {
				$this->db->trans_complete();
				return $this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Usuário não possui caixa aberto'
				), REST_Controller::HTTP_OK);
			}

			$this->db->select('tb_procedimento.nome, tb_procedimento.codigo_tuss, tb_parceiro_procedimento.comissao, tb_parceiro_procedimento.valor_parceiro, tb_agendamento_procedimento.quantidade, tb_agendamento_procedimento.desconto, tb_agendamento.id_agendamento_pagamento');
			$this->db->join('tb_procedimento', 'tb_procedimento.id_procedimento = tb_parceiro_procedimento.id_procedimento', 'inner');
			$this->db->join('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_parceiro_procedimento = tb_parceiro_procedimento.id_parceiro_procedimento', 'inner');
			$this->db->join('tb_agendamento', 'tb_agendamento.id_agendamento = tb_agendamento_procedimento.id_agendamento', 'inner');
			$this->db->where(array(
				'tb_parceiro_procedimento.id_parceiro_procedimento'	=> $id_parceiro_procedimento,
			));
			$this->db->where_in('tb_agendamento.status', array('pendente', 'realizado'));
			$procedimento = $this->db->get('tb_parceiro_procedimento')->result_array()[0];

			// Lança o movimento informando o cancelamento
			$this->db->insert('tb_movimento', array(
				'id_agendamento'	=> $id_agendamento,
				'descricao'			=> 'Removido procedimento '. $procedimento['nome'],
				'created_by'		=> $this->rest->user_id
			));

			// Lança um estorno no caixa
			$this->db->insert('tb_agendamento_pagamento', array(
				'tipo'							=> 'saida',
				'descricao'						=> 'Estorno - Cancelamento parcial',
				'valor_pago_cartao'				=> 0,
				'valor_adicional_cartao'		=> 0,
				'valor_pago_especie'			=> ((($procedimento['valor_parceiro'] + $procedimento['comissao']) * $procedimento['quantidade']) - $procedimento['desconto']) * -1,
				'valor_representante'			=> 0,
				'valor_troco'					=> 0,
				'id_caixa'						=> $id_caixa,
				'parent_agendamento_pagamento'	=> $procedimento['id_agendamento_pagamento']
			));

			// Remove o procedimento
			$this->db->delete('tb_agendamento_procedimento', array(
				'id_parceiro_procedimento'	=> $id_parceiro_procedimento,
				'id_agendamento'			=> $id_agendamento,
				'status' 					=> 1
			));
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

	private function valida($data)
	{
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->agendamento_model->rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}

	private function getParceiroUsuario()
	{
		$query = $this->db->query("SELECT id_parceiro FROM tb_parceiro
			INNER JOIN tb_usuario ON tb_usuario.id_pessoa = tb_parceiro.id_pessoa
			INNER JOIN `keys` ON `keys`.user_id = tb_usuario.id_usuario
			INNER JOIN tb_pessoa ON tb_pessoa.id_pessoa = tb_usuario.id_pessoa
			WHERE tb_usuario.id_usuario = ? AND `keys`.`key` = ?", array($this->rest->user_id, $this->rest->key));
		
		if ($query->num_rows()) {
			return $query->result_array()[0];
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum agendamento encontrado'
			), REST_Controller::HTTP_OK);
			return false;
		}
	}
}