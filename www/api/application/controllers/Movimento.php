<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Movimento extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('movimento_model');
        
		$this->methods['index_get'] = array('limit' => 500, 'level' => 2);
    }
    
    function index_get($id=false) 
    {
        if ($id) {
            $this->load->model('agendamento_model');
            $options = array(
                'fields'	=> 'tb_movimento.*, tb_usuario.email',
                'join'		=> array(
                    array('tb_movimento', 'tb_movimento.id_agendamento = tb_agendamento.id_agendamento', 'inner'),
                    array('tb_usuario', 'tb_usuario.id_usuario = tb_movimento.created_by', 'left'),
                ),
                'where'		=> array(
                    'tb_agendamento.id_agendamento'	=> $id
                )
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
                $options['where']['tb_agendamento.id_parceiro'] = $parceiroUsuario['id_parceiro'];
            }
    
            $resultado = $this->agendamento_model->get(null, $options);
    
            if ($resultado) {
                $this->response($resultado, REST_Controller::HTTP_OK);
            } else {
                $this->response(array(
                    'status' 	=> FALSE,
                    'message' 	=> 'Nenhum movimento encontrado'
                ), REST_Controller::HTTP_OK);
            }
        } else {
            $this->response(array(
                'status' 	=> FALSE,
                'message' 	=> 'Nenhum movimento encontrado'
            ), REST_Controller::HTTP_OK);
        }
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