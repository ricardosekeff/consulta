<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Representante extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('representante_model');

		$this->methods['index_get'] = array('limit' => 360, 'level' => 5);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 5);
		$this->methods['pagar_put'] = array('limit' => 300, 'level' => 5);
		$this->methods['pagamento_get'] = array('limit' => 360, 'level' => 5);
	}

	public function index_get( $id=null )
	{
		$options = array(
			'sort'		=> 'nome',
			'order'		=> 'ASC',
			'per_page'	=> -1
		);
		
		$this->db->simple_query('SET SESSION group_concat_max_len=20000');
		$representantes = $this->representante_model->get($id, $options);
	
		if ($representantes) {
			$this->response($representantes, REST_Controller::HTTP_OK);
		} else {
			$this->response(NULL, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
    }
    
    public function index_post()
    {
    	$representante = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($representante);

		if ( $validacao === TRUE ) {
			if ( $this->db->insert('tb_representante', $representante) ) {
				$this->response($representante, REST_Controller::HTTP_CREATED);
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
	
	public function pagar_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		if ($data['id_representante']) {
			
			$this->db->trans_start();
			
			// Seleciona o valor total devido ao representante
			$this->db->where_in('id_agendamento', $data['agendamentos']);
			
			$this->db->insert('tb_representante_pagamento', array(
				'id_representante' 	=> $data['id_representante'],
				'created_by' 		=> $this->rest->user_id
			));

			$id_representante_pagamento = $this->db->insert_id();

			$this->db->reset_query();

			$this->db->set('representante_pago', 1);
			$this->db->set('id_representante_pagamento', $id_representante_pagamento);
			$this->db->where('id_representante', $data['id_representante']);
			$this->db->where('representante_pago !=', 1);
			$this->db->where_in('id_agendamento', $data['agendamentos']);
			$this->db->update('tb_agendamento');

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao tentar efetuar o pagamento do representante.'
				), REST_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			}
			
		} else {
			$this->response(null, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function pagamento_get($id_representante=null, $id_representante_pagamento=null)
	{
		if ($id_representante) {
			$options = array(
				'fields'	=> 'tb_representante_pagamento.id_representante_pagamento, SUM(tb_agendamento_pagamento.valor_representante) AS total_pagamento, tb_representante_pagamento.data_criacao',
				'join'		=> array(
					array('tb_representante_pagamento', 'tb_representante_pagamento.id_representante = tb_representante.id_representante', 'inner'),
					array('tb_agendamento', 'tb_agendamento.id_representante_pagamento = tb_representante_pagamento.id_representante_pagamento', 'inner'),
					array('tb_agendamento_pagamento', 'tb_agendamento_pagamento.id_agendamento_pagamento = tb_agendamento.id_agendamento_pagamento', 'inner')
				),
				'group_by'	=> 'tb_representante_pagamento.id_representante_pagamento',
				'where'		=> array('tb_representante_pagamento.id_representante' => $id_representante)
			);
			$resultado = $this->representante_model->get(null, $options);

			if ($id_representante_pagamento) {
				$options = array(
					'fields'	=> 'tb_agendamento.id_agendamento, tb_agendamento.codigo, tb_agendamento.data_criacao, tb_agendamento_pagamento.valor_representante',
					'join'		=> array(
						array('tb_agendamento_pagamento', 'tb_agendamento_pagamento.id_agendamento_pagamento = tb_agendamento.id_agendamento_pagamento', 'inner')
					),
					'where'		=> array(
						'tb_agendamento.id_representante_pagamento' => $id_representante_pagamento,
						'tb_agendamento.id_representante' => $id_representante
					)
				);
				$this->load->model('agendamento_model');
				$resultado[0]['agendamentos'] = $this->agendamento_model->get(null, $options);
			}
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(null, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

    private function valida($data)
    {
       $this->form_validation->set_data($data);
			$this->form_validation->set_rules($this->representante_model->rules);

			if ( $this->form_validation->run() ) {
				return TRUE;
			} 
			return array(
				'status'	=> FALSE,
				'erros'		=> $this->form_validation->error_array()
			);
    }
}