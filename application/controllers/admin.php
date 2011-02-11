<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * admin - Main controller of all admin fucntionality
 *
 * @author Ben Cole <wengole@gmail.com>
 * @property Pheal_model $Pheal_model
 * @property Murmur_model $Murmur_model
 */
class Admin extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model(array('Pheal_model', 'Murmur_model'));
    }

    function renameAll() {
        $users = $this->Murmur_model->getUserNames();
        if ($users == NULL) {
            log_message('error', 'Failed to get users from Murmur');
        } else {
            foreach ($users as $userid => $username) {
                // Skip SuperUser
                if ($userid == 0)
                    continue;
                $userInfo = $this->Murmur_model->getUserInfo($userid);
                if ($userInfo == NULL) {
                    log_message('error', 'Failed to get UserInfo for: ' . $username);
                    continue;
                } else {
                    $this->db->select('eveCorpTicker, eveCharName')->from('eveUser')->where('murmurUserID', $userid);
                    $query = $this->db->get();
                    log_message('info', $this->db->last_query());
                    $row = $query->row();
                    if ($query->num_rows() < 1 || !isset($row->eveCorpTicker)) {
                        log_message('debug', 'Updating DB for user: ' . $userid);
                        if (!$this->Pheal_model->updateUserDetails($userid)) {
                            log_message('error', 'Failed to update eve user: ' . $username);
                            continue;
                        }
                        $this->db->select('eveCorpTicker, eveCharName')->from('eveUser')->where('murmurUserID', $userid);
                        $query = $this->db->get();
                        log_message('info', $this->db->last_query());
                        $row = $query->row();
                    }
                    $newUserName = '[' . $row->eveCorpTicker . '] ' . $row->eveCharName;
                    log_message('info', 'New Username: ' . $newUserName);
                    log_message('info', 'Old Username: ' . $userInfo['username']);
                    if ($newUserName != $userInfo['username']) {
                        if (!isset($userInfo['userEmail']))
                            $userInfo['userEmail'] = "";
                        $newUserInfo = array(
                            $newUserName,
                            $userInfo['userEmail'],
                            $userInfo['userComment'],
                            $userInfo['userHash']
                        );
                        if (!$this->Murmur_model->updateUserInfo($userid, $newUserInfo)) {
                            log_message('error', 'Failed to update registration: ' . $username);
                        } else {
                            log_message('debug', 'Updated ' . $username . ' to ' . $newUserName);
                        }
                    }
                }
            }
        }
    }

}

?>
