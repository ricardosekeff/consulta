<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require APPPATH . 'libraries/REST_Controller.php';

class Menu extends REST_Controller {

	function __construct()
	{
		parent::__construct();
		$this->load->model('menu_model');

		$this->methods['index_get'] = array('limit' => 240, 'level' => 1);
	}

	public function index_get( $cpf=null )
	{
		$sql = 'SELECT parent.id_menu, parent.nome, parent.link, parent.icone, parent.exact, CONCAT(\'[\', GROUP_CONCAT(\'{"id_menu":\', child.id_menu, \', "nome":"\', child.nome, \'", "link":"\', child.link, \'", "exact":\', child.exact, \'}\'), \']\') AS submenus FROM tb_menu AS parent
			INNER JOIN tb_menu AS child ON parent.id_menu = child.parent_menu
			INNER JOIN tb_menu_role ON parent.id_menu = tb_menu_role.id_menu
			INNER JOIN `keys` ON `keys`.id_role = tb_menu_role.id_role
			WHERE parent.parent_menu IS NULL AND `keys`.key = ?
			GROUP BY child.parent_menu
			ORDER BY parent.nome';

		$query = $this->db->query($sql, $this->rest->key);

		if ($query->num_rows() > 0) {
			$resultado = $query->result_array();

			foreach ($resultado as $key => $menu) {
				if (isset($menu['submenus'])) {
					$resultado[$key]['submenus'] = json_decode($menu['submenus'], true);
				}
			}

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum menu encontrado'
			), REST_Controller::HTTP_OK);
		}
	}
}