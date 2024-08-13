<?php
class Caixa_model extends MY_Model {
	function __construct() {
		parent::__construct();
		$this->table = 'tb_caixa';
		$this->pk = 'id_caixa';

		$this->rules = array(
			array(
				'field' 	=> 'id_usuario',
				'label' 	=> 'Usuário',
				'rules' 	=> 'required'
			),
			array(
				'field' 	=> 'valor_abertura',
				'label' 	=> 'Valor de Abertura',
				'rules' 	=> 'required'
			)
		);
	}

	public function _get_caixa_aberto($id_usuario)
    {
        $query = $this->db->query('SELECT id_caixa FROM tb_caixa WHERE id_usuario = ? AND data_fechamento IS NULL', $id_usuario);
        if ($query->num_rows()) {
            return $query->result_array()[0]['id_caixa'];
        } else {
            return false;
        }
    }
}