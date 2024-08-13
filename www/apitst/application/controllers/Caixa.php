<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Caixa extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('caixa_model');
		$this->methods['index_get']['limit'] = array('limit' => 300, 'level' => 5);
		$this->methods['index_post']['limit'] = array('limit' => 300, 'level' => 5);
		$this->methods['pagar_post']['limit'] = array('limit' => 100, 'level' => 5);
		$this->methods['transacao_post']['limit'] = array('limit' => 100, 'level' => 5);
		$this->methods['fechar_put']['limit'] = array('limit' => 120, 'level' => 5);
    }

    public function index_get( $id = null )
    {
        $options = array(
            'fields'    => 'id_caixa, data_abertura, valor_abertura, data_fechamento, valor_fechamento, total_cartao, tb_caixa.id_usuario, tb_pessoa_fisica.nome AS usuario_nome',
            'join'      => array(
                array('tb_usuario', 'tb_usuario.id_usuario = tb_caixa.id_usuario', 'inner'),
                array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa = tb_usuario.id_pessoa', 'inner')
            )
        );

        if ($this->rest->level < 10) {
            $options['where']['tb_caixa.id_usuario'] = $this->rest->user_id;
        }
        
        $caixas = $this->caixa_model->get($id, $options);
        
        if ($caixas) {
            if ($id) {
                $query = $this->db->query("SELECT tb_agendamento_pagamento.id_agendamento_pagamento, GROUP_CONCAT(tb_agendamento.codigo) AS agendamentos_codigos, GROUP_CONCAT(tb_agendamento.id_agendamento) AS ids_agendamentos, valor_pago_cartao, valor_adicional_cartao, valor_pago_especie, valor_representante, valor_troco 
                    FROM tb_agendamento_pagamento 
                    LEFT JOIN tb_agendamento ON tb_agendamento.id_agendamento_pagamento = tb_agendamento_pagamento.id_agendamento_pagamento OR tb_agendamento.id_agendamento_pagamento = tb_agendamento_pagamento.parent_agendamento_pagamento
                    WHERE id_caixa = ?
                    GROUP BY tb_agendamento_pagamento.id_agendamento_pagamento", $id);
                
                $caixas[0]['pagamentos'] = array();
                if ($query->num_rows() > 0) {
                    $caixas[0]['pagamentos'] = $query->result_array();
                }
            }
            $this->response($caixas, REST_Controller::HTTP_OK);
        } else {
            $this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum caixa encontrado'
			), REST_Controller::HTTP_OK);
        }
    }

    public function index_post()
    {
        // Verifica se usuário possui caixa em aberto
        if ($this->caixa_model->_get_caixa_aberto($this->rest->user_id)) {
            return $this->response(array(
                'status'    => FALSE,
                'message'   => 'Usuário possui caixa em aberto'
            ), REST_Controller::HTTP_OK);
        }

        $data = json_decode($this->input->raw_input_stream, true);
        $caixa = array(
            'valor_abertura'    => $data['valor_abertura'],
            'id_usuario'        => $this->rest->user_id
        );
        $validacao = $this->valida($caixa);

        if ( $validacao === TRUE ) {
            if ( $this->db->insert('tb_caixa', $caixa) ) {
                $this->response($caixa, REST_Controller::HTTP_CREATED);
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

    public function pagar_post()
    {
        // Verifica se usuário possui caixa em aberto
        $id_caixa = $this->caixa_model->_get_caixa_aberto($this->rest->user_id);
        if ($id_caixa) {
            $data = json_decode($this->input->raw_input_stream, true);
            $pagamento = array(
                'tipo'					    => 'saida',
                'descricao'				    => $data['descricao'],
                'valor_pago_cartao'		    => $data['valor_pago_cartao'] * -1,
                'valor_adicional_cartao'	=> $data['valor_adicional_cartao'] * -1,
                'valor_pago_especie'    	=> $data['valor_pago_especie'] * -1,
                'valor_troco'			    => $data['valor_troco'] * -1,
                'id_caixa'  				=> $id_caixa
            );

            if ( $this->db->insert('tb_agendamento_pagamento', $pagamento) ) {
                $this->response($pagamento, REST_Controller::HTTP_CREATED);
            } else {
                $this->response(array(
                    'status'	=> FALSE,
                    'erros'		=> array('Erro desconhecido. Entre em contato com o administrador do sistema.')
                ), REST_Controller::HTTP_BAD_REQUEST);
            }

        } else {
            return $this->response(array(
                'status'    => FALSE,
                'message'   => 'Usuário não possui caixa em aberto'
            ), REST_Controller::HTTP_OK);
        }
    }

    public function transacao_post()
    {
        // Verifica se usuário possui caixa em aberto
        $id_caixa = $this->caixa_model->_get_caixa_aberto($this->rest->user_id);
        if ($id_caixa) {
            $data = json_decode($this->input->raw_input_stream, true);
            $mult = ($data['tipo'] === 'saida')? -1:1; 
            $transacao = array(
                'tipo'					    => $data['tipo'],
                'descricao'				    => $data['descricao'],
                'valor_pago_cartao'		    => $data['valor_pago_cartao'] * $mult,
                'valor_adicional_cartao'	=> $data['valor_adicional_cartao'] * $mult,
                'valor_pago_especie'    	=> $data['valor_pago_especie'] * $mult,
                'valor_troco'			    => $data['valor_troco'] * $mult,
                'id_caixa'  				=> $id_caixa
            );

            if ( $this->db->insert('tb_agendamento_pagamento', $transacao) ) {
                $this->response($transacao, REST_Controller::HTTP_CREATED);
            } else {
                $this->response(array(
                    'status'	=> FALSE,
                    'erros'		=> array('Erro desconhecido. Entre em contato com o administrador do sistema.')
                ), REST_Controller::HTTP_BAD_REQUEST);
            }

        } else {
            return $this->response(array(
                'status'    => FALSE,
                'message'   => 'Usuário não possui caixa em aberto'
            ), REST_Controller::HTTP_OK);
        }
    }

    public function fechar_put()
    {
        $data = json_decode($this->input->raw_input_stream, true);
        if ( $data['id_caixa']) {
            // Desabilitado pela modificação que permite estorno
            // $query_pagamentos = $this->db->query("SELECT (SUM( tb_agendamento_pagamento.valor_pago_especie) - SUM( tb_agendamento_pagamento.valor_troco)) AS total_especie, SUM(tb_agendamento_pagamento.valor_pago_cartao) + SUM(tb_agendamento_pagamento.valor_adicional_cartao) AS total_cartao
            //     FROM tb_agendamento_pagamento
            //     INNER JOIN (SELECT id_agendamento_pagamento FROM tb_agendamento WHERE created_by = ? AND status != 'cancelado' GROUP BY id_agendamento_pagamento) agnd ON agnd.id_agendamento_pagamento = tb_agendamento_pagamento.id_agendamento_pagamento
            //     WHERE tb_agendamento_pagamento.id_caixa = ?
            //     GROUP BY tb_agendamento_pagamento.id_caixa", array($this->rest->user_id, $data['id_caixa']));

            $query_pagamentos = $this->db->query("SELECT (SUM(tb_agendamento_pagamento.valor_pago_especie) - SUM(tb_agendamento_pagamento.valor_troco)) AS total_especie, SUM(tb_agendamento_pagamento.valor_pago_cartao) + SUM(tb_agendamento_pagamento.valor_adicional_cartao) AS total_cartao
                FROM tb_agendamento_pagamento
                INNER JOIN tb_caixa ON tb_caixa.id_caixa = tb_agendamento_pagamento.id_caixa AND tb_caixa.id_usuario = ?
                WHERE tb_agendamento_pagamento.id_caixa = ?
                GROUP BY tb_agendamento_pagamento.id_caixa", array($this->rest->user_id, $data['id_caixa']));

            if ($query_pagamentos->num_rows() == 1) {
                $pagamentos = $query_pagamentos->result_array();
                if ($pagamentos[0]['total_especie'] >= 0) {
                    $this->db->query("UPDATE tb_caixa 
                        SET tb_caixa.data_fechamento = CURRENT_TIMESTAMP, tb_caixa.valor_fechamento = tb_caixa.valor_abertura + ?, tb_caixa.total_cartao = ?
                        WHERE tb_caixa.id_caixa = ? AND tb_caixa.id_usuario = ? AND tb_caixa.data_fechamento IS NULL", array($pagamentos[0]['total_especie'], $pagamentos[0]['total_cartao'], $data['id_caixa'], $this->rest->user_id));
                } else {
                    return $this->response(array(
                        'status'    => FALSE,
                        'message'   => 'Caixa não pode ser fechado com valor em espécie negativo'
                    ), REST_Controller::HTTP_OK);
                }
            } else {
                $this->db->query("UPDATE tb_caixa 
                    SET tb_caixa.data_fechamento = CURRENT_TIMESTAMP, tb_caixa.valor_fechamento = 0, tb_caixa.total_cartao = 0
                    WHERE tb_caixa.id_caixa = ? AND tb_caixa.id_usuario = ? AND tb_caixa.data_fechamento IS NULL", array($data['id_caixa'], $this->rest->user_id));
            }

            $this->response(null, REST_Controller::HTTP_NO_CONTENT);
        } else {
            $this->response(null, REST_Controller::HTTP_BAD_REQUEST);
        }
    }

    private function valida($data)
	{
		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->caixa_model->rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
    }
}