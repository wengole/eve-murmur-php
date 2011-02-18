<?php

/**
 * Murmur_model - Handles data communication with Mumble server (Murmur)
 * @author Ben Cole <wengole@gmail.com>
 */
class Murmur_model extends CI_Model {

    var $meta;
    var $server;
    var $errorMessage;

    function __construct() {
        parent::__construct();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $this->meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
    }

    /**
     * getUserNames - Retrieves associative array of userIDs to usernames from Murmur
     * 
     * @param int $vServerID ID of Murmur virtual server
     * @return Array NameMap userID => username
     */
    function getUserNames($vServerID = NULL) {
        if (!isset($vServerID)) {
            $vServerID = $this->config->item('vServerID');
        }
        log_message('debug', '<' . __FUNCTION__ . '> Getting registered users on server: ' . $vServerID);
        try {
            $this->server = $this->meta->getServer($vServerID);
            $users = $this->server->getRegisteredUsers('');
        } catch (Murmur_MurmurException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Murmur: ' . $exc->ice_name());
            $this->errorMessage = $exc->ice_name();
            return NULL;
        }
        return $users;
    }

    /**
     * getUserInfo - Gets the registration information of one user from Murmur
     * 
     * @param int $murmurUserID Murmur User ID
     * @param int $vServerID Murmur Server ID
     * @return Array Murmur::UserInfo
     */
    function getUserInfo($murmurUserID, $vServerID = NULL) {
        if (!isset($vServerID)) {
            $vServerID = $this->config->item('vServerID');
        }
        log_message('debug', '<' . __FUNCTION__ . '> Getting registration for: ' . $murmurUserID);
        try {
            $this->server = $this->meta->getServer($vServerID);
            $registration = $this->server->getRegistration($murmurUserID);
        } catch (Murmur_MurmurException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Murmur: ' . $exc->ice_name());
            $this->errorMessage = $exc->ice_name();
            return NULL;
        }
        $userInfo = array(
            'username' => $registration[0],
            'uesrEmail' => $registration[1],
            'userComment' => $registration[2],
            'userHash' => $registration[3],
            //'userPassword' => $registration[4],
            'userLastActive' => $registration[5]
        );
        return $userInfo;
    }

    /**
     * updateUserInfo - Updates registration information of one user on Murmur
     * 
     * @param int $murmurUserID Murmur User ID
     * @param enum $newUserInfo Enumeration of username, email, comment and hash
     * @param int $vServerID Murmur Server ID
     * @return bool Successfully updated user?
     */
    function updateUserInfo($murmurUserID, $newUserInfo, $vServerID = NULL) {
        if (!isset($vServerID)) {
            $vServerID = $this->config->item('vServerID');
        }
        log_message('debug', '<' . __FUNCTION__ . '> Updating registration for: ' . $newUserInfo[0]);
        try {
            $this->server = $this->meta->getServer($vServerID);
            $this->server->updateRegistration($murmurUserID, $newUserInfo);
        } catch (Murmur_MurmurException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Murmur: ' . $exc->ice_name());
            $this->errorMessage = $exc->ice_name();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * unregisterUser - Remove one user from Murmur
     *
     * @param int $murmurUserID Murmur User ID
     * @param int $vServerID Murmur server ID
     * @return bool Did user get unregistered successfully?
     */
    function unregisterUser($murmurUserID, $vServerID = NULL) {
        if (!isset($vServerID)) {
            $vServerID = $this->config->item('vServerID');
        }
        log_message('debug', '<' . __FUNCTION__ . '> Unregistering ID: ' . $murmurUserID);
        try {
            $this->server = $this->meta->getServer($vServerID);
            $this->server->unregisterUser($murmurUserID);
        } catch (Murmur_MurmurException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Murmur: ' . $exc->ice_name());
            $this->errorMessage = $exc->ice_name();
            return FALSE;
        }
        return TRUE;
    }

    /**
     * registerUser - Register one user on Murmur
     *
     * @param array $userInfo New Murmur user info of at least UserName
     * @param int $vServerID Murmur server ID
     * @return int|bool New Murmur user ID or false on fail
     */
    function registerUser($userInfo, $vServerID = NULL) {
        $murmurUserID = FALSE;
        if (!isset($vServerID)) {
            $vServerID = $this->config->item('vServerID');
        }
        log_message('debug', '<' . __FUNCTION__ . '> Registering: ' . $userInfo['UserName']);
        try {
            $this->server = $this->meta->getServer($vServerID);
            $murmurUserID = $this->server->registerUser($userInfo);
        } catch (Murmur_MurmurException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Murmur: ' . $exc->ice_name());
            $this->errorMessage = $exc->ice_name();
            return FALSE;
        }
        return $murmurUserID;
    }

}

?>
