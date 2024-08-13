<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Cidade extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('cidade_model');

		$this->methods['index_get']['limit'] = 30;
	}

	public function index_get()
	{
		$options = array(
			'fields'	=> 'uf, GROUP_CONCAT(nome) AS nomes, GROUP_CONCAT(id_cidade) AS codigos',
			'sort'		=> 'nome',
			'order'		=> 'ASC',
			'group_by'	=> 'uf',
			'per_page'	=> -1

		);
		
		$this->db->simple_query('SET SESSION group_concat_max_len=20000');

		$cidades = $this->cidade_model->get(null, $options);

		if (is_array($cidades)) {
			foreach ($cidades as $key => $value) {
				$cidades[$key]['nomes'] = explode(',', $value['nomes']);
				$cidades[$key]['codigos'] = explode(',', $value['codigos']);
			}
		}

		if ($cidades) {
			$this->response($cidades, REST_Controller::HTTP_OK);
		} else {
			$this->response(NULL, REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
		}
	}
}