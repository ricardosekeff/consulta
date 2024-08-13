<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Financeiro extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('financeiro_model');

		$this->methods['index_get'] = array('limit' => 500, 'level' => 2);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['pagar_put'] = array('limit' => 120, 'level' => 5);
		$this->methods['cancelar_put'] = array('limit' => 120, 'level' => 5);
		$this->methods['fecha_ciclos_post'] = array('limit' => 10, 'level' => 10);
	}

	public function index_get( $id = null, $field = null )
	{
		$options = array(
			'fields'	=> 'tb_financeiro.id_financeiro, tb_financeiro.codigo, tb_financeiro.status, tb_financeiro.data_criacao, tb_financeiro.id_parceiro, tb_parceiro.nome AS nome_parceiro, tb_financeiro.created_by, tb_usuario.email AS email_usuario, SUM(tb_parceiro_procedimento.valor_parceiro * tb_agendamento_procedimento.quantidade) AS total_parceiro',
			'join'		=> array(
				array('tb_parceiro', 'tb_parceiro.id_parceiro = tb_financeiro.id_parceiro', 'inner'),
				array('tb_usuario', 'tb_usuario.id_usuario = tb_financeiro.created_by', 'inner'),
				array('tb_agendamento', 'tb_agendamento.id_financeiro = tb_financeiro.id_financeiro', 'inner'),
				array('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento', 'inner'),
				array('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner')
			),
			'group_by'	=> 'tb_financeiro.id_financeiro'
		);

		if ($this->rest->level < 5) {
			$query = $this->db->query("SELECT id_parceiro FROM tb_usuario
				INNER JOIN tb_parceiro ON tb_parceiro.id_pessoa = tb_usuario.id_pessoa
				WHERE id_usuario = ?", $this->rest->user_id);
			if (!$query->num_rows()) {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Parceiro não encontrado'
				), REST_Controller::HTTP_OK);
			}
			$options['where'] = array(
				'tb_financeiro.id_parceiro' => $query->result_array()[0]['id_parceiro']
			);
		}

		if ($id) {
			$options['where']['tb_financeiro.id_financeiro'] = $id;
		}

		$resultado = $this->financeiro_model->get(null, $options);

		if ($resultado) {
			if ($id) {
				$this->db->reset_query();
				$this->db->select('tb_agendamento.id_agendamento, tb_agendamento.codigo, tb_agendamento.id_cliente, tb_cliente.id_cliente, tb_pessoa_fisica.cpf, tb_pessoa_fisica.nome as nome_cliente, tb_agendamento.status, tb_agendamento_procedimento.quantidade, SUM(tb_parceiro_procedimento.valor_parceiro * tb_agendamento_procedimento.quantidade) AS valor_parceiro, tb_parceiro_procedimento.comissao');
				$this->db->where('tb_agendamento.id_financeiro', $resultado[0]['id_financeiro']);
				$this->db->join('tb_cliente', 'tb_cliente.id_cliente = tb_agendamento.id_cliente', 'inner');
				$this->db->join('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa_fisica = tb_cliente.id_pessoa_fisica', 'inner');
				$this->db->join('tb_agendamento_procedimento', 'tb_agendamento_procedimento.id_agendamento = tb_agendamento.id_agendamento', 'inner');
				$this->db->join('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_parceiro_procedimento = tb_agendamento_procedimento.id_parceiro_procedimento', 'inner');
				$this->db->group_by('tb_agendamento.id_agendamento');
				$resultado['0']['agendamentos'] = $this->db->get('tb_agendamento')->result_array();
			}

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum financeiro encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);

		// Código gerado através de trigger no banco de dados
		// $codigo = $this->rest->user_id . sprintf("%014d", time());
		// 'codigo'		=> $codigo,

		$openedFinanceiro = $this->financeiro_model->get(null, array(
			'where' => array(
				'status'		=> 'pendente',
				'id_parceiro'	=> $data['id_parceiro']
			)
		));
		$newFinanceiro = array(
			'status'		=> 'pendente',
			'id_parceiro'	=> $data['id_parceiro'],
			'created_by'	=> $this->rest->user_id
		);

		$validacao = $this->valida($newFinanceiro);

		if ($validacao === TRUE) {
			$this->db->trans_start();

			// Se houver financeiro pendente aberto, adiciona os novos procedimentos lançados a ele
			// Caso contrário, gera um financeiro

			// Variável utilizada para caso desejem mudança na geração do financeiro
			$utilizaFinanceiroAberto = false;

			if ($utilizaFinanceiroAberto && $openedFinanceiro) {
				$financeiro = $openedFinanceiro[0];
			} else {
				$this->db->insert('tb_financeiro', $newFinanceiro);
				$financeiro = $newFinanceiro;
				$financeiro['id_financeiro'] = $this->db->insert_id();
			}

			$this->db->where_in('id_agendamento', $data['agendamentos']);
			$this->db->where('status', 'realizado');
			// Inserido a regra de mudar o status do agendamento para 'aguardando pagamento'
			// na trigger BEFORE_UPDATE da tabela TB_AGENDAMENTO
			// $this->db->update('tb_agendamento', array('status' => 'aguardando pagamento', 'id_financeiro' => $financeiro['id_financeiro']));
			$this->db->update('tb_agendamento', array('id_financeiro' => $financeiro['id_financeiro']));

			$this->db->trans_complete();
			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao cadastrar o usuário. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->response($financeiro, REST_Controller::HTTP_CREATED);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function pagar_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);

		if ($data['id_financeiro']) {
			$where = array('id_financeiro' => $data['id_financeiro']);

			if ( $this->financeiro_model->put($where, array('status' => 'pago')) ) {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum financeiro encontrado'
				), REST_Controller::HTTP_OK);
			}	
		}
	}

	public function cancelar_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);

		if ($data['id_financeiro']) {
			$where = array('id_financeiro' => $data['id_financeiro'], 'status' => 'pendente');

			if ( $this->financeiro_model->put($where, array('status' => 'cancelado')) ) {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum financeiro encontrado'
				), REST_Controller::HTTP_OK);
			}	
		}
	}

	public function fecha_ciclos_post()
	{
		$this->db->trans_start();

		// Seleciona o id dos parceiros aptos a fechar o ciclo e que tenham procedimentos realizados
		$query_parceiros = $this->db->query("SELECT DISTINCT tb_agendamento.id_parceiro 
			FROM tb_agendamento 
			INNER JOIN tb_parceiro ON tb_agendamento.id_parceiro = tb_parceiro.id_parceiro
			WHERE tb_parceiro.data_fechamento_ciclo <= DATE(NOW()) AND tb_agendamento.status = 'realizado' AND tb_agendamento.id_financeiro IS NULL");

		if ($query_parceiros->num_rows() > 0) {
			$parceiros = $query_parceiros->result_array();

			// Cadastra financeiro dos parceiros selecionados
			// O status do financeiro permanecerá como "processando"
			// enquanto a transação não terminar
			foreach ($parceiros as $p) {
				$this->db->query("INSERT INTO tb_financeiro (id_parceiro, created_by, status) 
					VALUES (?, ?, 'processando')", array($p['id_parceiro'], $this->rest->user_id));
			}

			// Vincula os agendamentos aos financeiros criados de acordo com as seguintes regras:
			// status do procedimento = realizado 
			// procedimento não vinculado a nenhum financeiro
			// data de realização do procedimento menor que a data de criacao do financeiro
			$this->db->query("UPDATE tb_agendamento a
				INNER JOIN tb_financeiro f ON f.id_parceiro = a.id_parceiro
				SET a.id_financeiro = f.id_financeiro, a.modified_by = ?, a.status = 'aguardando pagamento'
				WHERE f.status = 'processando' AND a.status = 'realizado' AND a.id_financeiro IS NULL AND a.data_realizacao <= f.data_criacao", array($this->rest->user_id));

			$this->db->query("UPDATE tb_financeiro SET status = 'pendente' WHERE status = 'processando'");
		}

		$this->db->trans_complete();

		if ($this->db->trans_status() === FALSE) {
			$this->response(array(
				'status'	=> FALSE,
				'message'	=> 'Ocorreu um erro ao gerar financeiros.'
			), REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status'	=> TRUE,
				'message'	=> 'Financeiros gerados com sucesso!'
			), REST_Controller::HTTP_CREATED);
		}
	}

	private function valida($data)
	{
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->financeiro_model->rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}
}