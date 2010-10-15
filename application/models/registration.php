<?php
class Registration extends Model {

    private $uname_array;
    private $selected_user;
    private $userid;
    private $apikey;

    function Registration() {
        parent::Model();
        $this->uname_array = array();
        $this->selected_user = '';
        $this->userid = '';
        $this->apikey = '';
    }
    
    public function getUname_array() {
        return $this->uname_array;
    }

    public function setUname_array($uname_array) {
        $this->uname_array = $uname_array;
    }

    public function addUser_to_array($username) {
        $this->uname_array[] = $username;
    }

    public function getSelected_user() {
        return $this->selected_user;
    }

    public function setSelected_user($selected_user) {
        $this->selected_user = $selected_user;
    }

    public function getUserid() {
        return $this->userid;
    }

    public function setUserid($userid) {
        $this->userid = $userid;
    }

    public function getApikey() {
        return $this->apikey;
    }

    public function setApikey($apikey) {
        $this->apikey = $apikey;
    }


}
?>
