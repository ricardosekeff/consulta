<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Procedimento extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('procedimento_model');

		$this->methods['index_get'] = array('limit' => 500, 'level' => 5);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['index_put'] = array('limit' => 60, 'level' => 5);
		$this->methods['options_get'] = array('limit' => 120, 'level' => 5);
		$this->methods['distinct_get'] = array('limit' => 500, 'level' => 5);
		$this->methods['tuss_get'] = array('limit' => 500, 'level' => 5);
		$this->methods['especialidade_get'] = array('limit' => 500, 'level' => 5);
	}

	public function index_get( $id=null )
	{
		$options = array(
			'fields'	=> 'id_procedimento, tb_procedimento.nome, tipo, codigo_tuss, tb_procedimento.codigo_especialidade, tb_especialidade.nome as especialidade',
			'join'		=> array('tb_especialidade', 'tb_procedimento.codigo_especialidade = tb_especialidade.codigo_especialidade', 'left'),
			'sort'		=> 'codigo_tuss, tb_procedimento.nome',
			'order'		=> 'ASC',
			'per_page'	=> -1
		);
		if ($id){
			$options['where'] = 'codigo_tuss = ' . $id;
		}

		$options = array_merge($options, $_GET);

		$procedimentos = $this->procedimento_model->get(NULL, $options);

		if ($procedimentos) {
			$this->response($procedimentos, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum procedimento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post() 
	{
		$procedimento = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($procedimento);

		if ( $validacao === TRUE ) {
			if ( $this->db->insert('tb_procedimento', $procedimento) ) {
				$this->response($procedimento, REST_Controller::HTTP_CREATED);
			} else {
				$this->response(array(
					'status'	=> FALSE,
					'erros'		=> array('Erro desconhecido. Entre em contato com o administrador do sistema.')
				), REST_Controller::HTTP_BAD_REQUEST);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function index_put( $id=null )
	{
		$procedimento = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($procedimento);

		if ( $validacao === TRUE && $id ) {
			$where = array('id_procedimento' => $id);

			if ( $this->procedimento_model->put($where, $procedimento) ) {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status'	=> FALSE,
					'erros'		=> array('Erro desconhecido. Entre em contato com o administrador do sistema.')
				), REST_Controller::HTTP_BAD_REQUEST);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function options_get()
	{
		$options = array(
			'fields'	=> 'tb_procedimento.id_procedimento, tb_procedimento.nome, tb_procedimento.tipo, tb_procedimento.codigo_tuss, tb_procedimento.codigo_especialidade',
			'join'		=> array('tb_parceiro_procedimento', 'tb_parceiro_procedimento.id_procedimento = tb_procedimento.id_procedimento', 'inner'),
			'distinct'	=> true,
			'sort'		=> 'codigo_tuss, nome',
			'order'		=> 'ASC',
			'per_page'	=> -1
		);

		$procedimentos = $this->procedimento_model->get(NULL, $options);

		if ($procedimentos) {
			$this->response($procedimentos, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum procedimento encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function distinct_get()
	{
		$options = array(
			'fields'	=> 'codigo_tuss',
			'distinct'	=> true,
			'sort'		=> 'codigo_tuss',
			'order'		=> 'ASC',
			'per_page'	=> -1
		);

		$procedimentos = $this->procedimento_model->get(NULL, $options);

		if ($procedimentos) {
			$this->response($procedimentos, REST_Controller::HTTP_OK);
		} else {
			$this->response(NULL, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	public function tuss_get( $search=null )
	{
		$sql = 'SELECT codigo_tuss AS value, CONCAT(codigo_tuss, " - ", nome) AS label FROM tb_tuss';
		if ($search) {
			$sql .= (is_numeric($search)) ? " WHERE codigo_tuss LIKE '{$search}%'" : " WHERE nome LIKE '%{$search}%'";
		}
		$query = $this->db->query($sql);
		if ($query->num_rows() > 0) {
			$this->response($query->result_array(), REST_Controller::HTTP_OK);
		} else {
			$this->response(array(), REST_Controller::HTTP_OK);
		}
	}
	
	public function especialidade_get()
	{
		$query = $this->db->query('SELECT codigo_especialidade as value, nome AS label FROM tb_especialidade');
		if ($query->num_rows() > 0) {
			$this->response($query->result_array(), REST_Controller::HTTP_OK);
		} else {
			$this->response(NULL, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}

	private function valida($data)
	{
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->procedimento_model->rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}
}