<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

class CheckUsers extends Controller {

    //var $User; // This line to be removed, for NetBeans autocomplete only

    function __construct() {
        parent::Controller();
        $this->load->model('User');
        $this->load->library('email');
    }

    function index() {
        //$this->User = new User(); // This line to be removed, for NetBeans autocomplete only
        $this->benchmark->mark('code_start');
        $this->User->setMurmurUsers();
        $this->User->setDbUsers();
        $dbUsers = $this->User->getDbUsers();
        $murmurIDs = $this->User->getMurmurUserIDs();
        $this->_cleanUtilMurmur($dbUsers, $murmurIDs);
        $this->User->setDbUsers();
        $murmurUsers = $this->User->getMurmurUsers();
        foreach ($murmurUsers as $murmurUserID => $username) {
            if ($murmurUserID == 0)
                continue;
            $this->User->setMurmurUserInfo($murmurUserID);
            $murmurUserName = $this->User->getUsername();
            if ($this->User->getUserHash() != "") {
                $this->renameUser($murmurUserID);
                $this->User->applyChanges();
                $this->User->setMurmurUserInfo();
            }
        }
        $this->benchmark->mark('code_end');
    }

    function _cleanUtilMurmur($dbUsers, $murmurIDs) {
        foreach ($dbUsers as $userID => $user) {
            // Clean utilMurmur table of old entries
            if (!in_array($user['murmurUserID'], $murmurIDs)) {
                echo "Removing " . $user['murmurUserID'] . " from utilMurmur...";
                if (!$ret = $this->User->removeFromDB($user['murmurUserID'])) {
                    echo "Failed\n<br />";
                } else {
                    echo " $ret Done\n<br />";
                }
            }
        }
        return TRUE;
    }

    function renameUser($murmurUserID, $newUsername = NULL) {
        if (!isset($newUsername)) {
            if (!$dbUser = $this->User->getDbUser($murmurUserID)) {
                echo "Failed to get dbUser for $murmurUserID\n<br />";
                return FALSE;
            } else {
                $username = $this->User->getUsername();
                echo "$username";
                if ($username != "[" . $dbUser['ticker'] . "] " . $dbUser['name']) {
                    $newUsername = "[" . $dbUser['ticker'] . "] " . $dbUser['name'];
                    echo " renamed to $newUsername";
                } else {
                    $newUsername = $username;
                }
                echo "\n<br />";
            }
            $this->User->setUsername($newUsername);
            return TRUE;
        }
    }

}

?>
