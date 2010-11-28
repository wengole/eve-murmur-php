<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed'); 

class Registration {

    public $uname_array = array();
    public $selected_user;
    public $userid;
    public $apikey;
    public $username;
    public $password;
    public $password2;
    public $host;
    public $port;

    public function getSelectedUser() {
        return $this->uname_array[$this->selected_user];
    }

    public function addUsername($username) {
        $this->uname_array[] = $username;
    }
}
?>
