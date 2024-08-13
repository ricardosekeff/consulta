<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Relatorio extends REST_Controller {

	function __construct()
	{
		parent::__construct();

        $this->load->dbutil();

        $this->methods['agendamento_get'] = array('limit' => 40, 'level' => 5);
		$this->methods['financeiro_get'] = array('limit' => 40, 'level' => 5);
        $this->methods['parceiro_get'] = array('limit' => 40, 'level' => 5);
        $this->methods['procedimento_get'] = array('limit' => 40, 'level' => 5);
	}

    public function agendamento_get($search_value=null, $search_field=null)
	{
        $sql = "SELECT 
                a.id_agendamento,
                a.codigo,
                a.status,
                UPPER(p.nome) AS nome, 
                p.id_parceiro,
                SUM(pp.valor_parceiro * ap.quantidade) AS valor_parceiro,
                SUM(((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) AS valor_cf, 
                SUM((pp.valor_total * ap.quantidade) - ap.desconto) AS total
            FROM tb_agendamento a
                INNER JOIN tb_parceiro p ON p.id_parceiro = a.id_parceiro
                INNER JOIN tb_agendamento_procedimento ap ON ap.id_agendamento = a.id_agendamento
                LEFT JOIN tb_parceiro_procedimento pp ON pp.id_parceiro_procedimento = ap.id_parceiro_procedimento
            WHERE a.status != 'cancelado'";
        if ($search_field) {
            $sanitized_search_value = preg_replace('/[^\w\s-]+/', '', $search_value);
            switch ($search_field) {
                case 'nome':
                    $sql .= " AND p.nome LIKE '$sanitized_search_value%'";
                    break;
            }
        }
        if ($this->get('parceiro')) {
            $id_parceiro = $this->get('parceiro', null, 'num');
            $sql .= " AND p.id_parceiro = $id_parceiro";
        }

        if ($this->get('tipo') == 'financeiro') {
            if ($this->get('data_inicial')) {
                $data_inicial = $this->get('data_inicial', null, 'date');
                $sql .= " AND (a.id_financeiro IS NOT NULL AND ((f.status = 'pendente' AND f.data_criacao >= '$data_inicial') OR f.data_modificacao >= '$data_inicial') OR (a.id_financeiro IS NULL AND a.data_modificacao >= '$data_inicial'))";
            }
            if ($this->get('data_final')) {
                $data_final = $this->get('data_final', null, 'date');
                $sql .= " AND (a.id_financeiro IS NOT NULL AND ((f.status = 'pendente' AND f.data_criacao <= '$data_final') OR f.data_modificacao <= '$data_final') OR (a.id_financeiro IS NULL AND a.data_modificacao <= '$data_final'))";
            }
        } else {
            if ($this->get('data_inicial')) {
                $data_inicial = $this->get('data_inicial', null, 'date');
                $sql .= " AND DATE(a.data_criacao) >= '$data_inicial'";
            }
            if ($this->get('data_final')) {
                $data_final = $this->get('data_final', null, 'date');
                $sql .= " AND DATE(a.data_criacao) <= '$data_final'";
            }
        }

        if (in_array($this->get('status'), array('pendente', 'realizado', 'pago'))) {
            $status = $this->get('status');
            $sql .= " AND a.status = '$status'";
        }
        if (in_array($this->get('financeiro'), array('s', 'n'))) {
            $financeiro = $this->get('financeiro');
            if ($financeiro == 's') {
                $sql .= " AND a.id_financeiro IS NOT NULL";
            } else {
                $sql .= " AND a.id_financeiro IS NULL";
            }
        }
        $sql .= ' GROUP BY a.id_agendamento ORDER BY a.id_agendamento DESC';
        $query = $this->db->query($sql);

		if ($query->num_rows()) {
            if ($this->get('export') == 'csv') {
				echo $this->dbutil->csv_from_result($query, ';');
                die();
			} else {
                $this->response($query->result_array(), REST_Controller::HTTP_OK);
            }
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Erro ao gerar relatório'
			), REST_Controller::HTTP_OK);
		}
	}

	public function financeiro_get($search_value=null, $search_field=null)
	{
        $sql = "SELECT 
                UPPER(p.nome) AS nome, 
                SUM(CASE WHEN a.id_financeiro IS NULL THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS procedimentos_pendentes,
                SUM(CASE WHEN a.id_financeiro IS NOT NULL THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS financeiros_pendentes,
                p.id_parceiro
            FROM tb_agendamento a
                INNER JOIN tb_parceiro p ON p.id_parceiro = a.id_parceiro
                INNER JOIN tb_agendamento_procedimento ap ON ap.id_agendamento = a.id_agendamento
                LEFT JOIN tb_parceiro_procedimento pp ON pp.id_parceiro_procedimento = ap.id_parceiro_procedimento
                LEFT JOIN tb_financeiro f ON f.id_financeiro = a.id_financeiro
            WHERE a.status = 'realizado'";
        if ($search_field) {
            $sanitized_search_value = preg_replace('/[^\w\s-]+/', '', $search_value);
            switch ($search_field) {
                case 'nome':
                    $sql .= " AND p.nome LIKE '$sanitized_search_value%'";
                    break;
            }
        }
        if ($this->get('data_inicial')) {
            $data_inicial = $this->get('data_inicial', null, 'date');
            $sql .= " AND (a.id_financeiro IS NOT NULL AND ((f.status = 'pendente' AND f.data_criacao >= '$data_inicial') OR f.data_modificacao >= '$data_inicial') OR (a.id_financeiro IS NULL AND a.data_modificacao >= '$data_inicial'))";
        }
        if ($this->get('data_final')) {
            $data_final = $this->get('data_final', null, 'date');
            $sql .= " AND (a.id_financeiro IS NOT NULL AND ((f.status = 'pendente' AND f.data_criacao <= '$data_final') OR f.data_modificacao <= '$data_final') OR (a.id_financeiro IS NULL AND a.data_modificacao <= '$data_final'))";
        }
        $sql .= ' GROUP BY a.id_parceiro ORDER BY financeiros_pendentes DESC, procedimentos_pendentes DESC';
        $query = $this->db->query($sql);

		if ($query->num_rows()) {
            if ($this->get('export') == 'csv') {
				echo $this->dbutil->csv_from_result($query, ';');
                die();
			} else {
                $this->response($query->result_array(), REST_Controller::HTTP_OK);
            }
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Erro ao gerar relatório'
			), REST_Controller::HTTP_OK);
		}
	}

	public function parceiro_get($search_value=null, $search_field=null)
	{
        $sql = "SELECT 
            p.id_parceiro,
            UPPER(p.nome) AS nome, 
            COUNT(DISTINCT a.id_agendamento) AS qnt_agendamentos,
            COUNT(1 * ap.quantidade) AS qnt_procedimentos,
            SUM(CASE WHEN a.status = 'pago' THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS valor_parceiro_pago,
            SUM(CASE WHEN a.status = 'realizado' THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS valor_parceiro_pendente, 
            SUM(CASE WHEN a.status = 'pendente' THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS valor_parceiro_previsto, 
            SUM(pp.valor_parceiro * ap.quantidade) AS valor_parceiro_total,
            SUM(CASE WHEN a.status = 'pago' THEN (((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) ELSE 0 END) AS valor_cf_pago,
            SUM(CASE WHEN a.status = 'realizado' THEN (((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) ELSE 0 END) AS valor_cf_pendente, 
            SUM(CASE WHEN a.status = 'pendente' THEN (((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) ELSE 0 END) AS valor_cf_previsto, 
            SUM((pp.valor_total - pp.valor_parceiro) * ap.quantidade) AS valor_cf_total
        FROM tb_agendamento a
            INNER JOIN tb_parceiro p ON p.id_parceiro = a.id_parceiro
            INNER JOIN tb_agendamento_procedimento ap ON ap.id_agendamento = a.id_agendamento
            LEFT JOIN tb_parceiro_procedimento pp ON pp.id_parceiro_procedimento = ap.id_parceiro_procedimento
        WHERE a.status != 'cancelado'";
        if ($search_field) {
            $sanitized_search_value = preg_replace('/[^\w\s-]+/', '', $search_value);
            switch ($search_field) {
                case 'nome':
                    $sql .= " AND p.nome LIKE '$sanitized_search_value%'";
                    break;
            }
        }
        if (in_array($this->get('status'), array('pendente', 'realizado', 'pago'))) {
            $status = $this->get('status');
            $sql .= " AND a.status = '$status'";
        }
        if ($this->get('data_inicial')) {
            $data_inicial = $this->get('data_inicial', null, 'date');
            $sql .= " AND DATE(a.data_criacao) >= '$data_inicial'";
        }
        if ($this->get('data_final')) {
            $data_final = $this->get('data_final', null, 'date');
            $sql .= " AND DATE(a.data_criacao) <= '$data_final'";
        }
        $sql .= ' GROUP BY a.id_parceiro ORDER BY valor_parceiro_total DESC';
        $query = $this->db->query($sql);

		if ($query->num_rows()) {
            if ($this->get('export') == 'csv') {
                // $this->_download_send_headers('relatorio-parceiro.csv');
				echo $this->dbutil->csv_from_result($query, ';');
                die();
			} else {
			    $this->response($query->result_array(), REST_Controller::HTTP_OK);
            }
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Erro ao gerar relatório'
			), REST_Controller::HTTP_OK);
		}
	}

    public function procedimento_get($search_value=null, $search_field=null)
	{
        $sql = "SELECT UPPER(p.nome) AS procedimento,
            SUM(ap.quantidade) AS quantidade,
            SUM(CASE WHEN a.status = 'pago' THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS valor_parceiro_pago,
            SUM(CASE WHEN a.status = 'realizado' THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS valor_parceiro_pendente,
            SUM(CASE WHEN a.status = 'pendente' THEN pp.valor_parceiro * ap.quantidade ELSE 0 END) AS valor_parceiro_previsto,
            SUM(pp.valor_parceiro * ap.quantidade) AS valor_parceiro_total,
            SUM(CASE WHEN a.status = 'pago' THEN (((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) ELSE 0 END) AS valor_cf_pago,
            SUM(CASE WHEN a.status = 'realizado' THEN (((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) ELSE 0 END) AS valor_cf_pendente,
            SUM(CASE WHEN a.status = 'pendente' THEN (((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) ELSE 0 END) AS valor_cf_previsto,
            SUM(((pp.valor_total - pp.valor_parceiro) * ap.quantidade) - ap.desconto) AS valor_cf_total
        FROM tb_procedimento p
            INNER JOIN tb_parceiro_procedimento pp ON pp.id_procedimento = p.id_procedimento
            INNER JOIN tb_agendamento_procedimento ap ON ap.id_parceiro_procedimento = pp.id_parceiro_procedimento
            INNER JOIN tb_agendamento a ON a.id_agendamento = ap.id_agendamento 
        WHERE a.status != 'cancelado'";
        if ($search_field) {
            $sanitized_search_value = preg_replace('/[^\w\s-]+/', '', $search_value);
            switch ($search_field) {
                case 'nome':
                    $sql .= " AND p.nome LIKE '$sanitized_search_value%'";
                    break;
            }
        }
        if (in_array($this->get('status'), array('pendente', 'realizado', 'pago'))) {
            $status = $this->get('status');
            $sql .= " AND a.status = '$status'";
        }
        if ($this->get('data_inicial')) {
            $data_inicial = $this->get('data_inicial', null, 'date');
            $sql .= " AND DATE(a.data_criacao) >= '$data_inicial'";
        }
        if ($this->get('data_final')) {
            $data_final = $this->get('data_final', null, 'date');
            $sql .= " AND DATE(a.data_criacao) <= '$data_final'";
        }
        if ($this->get('tipo')) {
            $tipo = $this->get('tipo');
            $sql .= " AND p.tipo = '$tipo'";
        }
        $sql .= ' GROUP BY p.id_procedimento ORDER BY valor_cf_total DESC';
        $query = $this->db->query($sql);

		if ($query->num_rows()) {
            if ($this->get('export') == 'csv') {
                // $this->_download_send_headers('relatorio-procedimento.csv');
				echo $this->dbutil->csv_from_result($query, ';');
                die();
			} else {
			    $this->response($query->result_array(), REST_Controller::HTTP_OK);
            }
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Erro ao gerar relatório'
			), REST_Controller::HTTP_OK);
		}
	}

    public function _download_send_headers($filename) {
        // disable caching
        $now = gmdate("D, d M Y H:i:s");
        header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
        header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
        header("Last-Modified: {$now} GMT");
    
        // force download  
        // header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream;charset=utf-8");
        // header("Content-Type: application/download");
        // header("Content-Type: text/csv");
    
        // disposition / encoding on response body
        header("Content-Disposition: attachment;filename={$filename}");
        header("Content-Transfer-Encoding: binary");
    }
}