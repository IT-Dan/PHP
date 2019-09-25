<?php
namespace system\controller;
use SessionHandlerInterface;

/**
 * Session - v2.0 
 * Added compatibility to PHP 7.2 and higher
 * Modified By: Danny Vargas
 */

class Session implements SessionHandlerInterface {
	public $appname;
	var $db;
	var $storage;

	function __construct($storage = 'database'){
		$this->db = new Database();
		if($storage == 'database'){
			session_set_save_handler(
				array($this, "open"),
				array($this, "close"),
				array($this, "read"),
				array($this, "write"),
				array($this, "destroy"),
				array($this, "gc")
			);
		}

		/* check if session has started */
		if( !$this->is_session_started() ) {
			session_start();
		}
	}

	public function open($save_path, $session_name){
		if($this->db){
		  	return true;
		}
		return false;
	}

	public function close(){
		if($this->db->closeDB()){
		  	return true;
		}
		return false;
	  }

	public function read($id){
		/* declare $row var - this must be empty*/
		$row = '';
		$this->db->query('SELECT `data` FROM `app_sessions` WHERE `id` = :id');
		$this->db->bind(':id', $id);
		$this->db->execute();
		$count = $this->db->rowCount();
		
		/* if the count is higher than zero then get the data and store it in $row */
		if($count > 0) {
			$row = $this->db->single();
			$row = $row['data'];
		}

		/* check if $row output is a string. If not, make it to a string. This is important */
		if (!is_string($row)) {
			var_dump($row);
		}

		return $row;
	}
	  
	public function write($id, $data){
		$access = time();
		$this->db->query('REPLACE INTO `app_sessions` VALUES (:id, :access, :data)');
		$this->db->bind(':id', $id);
		$this->db->bind(':access', $access);  
		$this->db->bind(':data', $data);
		if($this->db->execute()){
			//session_write_close();
			return true;
		}
		return false;
	}	  

	public function destroy($id){
		$this->db->query('DELETE FROM `app_sessions` WHERE `id` = :id');
		$this->db->bind(':id', $id);
		if($this->db->execute()){
		  return true;
		}
		return false;
	} 
	  
	public function gc($max){
		$old = time() - $max;
		$this->db->query('DELETE FROM `app_sessions` WHERE `access` < :old');
		$this->db->bind(':old', $old);
		if($this->db->execute()){
			return true;
		}
		return false;
	}	  

	public function start_session($_webapp_name){
		$this->appname = $_webapp_name;

		/* execute GC */
		// session_gc();
        /* if the session is empty, then set the is_login variable false */
		if (empty($_SESSION[$this->appname])){
			$this->createGuestSession();
		}
	}
	
	public function check_login_status(){
		return $_SESSION[$this->appname]['is_login'];
	}

	private function createGuestSession(){
        $_SESSION[$this->appname] = array('is_login'=>'false','grp_id'=>0);
	}
	
	public function get_session_data(){
		return $_SESSION[$this->appname];
	}
	
	public function get_session_name(){
		return $this->appname;
	}

	public function delete_session(){
		session_destroy();
		$this->createGuestSession();
		header('location: index.php');
	}
	
	public function check_session(){
		if( $_SESSION[$this->appname]['is_login'] == false ){
			die('<p>You are not authenicated!</p>');
		} 
	}

    public function is_session_started() {
        if ( php_sapi_name() !== 'cli' ) {
            if ( version_compare(phpversion(), '5.4.0', '>=') ) {
                return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
            } else {
                return session_id() === '' ? FALSE : TRUE;
            }
        }
        return FALSE;
    }

}
