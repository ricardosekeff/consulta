<?php

class MY_Model extends CI_Model {

	public $table = '';
	public $pk = 'id';
	public $rules = array();

	function __construct() {
		parent::__construct();
	}

	function get( $id=null, $options=array(), $return_array=true ) {
		$options_default = array(
			'sort' 		=> $this->table .'.'. $this->pk,
			'order' 	=> 'DESC',
			'fields' 	=> null,
			'distinct'	=> false,
			'where' 	=> null,
			'join'		=> null,
			'group_by'	=> '',
			'page'		=> 0,
			'per_page'	=> -1
		);

		$options = array_merge($options_default, $options);

		if ( $options['fields'] ) {
			$this->db->select($options['fields']);
		}

		$this->db->distinct($options['distinct']);

		if ( $options['join'] && is_array($options['join']) ) {
			if ( is_array($options['join'][0]) ) {
				foreach ($options['join'] as $join) {
					$this->db->join($join[0], $join[1], $join[2]);
				}
			} else {
				$this->db->join($options['join'][0], $options['join'][1], $options['join'][2]);
			}
		}

		if ( $options['group_by'] ) {
			$this->db->group_by($options['group_by']);
		}

		if ( $id ) {
			$this->db->where("$this->table.$this->pk", $id);
		} else {
			$this->db->order_by($options['sort'], $options['order']);

			if ( $options['per_page'] != -1 ) {
				$paged = $options['page'] * $options['per_page'];
				$this->db->limit($options['per_page'], $paged);
			}

			if ( $options['where'] ) {
				$this->db->where($options['where']);
			}
		}
		
		$query = $this->db->get($this->table);
		if ($query->num_rows() > 0) {
			return ($return_array)? $query->result_array() : $query;
		}

		return null;	
	}

	function post($data) {
		if (is_null($data)) {
			return false;
		}
		return $this->db->insert($this->table, $data);
	}

	function put($where, $data, $options=array()) {
		if (is_null($data)) {
			return false;
		}
		if ( isset($options['join']) && is_array($options['join']) ) {
			if ( is_array($options['join'][0]) ) {
				foreach ($options['join'] as $join) {
					$this->db->join($join[0], $join[1], $join[2]);
				}
			} else {
				$this->db->join($options['join'][0], $options['join'][1], $options['join'][2]);
			}
		}

		if ($this->db->update($this->table, $data, $where)) {
			return $this->db->affected_rows();
		} else {
			return false;
		}
	}

	function delete() {
		return false;
	}

	function count($options) {
		if ( isset($options['where']) ) {
			$this->db->where($options['where']);
		}
		$this->db->from($this->table);
		return $this->db->count_all_results();
	}
}