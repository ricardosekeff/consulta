<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Cargo extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('cargo_model');
		// $this->methods['index_get']['limit'] = 500; // 500 requests per hour per user/key
		/*$this->methods['cidade_post']['limit'] = 100; // 100 requests per hour per user/key
        $this->methods['cidade_delete']['limit'] = 50; // 50 requests per hour per user/key*/


	}

	public function index_get( $id=null )
	{
		$cargos = $this->cargo_model->get($id);

		if ($cargos) {
			$this->response($cargos, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum cargo encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$cargo = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($cargo);

		if ( $validacao === TRUE ) {
			if ( $this->db->insert('tb_cargo', $cargo) ) {
				$this->response($cargo, REST_Controller::HTTP_CREATED);
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

	private function valida($data)
	{
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->cargo_model->rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}
}