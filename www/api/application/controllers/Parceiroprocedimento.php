<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class ParceiroProcedimento extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('parceiroProcedimento_model');

		$this->methods['index_get'] = array('limit' => 500, 'level' => 2);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['index_put'] = array('limit' => 60, 'level' => 5);
		$this->methods['index_delete'] = array('limit' => 60, 'level' => 5);
		/*
		$this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
        $this->methods['index_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['index_delete']['limit'] = 50; // 50 requests per hour per user/key
        */
	}

	public function index_get( $id_parceiro=null, $id_parceiro_procedimento=null )
	{
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
			$id_parceiro = $query->result_array()[0]['id_parceiro'];
		}

		$options = array(
			'fields'	=> 'id_parceiro_procedimento, observacao, horario_atendimento, comissao, valor_parceiro, valor_total, status, codigo_tuss, tb_procedimento.nome, tb_parceiro_procedimento.nome AS nome_parceiro_procedimento, tb_parceiro_procedimento.id_parceiro',
			'join'		=> array('tb_procedimento', 'tb_procedimento.id_procedimento = tb_parceiro_procedimento.id_procedimento', 'inner'),
			'where'		=> array('id_parceiro' => $id_parceiro, 'status' => 1),
			'sort'		=> 'tb_procedimento.nome',
			'order'		=> 'ASC'
		);

		if ($id_parceiro_procedimento) {
			$options['where']['tb_parceiro_procedimento.id_parceiro_procedimento'] = $id_parceiro_procedimento;
		}

		if ($this->rest->level > 45) {
			$options = array_merge($options, $_GET);
		}
		$resultado = $this->parceiroProcedimento_model->get(null, $options);

		if ($resultado) {
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum procedimento de parceiro encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data);

		if ( $validacao === TRUE ) {
			$this->db->trans_start();
			$where = array(
				'id_parceiro'		=> $data['id_parceiro'],
				'id_procedimento'	=> $data['id_procedimento'],
				'status'			=> 1
			);
			// Desabilita o procedimento atual - se houver - e adiciona um novo
			// Tal estratégia foi utilizada para manter o histórico de valores do procedimento
			$this->db->update('tb_parceiro_procedimento', array('status' => 0), $where);
			$this->db->insert('tb_parceiro_procedimento', $data);
			$this->db->trans_complete();
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function index_put( $id=null )
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data);

		if ( $validacao === TRUE && $id ) {
			$where = array('id_parceiro_procedimento' => $id);

			// Inicio da transaction
			$this->db->trans_start();
			$where = array(
				'id_parceiro_procedimento' 	=> $id,
				'status'					=> 1
			);

			$query = $this->db->get_where('tb_parceiro_procedimento', $where);
			if ($query->num_rows() > 0) { 
				$parceiro_procedimento = $query->result_array()[0];

				// Verifica se os valores foram alterados
				// Caso exista mudança de valor, altera o status do procedimento para 0 e insere novo procedimento
				if ($parceiro_procedimento['valor_parceiro'] != $data['valor_parceiro'] || $parceiro_procedimento['comissao'] != $data['comissao']) {
					$this->db->update('tb_parceiro_procedimento', array('status' => 0), $where);

					$data['id_parceiro'] = $parceiro_procedimento['id_parceiro'];
					$data['id_procedimento'] = $parceiro_procedimento['id_procedimento'];

					$this->db->insert('tb_parceiro_procedimento', $data);
				} else {
					$this->db->update('tb_parceiro_procedimento', $data, $where);
				}
			}
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Ocorreu um erro ao atualizar o procedimento do parceiro.'
				), REST_Controller::HTTP_OK);
			} else {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function index_delete( $id=null )
	{
		if ( $id ) {
			$this->db->trans_start();
			$where = array(
				'id_parceiro_procedimento' 	=> $id,
				'status'					=> 1
			);
			$this->db->update('tb_parceiro_procedimento', array('status' => 0), $where);
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Ocorreu um erro ao excluir o procedimento do parceiro.'
				), REST_Controller::HTTP_OK);
			} else {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			}
		} else {
			$this->response(null, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function procedimento_get($id_procedimento=null)
	{
		$options = array(
			'fields'	=> 'id_parceiro_procedimento, observacao, comissao, valor_parceiro, valor_total, codigo_tuss, tb_parceiro.nome as parceiro_nome, tb_parceiro_procedimento.id_parceiro',
			'join'		=> array(
				array('tb_procedimento', 'tb_procedimento.id_procedimento = tb_parceiro_procedimento.id_procedimento', 'inner'),
				array('tb_parceiro', 'tb_parceiro.id_parceiro = tb_parceiro_procedimento.id_parceiro', 'inner')
			),
			'where'		=> array('tb_parceiro.status' => 1, 'tb_parceiro_procedimento.status' => 1)
		);

		if ($id_procedimento) {
			$options['where']['tb_parceiro_procedimento.id_procedimento'] = $id_procedimento;
		}

		$resultado = $this->parceiroProcedimento_model->get(null, $options);

		if ($resultado) {
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum procedimento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function distinct_get()
	{
		// SELECT DISTINCT `tb_parceiro_procedimento`.`id_procedimento`, `tb_procedimento`.`nome`, `tb_procedimento`.`tipo`, `tb_procedimento`.`codigo_tuss` FROM `tb_parceiro_procedimento` LEFT JOIN `tb_procedimento` ON `tb_parceiro_procedimento`.`id_procedimento` = `tb_procedimento`.`id_procedimento`

		$options = array(
			'fields'	=> 'tb_parceiro_procedimento.id_procedimento, tb_procedimento.nome, tb_procedimento.tipo, tb_procedimento.codigo_tuss, tb_procedimento.codigo_especialidade',
			'distinct' 	=> true,
			'join'		=> array('tb_procedimento', 'tb_parceiro_procedimento.id_procedimento = tb_procedimento.id_procedimento', 'left'),
			'where'		=> array('tb_parceiro_procedimento.status' => 1)
		);

		$resultado = $this->parceiroProcedimento_model->get(null, $options);

		if ($resultado) {
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum procedimento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	private function valida($data)
	{
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->parceiroProcedimento_model->rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}
}