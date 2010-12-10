<?php

class User extends Model {

    // Mumble registered user data
    var $username;
    var $userEmail;
    var $userComment;
    var $userHash;
    var $userPassword;
    var $userLastActive;
    var $murmurUserID;
    // Eve registered user data
    var $eveUserID;
    var $apiKey;
    var $charID;
    var $corpID;
    var $allyID;
    // Mumble online user data
    var $session;
    var $mute;
    var $deaf;
    var $suppress;
    var $prioritySpeaker;
    var $selfMute;
    var $selfDeaf;
    var $channel;
    var $onlineSecs;
    var $bytesPerSec;
    var $version;
    var $release;
    var $os;
    var $osVersion;
    var $identity;
    var $context;
    var $address;
    var $tcpOnly;
    var $idleSecs;
    // Others
    var $server;
    var $blues;
    var $murmurUsers;
    var $dbUsers;

    function User() {
        parent::Model();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
        $this->server = $meta->getServer($this->config->item('vServerID'));
        $params = array('userid' => '123456', 'key' => 'abc123');
        $this->load->library('pheal/Pheal', $params);
    }

    public function setBlues() {
        $this->db->select('corpAllianceContactList.contactID');
        $this->db->from('corpAllianceContactList');
        $this->db->join('corpCorporationSheet', 'corpCorporationSheet.corporationID = corpAllianceContactList.ownerID');
        $this->db->where('corpCorporationSheet.allianceID', $this->config->item('allianceID'));
        $this->db->where('standing >', 0);
        $query = $this->db->get();
        $this->blues = array();
        foreach ($query->result_array() as $row) {
            $this->blues[] = $row['contactID'];
        }
    }

    public function getBlues() {
        if (empty($this->blues))
            $this->setBlues();
        return $this->blues;
    }

    public function getMurmurUsers() {
        if (empty($this->murmurUsers))
            $this->setMurmurUsers();
        return $this->murmurUsers;
    }

    public function setMurmurUserInfo($murmurUserID = NULL) {
        if (isset($murmurUserID)) {
            $userInfo = $this->server->getRegistration($murmurUserID);
            $this->username = $userInfo[0];
            if (isset($userInfo[1]))
                $this->userEmail = $userInfo[1];
            if (isset($userInfo[2]))
                $this->userComment = $userInfo[2];
            if (isset($userInfo[3]))
                $this->userHash = $userInfo[3];
            if (isset($userInfo[4]))
                $this->userPassword = $userInfo[4];
            if (isset($userInfo[5]))
                $this->userLastActive = $userInfo[5];
            $this->murmurUserID = $murmurUserID;
        } else {
            unset($this->username);
            unset($this->userEmail);
            unset($this->userComment);
            unset($this->userHash);
            unset($this->userPassword);
            unset($this->userLastActive);
            unset($this->murmurUserID);
        }
    }

    public function applyChanges() {
        $userInfo = array(
            $this->username,
            $this->userEmail,
            $this->userComment,
            $this->userHash
        );
        $this->server->updateRegistration($this->murmurUserID, $userInfo);
    }

    public function getMurmurUserIDs() {
        $murmurUserIDs = array();
        foreach ($this->murmurUsers as $id => $username) {
            $murmurUserIDs[] = $id;
        }
        return $murmurUserIDs;
    }

    public function getDbUsers() {
        if (empty($this->dbUsers))
            $this->setDbUsers();
        return $this->dbUsers;
    }

    public function getDbUser($murmurUserID) {
        $this->db->select('murmurUserID, accountCharacters.characterID, name, ticker, allianceID, accountCharacters.corporationID');
        $this->db->from('utilMurmur');
        $this->db->join('accountCharacters', 'utilMurmur.characterID = accountCharacters.characterID');
        $this->db->join('corpCorporationSheet', 'accountCharacters.corporationID = corpCorporationSheet.corporationID');
        $this->db->where('murmurUserID', $murmurUserID);
        $query = $this->db->get();
        $dbUser = $query->result_array();
        if (empty ($dbUser))
            return FALSE;
        return $dbUser[0];
    }

    public function setMurmurUsers() {
        $this->murmurUsers = $this->server->getRegisteredUsers('');
    }

    public function setDbUsers() {
        $this->db->select('murmurUserID, accountCharacters.characterID, name, ticker, allianceID, accountCharacters.corporationID');
        $this->db->from('utilMurmur');
        $this->db->join('accountCharacters', 'utilMurmur.characterID = accountCharacters.characterID');
        $this->db->join('corpCorporationSheet', 'accountCharacters.corporationID = corpCorporationSheet.corporationID');
        $query = $this->db->get();
        $this->dbUsers = $query->result_array();
    }

    public function removeFromDB($murmurUserID) {
        $ret = $this->db->delete('utilMurmur', array('murmurUserID' => $murmurUserID));
        if (!$ret)
            return FALSE;
        return $ret;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getUserEmail() {
        return $this->userEmail;
    }

    public function setUserEmail($userEmail) {
        $this->userEmail = $userEmail;
    }

    public function getUserComment() {
        return $this->userComment;
    }

    public function setUserComment($userComment) {
        $this->userComment = $userComment;
    }

    public function getUserHash() {
        return $this->userHash;
    }

    public function setUserHash($userHash) {
        $this->userHash = $userHash;
    }

    public function getUserPassword() {
        return $this->userPassword;
    }

    public function setUserPassword($userPassword) {
        $this->userPassword = $userPassword;
    }

    public function getUserLastActive() {
        return $this->userLastActive;
    }

    public function setUserLastActive($userLastActive) {
        $this->userLastActive = $userLastActive;
    }

    public function getMurmurUserID() {
        return $this->murmurUserID;
    }

    public function setMurmurUserID($murmurUserID) {
        $this->murmurUserID = $murmurUserID;
    }

    public function getEveUserID() {
        return $this->eveUserID;
    }

    public function setEveUserID($eveUserID) {
        $this->eveUserID = $eveUserID;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getCharID() {
        return $this->charID;
    }

    public function setCharID($charID) {
        $this->charID = $charID;
    }

    public function getCorpID() {
        return $this->corpID;
    }

    public function setCorpID($corpID) {
        $this->corpID = $corpID;
    }

    public function getAllyID() {
        return $this->allyID;
    }

    public function setAllyID($allyID) {
        $this->allyID = $allyID;
    }

    public function getSession() {
        return $this->session;
    }

    public function setSession($session) {
        $this->session = $session;
    }

    public function getMute() {
        return $this->mute;
    }

    public function setMute($mute) {
        $this->mute = $mute;
    }

    public function getDeaf() {
        return $this->deaf;
    }

    public function setDeaf($deaf) {
        $this->deaf = $deaf;
    }

    public function getSuppress() {
        return $this->suppress;
    }

    public function setSuppress($suppress) {
        $this->suppress = $suppress;
    }

    public function getPrioritySpeaker() {
        return $this->prioritySpeaker;
    }

    public function setPrioritySpeaker($prioritySpeaker) {
        $this->prioritySpeaker = $prioritySpeaker;
    }

    public function getSelfMute() {
        return $this->selfMute;
    }

    public function setSelfMute($selfMute) {
        $this->selfMute = $selfMute;
    }

    public function getSelfDeaf() {
        return $this->selfDeaf;
    }

    public function setSelfDeaf($selfDeaf) {
        $this->selfDeaf = $selfDeaf;
    }

    public function getChannel() {
        return $this->channel;
    }

    public function setChannel($channel) {
        $this->channel = $channel;
    }

    public function getOnlineSecs() {
        return $this->onlineSecs;
    }

    public function setOnlineSecs($onlineSecs) {
        $this->onlineSecs = $onlineSecs;
    }

    public function getBytesPerSec() {
        return $this->bytesPerSec;
    }

    public function setBytesPerSec($bytesPerSec) {
        $this->bytesPerSec = $bytesPerSec;
    }

    public function getVersion() {
        return $this->version;
    }

    public function setVersion($version) {
        $this->version = $version;
    }

    public function getRelease() {
        return $this->release;
    }

    public function setRelease($release) {
        $this->release = $release;
    }

    public function getOs() {
        return $this->os;
    }

    public function setOs($os) {
        $this->os = $os;
    }

    public function getOsVersion() {
        return $this->osVersion;
    }

    public function setOsVersion($osVersion) {
        $this->osVersion = $osVersion;
    }

    public function getIdentity() {
        return $this->identity;
    }

    public function setIdentity($identity) {
        $this->identity = $identity;
    }

    public function getContext() {
        return $this->context;
    }

    public function setContext($context) {
        $this->context = $context;
    }

    public function getAddress() {
        return $this->address;
    }

    public function setAddress($address) {
        $this->address = $address;
    }

    public function getTcpOnly() {
        return $this->tcpOnly;
    }

    public function setTcpOnly($tcpOnly) {
        $this->tcpOnly = $tcpOnly;
    }

    public function getIdleSecs() {
        return $this->idleSecs;
    }

    public function setIdleSecs($idleSecs) {
        $this->idleSecs = $idleSecs;
    }

}

?>
