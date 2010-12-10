<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

class CheckUsers extends Controller {

    var $user; // This line to be removed, for NetBeans autocomplete only

    function __construct() {
        parent::Controller();
        $this->load->model('User');
        $this->load->library('email');
    }

    function index() {
        $this->user = new User(); // This line to be removed, for NetBeans autocomplete only
        $this->user->setMurmurUsers();
        $this->user->setDbUsers();
        $dbUsers = $this->user->getDbUsers();
        $murmurUsers = $this->user->getMurmurUsers();
        $murmurUserIDs = array();
        foreach ($murmurUsers as $id => $username) {
            $murmurUserIDs[] = $id;
        }
        foreach ($dbusers as $userID => $user) {
            if (!in_array($userID, $murmurUserIDs)) {
                // TODO: Delete from DB userIDs not on mumble server
                // Probably best to email results before *actually* doing this
            }
        }
    }

}

?>
