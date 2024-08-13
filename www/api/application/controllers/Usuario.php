<?php


defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . 'libraries/REST_Controller.php';

class Usuario extends REST_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model('usuario_model');

		$this->methods['index_get'] = array('limit' => 240, 'level' => 10);
		$this->methods['index_post'] = array('limit' => 60, 'level' => 10);
		$this->methods['index_put'] = array('limit' => 60, 'level' => 10);
		$this->methods['perfil_get'] = array('limit' => 240, 'level' => 1);
		$this->methods['info_get'] = array('limit' => 240, 'level' => 1);
		$this->methods['senha_put'] = array('limit' => 15, 'level' => 1);
		$this->methods['recupera_senha_get']['limit'] = 15;
		$this->methods['recupera_senha_post']['limit'] = 15;
		$this->methods['login_post']['limit'] = 15;
	}

	public function index_get( $id=null, $field=null )
	{	
		$options = array(
			'fields'	=> 'tb_usuario.id_usuario, tb_usuario.email, tb_usuario.status, tb_usuario.id_role, tb_usuario.ultimo_acesso, tb_pessoa_fisica.id_pessoa_fisica, tb_pessoa_fisica.nome, tb_pessoa_fisica.nome_social, tb_pessoa_fisica.sexo, tb_pessoa_fisica.data_nascimento, tb_pessoa_fisica.cpf, tb_pessoa_fisica.rg, tb_pessoa_fisica.telefone, tb_pessoa_fisica.telefone2, tb_pessoa_fisica.email as pf_email, tb_pessoa_fisica.pai, tb_pessoa_fisica.mae, tb_pessoa_fisica.responsavel',
			'join'		=> array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa = tb_usuario.id_pessoa', 'inner')
		);

		if (isset($_GET['status']) && $_GET['status'] !== '') {
			$options['where']['tb_usuario.status'] = $_GET['status'];
		}

		if ($id && $field) {
			switch ($field) {
				case 'email':
					$options['where']['tb_usuario.email LIKE'] = "'$id%'";
					break;
			}
			$resultado = $this->usuario_model->get(null, $options);
		} else {
			$resultado = $this->usuario_model->get($id, $options);
		}


		if ($resultado) {

			foreach ($resultado as $key => $row) {
				$pessoa_fisica = array(
					'id_pessoa_fisica'	=> $resultado[$key]['id_pessoa_fisica'],
					'nome'				=> $resultado[$key]['nome'],
					'nome_social'		=> $resultado[$key]['nome_social'],
					'sexo'				=> $resultado[$key]['sexo'],
					'email'				=> $resultado[$key]['pf_email'],
					'rg'				=> $resultado[$key]['rg'],
					'cpf'				=> $resultado[$key]['cpf'],
					'data_nascimento'	=> $resultado[$key]['data_nascimento'],
					'telefone'			=> $resultado[$key]['telefone'],
					'telefone2'			=> $resultado[$key]['telefone2'],
					'pai'				=> $resultado[$key]['pai'],
					'mae'				=> $resultado[$key]['mae'],
					'responsavel'		=> $resultado[$key]['responsavel']
				);

				$resultado[$key] = (object) array(
					'id_usuario'	=> $resultado[$key]['id_usuario'],
					'email'			=> $resultado[$key]['email'],
					'status'		=> $resultado[$key]['status'],
					'tipo'			=> $resultado[$key]['id_role'],
					'ultimo_acesso'	=> $resultado[$key]['ultimo_acesso'],
					'pf'			=> $pessoa_fisica
				);
			}

			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum usuário encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function index_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data);

		if ( $validacao === TRUE ) {

			$this->db->trans_start();

			if ($data['id_pessoa']) {
				$id_pessoa = $data['id_pessoa'];
			} else {
				$pessoa = array(
					'pessoa_juridica' => 0
				);
				$this->db->insert('tb_pessoa', $pessoa);
				$id_pessoa = $this->db->insert_id();

				$pessoa_fisica = array(
					'nome'				=> $data['pf']['nome'],
					'nome_social'		=> $data['pf']['nome_social'],
					'sexo'				=> $data['pf']['sexo'],
					'rg'				=> $data['pf']['rg'],
					'cpf'				=> str_replace(array('.', '-'), '', $data['pf']['cpf']),
					'data_nascimento'	=> $data['pf']['data_nascimento'],
					'telefone'			=> $data['pf']['telefone'],
					'email'				=> $data['pf']['email'],
					'pai'				=> $data['pf']['pai'],
					'mae'				=> $data['pf']['mae'],
					'responsavel'		=> $data['pf']['responsavel'],
					'id_pessoa'			=> $id_pessoa
				);
				$this->db->insert('tb_pessoa_fisica', $pessoa_fisica);
				$id_pessoa_fisica = $this->db->insert_id();
			}

			$usuario = array(
				'email'		=> $data['email'],
				'senha'		=> $data['senha'],
				'id_pessoa'	=> $id_pessoa,
				'id_role'	=> $data['tipo']
			);
			$this->db->insert('tb_usuario', $usuario);
			
			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao cadastrar o usuário. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_BAD_REQUEST);
			} else {
				$this->response($usuario, REST_Controller::HTTP_CREATED);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_BAD_REQUEST);
		}
	}

	public function index_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida($data, true);

		if ( $validacao === TRUE && isset($data['pf']) ) {

			$this->db->trans_start();

			$sql = 'UPDATE tb_usuario AS u
				INNER JOIN tb_pessoa_fisica AS pf ON pf.id_pessoa = u.id_pessoa
				SET u.email = ?, 
					u.id_role = ?,
					pf.nome = ?,
					pf.nome_social = ?,
					pf.sexo = ?,
					pf.rg = ?,
					pf.cpf = ?,
					pf.data_nascimento = ?,
					pf.telefone = ?,
					pf.email = ?,
					pf.pai = ?,
					pf.mae = ?,
					pf.responsavel = ?
				WHERE u.id_usuario = ?';

			$query = $this->db->query($sql, array(
				$data['email'],
				$data['tipo'],
				$data['pf']['nome'],
				$data['pf']['nome_social'],
				$data['pf']['sexo'],
				$data['pf']['rg'],
				str_replace(array('.', '-'), '', $data['pf']['cpf']),
				$data['pf']['data_nascimento'],
				$data['pf']['telefone'],
				$data['pf']['email'],
				$data['pf']['pai'],
				$data['pf']['mae'],
				$data['pf']['responsavel'],
				$data['id_usuario']
			));

			if (!empty($data['senha'])) {
				$this->db->query('UPDATE tb_usuario SET senha = ? WHERE id_usuario = ?', 
					array(
						$data['senha'],
						$data['id_usuario']
					)
				);
			}

			$this->db->trans_complete();

			if ($this->db->trans_status() === FALSE) {
				$this->response(array(
					'status'	=> FALSE,
					'message'	=> 'Ocorreu um erro ao atualizar o usuário. Por favor, verifique os campos do formulário.'
				), REST_Controller::HTTP_OK);
			} else {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_OK);
		}
	}

	public function perfil_get()
	{	
		$options = array(
			'fields'	=> 'id_usuario, nome, tb_usuario.email, status',
			'join'		=> array('tb_pessoa_fisica', 'tb_pessoa_fisica.id_pessoa = tb_usuario.id_pessoa', 'inner')
		);

		$resultado = $this->usuario_model->get($this->rest->user_id, $options);

		if ($resultado) {
			$this->response($resultado, REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum usuário encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function info_get()
	{
		$query = $this->db->query("SELECT nome, tb_usuario.email, `status`, `level` FROM tb_usuario
			INNER JOIN `keys` ON `keys`.user_id = tb_usuario.id_usuario
			INNER JOIN tb_pessoa ON tb_pessoa.id_pessoa = tb_usuario.id_pessoa
			LEFT JOIN tb_pessoa_fisica ON tb_pessoa_fisica.id_pessoa = tb_pessoa.id_pessoa
			LEFT JOIN tb_pessoa_juridica ON tb_pessoa_juridica.id_pessoa = tb_pessoa.id_pessoa
			WHERE tb_usuario.id_usuario = ? AND `keys`.`key` = ?", array($this->rest->user_id, $this->rest->key));

		if ($query->num_rows()) {
			$this->response($query->result_array(), REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' 	=> FALSE,
				'message' 	=> 'Nenhum usuário encontrado'
			), REST_Controller::HTTP_OK);
		}
	}

	public function senha_put()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$validacao = $this->valida_senha($data);

		if ($validacao === TRUE) {
			$where = array('id_usuario' => $this->rest->user_id);

			if ( $this->usuario_model->put($where, array('senha' => $data['senha'])) ) {
				$this->response(null, REST_Controller::HTTP_NO_CONTENT);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Nenhum usuário encontrado'
				), REST_Controller::HTTP_OK);
			}
		} else {
			$this->response($validacao, REST_Controller::HTTP_OK);
		}

	}

	public function recupera_senha_get($hash='')
	{
		if ($this->_verify_hash($hash)) {
			$this->response(TRUE, REST_Controller::HTTP_OK);
		} else {
			$this->response('', REST_Controller::HTTP_OK);
		}
	}

	public function recupera_senha_post()
	{
		$data = json_decode($this->input->raw_input_stream, TRUE);
		
		if ($data['hash']) {
			if ((strlen($data['senha']) > 5) && ($data['senha'] != $data['senha_confirma'])) {
				$response = array(
					'status' 	=> FALSE,
					'message' 	=> 'As senhas informadas devem ser iguais'
				);
			} else {
				$id_usuario = $this->_verify_hash($data['hash']);
				if ($id_usuario) {
					$where = array('id_usuario' => $id_usuario);
					if ( $this->usuario_model->put($where, array('senha' => $data['senha'])) ) {
						$response = TRUE;
						$this->db->query('DELETE FROM tb_reseta_senha WHERE id_usuario = ?', array($id_usuario));
					} else {
						$response = array(
							'status' 	=> FALSE,
							'message' 	=> 'Usuário não encontrado'
						);
					}
				} else {
					$response = array(
						'status' 	=> FALSE,
						'message' 	=> 'Link de recuperação de senha inválido! Tente gerar um novo link.'
					);
				}
			}
			$this->response($response, REST_Controller::HTTP_OK);
		} else if ($data['email']) {
			$options = array(
				'fields'	=> 'id_usuario, email',
				'where'		=> array('email' => $data['email'], 'status' => 1)
			);
			$usuario = $this->usuario_model->get(null, $options);
	
			if ($usuario) {
				$hash = $this->_generate_random_hash(45);
	
				$this->db->query('DELETE FROM tb_reseta_senha WHERE id_usuario = ?', array($usuario[0]['id_usuario']));
				$this->db->query('INSERT INTO tb_reseta_senha (id_usuario, hash) VALUES (?, ?)', array($usuario[0]['id_usuario'], $hash));
	
				$this->load->library('email');
				$this->email->from('naoresponda@consultafacilthe.com.br', 'Consulta Fácil THE');
				$this->email->to($usuario[0]['email']);
				$this->email->subject('Consulta Fácil THE - Recuperação de Senha');
				
				$link = 'http://localhost:4500/#/recupera-senha?hash='. $hash;
				$message = '<h4>RECUPERAÇÃO DE SENHA</h4>
				<p>Foi realizada uma solicitação para recuperar sua senha no sistema <a href="http://localhost:4500" target="_blank" style="font-weight:bold;">Consulta Fácil THE</a>.</p>
				<p>Para atualizar sua senha acesse o link abaixo:</p>
				<br/>
				<p><a href="'. $link .'" target="_blank">'. $link .'</a></p>
				<br/>
				<p>Caso esta solicitação não tenha sido feita por você, ignore esta mensagem.</p>
				<p>--<br/>
					Atenciosamente<br/>
					<b>Equipe Consulta Fácil THE</b></p>
				<img src="http://localhost:4500/site/wp-content/themes/consultafacil/images/logo.png" alt="Consulta Fácil" height="80" width="226" />
				<br/><br/>
				<p style="font-size:14px;color:#999;">*Não responda esta mensagem.</p>';
		
				$this->email->message($message);
		
				$this->response($this->email->send(), REST_Controller::HTTP_OK);
			} else {
				$this->response(array(
					'status' 	=> FALSE,
					'message' 	=> 'Usuário não encontrado'
				), REST_Controller::HTTP_BAD_REQUEST);
			}
		}

	}

	private function _verify_hash($hash) 
	{
		$query = $this->db->query('SELECT id_usuario FROM tb_reseta_senha WHERE BINARY hash = ?', array($hash));
		return ($query->num_rows() > 0)? $query->result_array()[0]['id_usuario'] : false;
	}

	private function _generate_random_hash($tamanho)
	{
		$characters = '.+_-abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPRSTUVWXYZ0123456789';
		$hash = '';
		$max = strlen($characters) - 1;
		for ($i = 0; $i < $tamanho; $i++) {
			$hash .= $characters[mt_rand(0, $max)];
		}
		return $hash;
	}

	public function login_post()
	{
		$data = json_decode($this->input->raw_input_stream, true);
		$this->_login($data['email'], md5($data['senha']));
	}

	private function _login($usuario, $senha, $tipo='')
	{
		date_default_timezone_set('America/Sao_Paulo');

		switch ($tipo) {
			case 'funcionario':
				$sql = "SELECT tb_usuario.*, tb_role.level, tb_role.id_role, tb_pessoa_fisica.nome AS nome_usuario FROM tb_usuario
					INNER JOIN tb_role ON tb_role.id_role = tb_usuario.id_role
					INNER JOIN tb_pessoa_fisica ON tb_pessoa_fisica.id_pessoa = tb_usuario.id_pessoa
					WHERE tb_role.level >= 5 AND tb_usuario.status = 1 AND tb_usuario.email = ? AND tb_usuario.senha = ?";

				// QUANDO A TABELA FUNCIONÁRIO ESTIVER EM OPERAÇÃO ADICIONAR O JOIN:
				// INNER JOIN tb_funcionario ON tb_funcionario.id_pessoa_fisica = tb_pessoa_fisica.id_pessoa_fisica
				break;
			default:
				$sql = "SELECT tb_usuario.*, tb_role.level, tb_role.id_role, tb_pessoa_fisica.nome AS nome_usuario, tb_pessoa_juridica.nome_fantasia AS nome_usuario_pj FROM tb_usuario
					INNER JOIN tb_role ON tb_role.id_role = tb_usuario.id_role
					LEFT JOIN tb_pessoa_fisica ON tb_pessoa_fisica.id_pessoa = tb_usuario.id_pessoa
					LEFT JOIN tb_pessoa_juridica ON tb_pessoa_juridica.id_pessoa = tb_usuario.id_pessoa
					WHERE tb_role.level >= 4 AND tb_usuario.status = 1 AND tb_usuario.email = ? AND tb_usuario.senha = ?";
				break;
		}
		
		$query = $this->db->query($sql, array($usuario, $senha));
		if ($query->num_rows()) {
			$usuario_logado = $query->result_array();
			$nome_usuario = (isset($usuario_logado[0]['nome_usuario']) && !empty($usuario_logado[0]['nome_usuario']))? $usuario_logado[0]['nome_usuario'] : $usuario_logado[0]['nome_usuario_pj'];
		}

		// if (isset($usuario_logado) && $this->usuario_model->put(array('id_usuario' => $usuario_logado[0]['id_usuario']), array('ultimo_acesso' => date('Y-m-d H:i:s')))) {
		if (isset($usuario_logado)) {
			$this->load->model('keys_model');

			 // Build a new key
			$key = $this->_generate_key();

			// If no key level provided, provide a generic key
			$level = isset($usuario_logado[0]['level']) ? $usuario_logado[0]['level'] : -1;
			$ignore_limits = ctype_digit($this->put('ignore_limits')) ? (int) $this->put('ignore_limits') : 1;

			// Insert the new key
			if ($this->_insert_key($key, array(
					'level' => $level, 
					'ignore_limits' => $ignore_limits, 
					'user_id' => $usuario_logado[0]['id_usuario'], 
					'id_role' => $usuario_logado[0]['id_role'])
				)) {
				$this->response(array(
					'status' 	=> TRUE,
					'key' 		=> $key,
					'nome'		=> $nome_usuario,
					'email'		=> $usuario,
					'uid' 		=> $usuario_logado[0]['id_usuario'],
					'level'		=> $level
				), REST_Controller::HTTP_OK);
			} else {
				$this->response(array(
					'status' => FALSE,
					'message' => 'A chave não pôde ser salva'
				), REST_Controller::HTTP_INTERNAL_SERVER_ERROR);
			}
		} else {
			$this->response(array(
				'status'	=> FALSE,
				'message'	=> 'Dados de login incorreto'
			));
		}
	}

	public function logout_get() {
		if ( $this->_delete_key($this->input->get_request_header(config_item('rest_key_name'))) ) {
			$this->response(array(
				'status' => TRUE,
				'message' => 'Usuário deslogado com sucesso'
			), REST_Controller::HTTP_OK);
		} else {
			$this->response(array(
				'status' => FALSE,
				'message' => 'Ocorreu um erro ao deslogar o usuário'
			), REST_Controller::HTTP_OK);
		}
	}

	private function valida($data, $update=false)
	{
		$this->load->model('pessoaFisica_model');

		if ($update) {
			$rules = $this->usuario_model->senha_rules;
		} else {
			$rules = array_merge($this->usuario_model->rules, $this->pessoaFisica_model->rules);
		}

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}

	private function valida_senha($data)
	{
		$this->load->model('pessoaFisica_model');

		$this->form_validation->set_data($data);
		$this->form_validation->set_rules($this->usuario_model->senha_rules);

		if ( $this->form_validation->run() ) {
			return TRUE;
		} 
		return array(
			'status'	=> FALSE,
			'erros'		=> $this->form_validation->error_array()
		);
	}

	 /* Helper Methods */
	protected function _perform_library_auth($username = '', $password = NULL)
	{
		var_dump($password);
		var_dump($username);
		/*$options = array('where' => "usuario='$username' and senha='$password'");

		$usuario_logado = $this->usuario_model->get(null, $options);

		if ($usuario_logado) {
			$this->load->model('keys_model');

			 // Build a new key
			$key = $this->_generate_key();

			// If no key level provided, provide a generic key
			$level = $this->put('level') ? $this->put('level') : 1;
			$ignore_limits = ctype_digit($this->put('ignore_limits')) ? (int) $this->put('ignore_limits') : 1;

			// Insert the new key
			if ($this->_insert_key($key, ['level' => $level, 'ignore_limits' => $ignore_limits]))
			{
				$this->response([
					'status' => TRUE,
					'key' => $key
				], REST_Controller::HTTP_CREATED); // CREATED (201) being the HTTP response code
			}
			else
			{
				$this->response([
					'status' => FALSE,
					'message' => 'Could not save the key'
				], REST_Controller::HTTP_INTERNAL_SERVER_ERROR); // INTERNAL_SERVER_ERROR (500) being the HTTP response code
			}
		}	*/
	}

	private function _generate_key()
	{
		do
		{
			// Generate a random salt
			$salt = base_convert(bin2hex($this->security->get_random_bytes(64)), 16, 36);

			// If an error occurred, then fall back to the previous method
			if ($salt === FALSE)
			{
				$salt = hash('sha256', time() . mt_rand());
			}

			$new_key = substr($salt, 0, config_item('rest_key_length'));
		}
		while ($this->_key_exists($new_key));

		return $new_key;
	}

	/* Private Data Methods */

	private function _get_key($key)
	{
		return $this->rest->db
			->where(config_item('rest_key_column'), $key)
			->get(config_item('rest_keys_table'))
			->row();
	}

	private function _key_exists($key)
	{
		return $this->rest->db
			->where(config_item('rest_key_column'), $key)
			->count_all_results(config_item('rest_keys_table')) > 0;
	}

	private function _insert_key($key, $data)
	{
		$data[config_item('rest_key_column')] = $key;
		$data['date_created'] = function_exists('now') ? now() : time();

		return $this->rest->db
			->set($data)
			->insert(config_item('rest_keys_table'));
	}

	private function _update_key($key, $data)
	{
		return $this->rest->db
			->where(config_item('rest_key_column'), $key)
			->update(config_item('rest_keys_table'), $data);
	}

	private function _delete_key($key)
	{
		return $this->rest->db
			->where(config_item('rest_key_column'), $key)
			->delete(config_item('rest_keys_table'));
	}
}
