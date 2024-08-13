<?php
class Api_auth {
	private $CI;

	function __construct() {
		$this->CI =& get_instance();
		$this->CI->load->database();
	}

	public function login($username, $password) 
	{
		$md5Password = md5($password);
		$usuario = $this->CI->db->where("usuario='$username' and senha='$md5Password'")->get('tb_usuario')->row();


		if ($usuario){
			return md5("$username:CFACIL API:$md5Password");
		} else {
			return false;
		}
	}
}