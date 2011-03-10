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
        $output = array();
        $users = $this->Murmur_model->getUserNames();
        if ($users == NULL) {
            log_message('error', '<' . __FUNCTION__ . '> Failed to get users from Murmur');
        } else {
            foreach ($users as $userid => $username) {
                // Skip SuperUser
                if ($userid == 0)
                    continue;
                $userInfo = $this->Murmur_model->getUserInfo($userid);
                if ($userInfo == NULL) {
                    log_message('error', '<' . __FUNCTION__ . '> Failed to get UserInfo for: ' . $username);
                    continue;
                } else {
                    $this->db->select('eveCorpTicker, eveCharName, eveAllyTicker')->from('eveUser')->where('murmurUserID', $userid);
                    $query = $this->db->get();
                    log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
                    $row = $query->row();
                    if ($query->num_rows() < 1 || !isset($row->eveCorpTicker)) {
                        log_message('info', '<' . __FUNCTION__ . '> Updating DB for user: ' . $userid);
                        if (!$this->Pheal_model->updateUserDetails($userid)) {
                            log_message('error', '<' . __FUNCTION__ . '> Failed to update eve user: ' . $username);
                            continue;
                        }
                        log_message('debug', '<' . __FUNCTION__ . '> Updated DB for user: ' . $userid);
                        $this->db->select('eveCorpTicker, eveCharName, eveAllyTicker')->from('eveUser')->where('murmurUserID', $userid);
                        $query = $this->db->get();
                        log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
                        $row = $query->row();
                    }
                    if (!isset($userInfo['userHash']) || empty($userInfo['userHash'])) {
                        log_message('info', '<' . __FUNCTION__ . '> User not logged in yet: ' . $username);
                        if ($userInfo['username'] != $row->eveCharName) {
                            log_message('info', '<' . __FUNCTION__ . '> Resetting username to: ' . $row->eveCharName);
                            $newUserInfo = $userInfo;
                            $newUserInfo['username'] = $row->eveCharName;
                            if (!$this->Murmur_model->updateUserInfo($userid, $newUserInfo)) {
                                log_message('error', '<' . __FUNCTION__ . '> Failed to reset username: ' . $row->eveCharName . ' - ' . $this->Murmur_model->errorMessage);
                            } else {
                                log_message('debug', '<' . __FUNCTION__ . '> Reset ' . $username . ' to ' . $row->eveCharName);
                            }
                        }
                        continue;
                    }
                    $newUserName = $row->eveCharName . ' ';
                    if (isset($row->eveAllyTicker))
                        $newUserName = $newUserName . '<' . $row->eveAllyTicker . '>';
                    $newUserName = $newUserName . '[' . $row->eveCorpTicker . ']';
                    log_message('info', '<' . __FUNCTION__ . '> New Username: ' . $newUserName);
                    log_message('info', '<' . __FUNCTION__ . '> Old Username: ' . $userInfo['username']);
                    if ($newUserName != $userInfo['username']) {
                        if (!isset($userInfo['userEmail']))
                            $userInfo['userEmail'] = '';
                        if (!isset($userInfo['userComment']))
                            $userInfo['userComment'] = '';
                        if (!isset($userInfo['userHash']))
                            $userInfo['userHash'] = '';
                        $newUserInfo = array(
                            $newUserName,
                            $userInfo['userEmail'],
                            $userInfo['userComment'],
                            $userInfo['userHash']
                        );
                        if (!$this->Murmur_model->updateUserInfo($userid, $newUserInfo)) {
                            log_message('error', '<' . __FUNCTION__ . '> Failed to update registration: ' . $userid . ' - ' . $newUserName . ' - ' . $this->Murmur_model->errorMessage);
                        } else {
                            log_message('debug', '<' . __FUNCTION__ . '> Updated ' . $username . ' to ' . $newUserName);
                        }
                    }
                }
            }
        }
    }

    function checkUsers() {
        $where = '`apiLastChecked` NOT BETWEEN ADDDATE(NOW(), INTERVAL -1 HOUR) AND NOW()';
        $this->db->order_by('apiLastChecked', 'DESC');
        $users = $this->db->get_where('eveUser', $where, 30);
        $this->Pheal_model->updateBlues();
        $blues = $this->Pheal_model->loadBlues();
        foreach ($users->result() as $user) {
            if ($this->Murmur_model->getUserInfo(intval($user->murmurUserID)) == NULL) {
                log_message('info', '<' . __FUNCTION__ . '> ' . $user->eveCharName . ' not registered');
                log_message('info', '<' . __FUNCTION__ . '> Deleting from DB: ' . $user->eveCharName);
                $this->db->trans_start();
                $this->db->delete('eveUser', array('murmurUserID' => $user->murmurUserID));
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    log_message('error', '<' . __FUNCTION__ . '> Failed to delete ' . $user->eveCharName . ' from DB');
                }
                log_message('debug', '<' . __FUNCTION__ . '> Deleted from DB: ' . $user->eveCharName);
                continue;
            }
            log_message('debug', '<' . __FUNCTION__ . '> Updating: ' . $user->eveCharID);
            $this->Pheal_model->updateUserDetails($user->murmurUserID);
            $query = $this->db->get_where('eveUser', array('murmurUserID' => $user->murmurUserID));
            $user = $query->row();
            if (!in_array($user->eveCharID, $blues) && !in_array($user->eveCorpID, $blues) && !in_array($user->eveAllyID, $blues)
                    && $user->eveCorpID != $this->config->item('corpID') && $user->eveAllyID != $this->config->item('allianceID')) {
                log_message('info', '<' . __FUNCTION__ . '> ' . $user->eveCharName . ' is not blue');
                log_message('info', '<' . __FUNCTION__ . '> Unregistering: ' . $user->eveCharName);
                if (!$this->Murmur_model->unregisterUser(intval($user->murmurUserID))) {
                    log_message('error', '<' . __FUNCTION__ . '> Failed to unregister: ' . $user->eveCharName);
                } else {
                    log_message('debug', '<' . __FUNCTION__ . '> Not blue. Unregistered: ' . $user->eveCharName);
                }
            }
        }
    }

}

?>
