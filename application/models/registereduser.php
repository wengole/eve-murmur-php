<?php

class Registereduser extends Model {

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
    var $charName;
    var $corpID;
    var $corpName;
    var $corpTicker;
    var $allyID;
    var $allyName;
    var $allyTicker;
    var $apiIsActive;
    var $apiLastChecked;
    var $apiLastCode;
    var $apiLastMessage;
// Others
    var $server;
    var $blues;
    var $murmurUsers;
    var $dbUsers;

    function Registereduser() {
        parent::Model();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
        $this->server = $meta->getServer($this->config->item('vServerID'));
        $params = array('userid' => NULL, 'key' => NULL);
        $this->load->library('pheal/Pheal', $params);
        spl_autoload_register('Pheal::classload');
        PhealConfig::getInstance()->cache = new PhealFileCache($this->config->item('phealCache'));
        PhealConfig::getInstance()->log = new PhealFileLog($this->config->item('phealLog'));
    }

    private function retrieveRegistration($murmurUserID) {
        $reg = array();
        array_pad($reg, 6, NULL);
        try {
            $reg = $this->server->retrieveRegistration($murmurUserID);
        } catch (Murmur_InvalidUserException $exc) {
            // TODO: Log exception with trace and possibly display message
        }
        $this->setUsername($reg[0]);
        $this->setUserEmail($reg[1]);
        $this->setUserComment($reg[2]);
        $this->setUserHash($reg[3]);
        $this->setUserPassword($reg[4]);
        $this->setUserLastActive($reg[5]);
    }

    private function retrieveUserFromDB($murmurUserID) {
        $query = $this->db->get_where('eveUser', array('murmurUserID' => $murmurUserID));
        if ($query->num_rows() == 1) {
            foreach ($query->result_array() as $row) {
                $this->setEveUserID($row['eveUserID']);
                $this->setApiKey($row['eveApiKey']);
                $this->setCharID($row['eveCharID']);
                $this->setCharName($row['eveCharName']);
                $this->setCorpID($row['eveCorpID']);
                $this->setCorpName($row['eveCorpName']);
                $this->setCorpTicker($row['eveCorpTicker']);
                $this->setAllyID($row['eveAllyID']);
                $this->setAllyName($row['eveAllyName']);
                $this->setAllyTicker($row['eveAllyTicker']);
                $this->setApiIsActive($row['apiIsActive']);
                $this->setApiLastChecked($row['apiLastChecked']);
                $this->setApiLastCode($row['apiLastCode']);
                $this->setApiLastMessage($row['apiLastMessage']);
            }
        } elseif ($query->num_rows == 0 && isset($this->eveUserID) && isset($this->apiKey) && isset ($this->charID)) {
            $this->retrieveCharacterInfo();
        } else {
            // TODO: Log error and possibly display message
            // TODO: Clean up extraneous entries
        }
    }

    public function retrieveCharactersOnAccount() {
        $characters = array();
        $params = array('userid' => $this->getEveUserID(), 'key' => $this->getApiKey(), 'scope' => 'account');
        $pheal = new Pheal($params);
        $result = $pheal->Characters();
        foreach ($result as $character) {
            $characters[]['charid'] = $character->characterID;
            $characters[]['name'] = $character->name;
            $characters[]['corpid'] = $character->corporationID;
            $characters[]['corpname'] = $character->corporationName;
        }
        return $characters;
    }
    
    public function retrieveCharacterInfo() {
        $params = array('userid' => NULL, 'key' => NULL, 'scope' => 'eve');
        $pheal = new Pheal($params);
        $result = $pheal->CharacterInfo(array('characterID' => $this->charID));
        $this->setCharName($result->characterName);
        $this->setCorpID($result->corporationID);
        $this->setCorpName($result->corporation);
        $this->setAllyID($result->allianceID);
        $this->setAllyName($result->alliance);
    }
    
    public function retrieveCorporationSheet() {
        $params = array('userid' => NULL, 'key' => NULL, 'scope' => 'corp');
        $pheal = new Pheal($params);
        $result = $pheal->CorporationSheet(array('corporationID' => $this->corpID));
    }

    public function getUsername() {
        if (!isset($this->username)) {
            $this->retrieveRegistration($this->getMurmurUserID());
        }
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getUserEmail() {
        if (!isset($this->userEmail)) {
            $this->retrieveRegistration($this->getMurmurUserID());
        }
        return $this->userEmail;
    }

    public function setUserEmail($userEmail) {
        $this->userEmail = $userEmail;
    }

    public function getUserComment() {
        if (!isset($this->userComment)) {
            $this->retrieveRegistration($this->getMurmurUserID());
        }
        return $this->userComment;
    }

    public function setUserComment($userComment) {
        $this->userComment = $userComment;
    }

    public function getUserHash() {
        if (!isset($this->userHash)) {
            $this->retrieveRegistration($this->getMurmurUserID());
        }
        return $this->userHash;
    }

    public function setUserHash($userHash) {
        $this->userHash = $userHash;
    }

    public function getUserPassword() {
        if (!isset($this->userPassword)) {
            $this->retrieveRegistration($this->getMurmurUserID());
        }
        return $this->userPassword;
    }

    public function setUserPassword($userPassword) {
        $this->userPassword = $userPassword;
    }

    public function getUserLastActive() {
        if (!isset($this->userLastActive)) {
            $this->retrieveRegistration($this->getMurmurUserID());
        }
        return $this->userLastActive;
    }

    public function setUserLastActive($userLastActive) {
        $this->userLastActive = $userLastActive;
    }

    public function getMurmurUserID() {
        if (!isset($this->murmurUserID)) {
            return -1;
        }
        return $this->murmurUserID;
    }

    public function setMurmurUserID($murmurUserID) {
        $this->murmurUserID = $murmurUserID;
    }

    public function getEveUserID() {
        if (!isset($this->eveUserID)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->eveUserID;
    }

    public function setEveUserID($eveUserID) {
        $this->eveUserID = $eveUserID;
    }

    public function getApiKey() {
        if (!isset($this->apiKey)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->apiKey;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getCharID() {
        if (!isset($this->charID)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->charID;
    }

    public function setCharID($charID) {
        $this->charID = $charID;
    }

    public function getCharName() {
        if (!isset($this->charName)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->charName;
    }

    public function setCharName($charName) {
        $this->charName = $charName;
    }

    public function getCorpID() {
        if (!isset($this->corpID)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->corpID;
    }

    public function setCorpID($corpID) {
        $this->corpID = $corpID;
    }

    public function getCorpName() {
        if (!isset($this->corpName)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->corpName;
    }

    public function setCorpName($corpName) {
        $this->corpName = $corpName;
    }

    public function getCorpTicker() {
        if (!isset($this->corpTicker)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->corpTicker;
    }

    public function setCorpTicker($corpTicker) {
        $this->corpTicker = $corpTicker;
    }

    public function getAllyID() {
        if (!isset($this->allyID)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->allyID;
    }

    public function setAllyID($allyID) {
        $this->allyID = $allyID;
    }

    public function getAllyName() {
        if (!isset($this->allyName)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->allyName;
    }

    public function setAllyName($allyName) {
        $this->allyName = $allyName;
    }

    public function getAllyTicker() {
        if (!isset($this->allyTicker)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->allyTicker;
    }

    public function setAllyTicker($allyTicker) {
        $this->allyTicker = $allyTicker;
    }

    public function getApiIsActive() {
        if (!isset($this->apiIsActive)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->apiIsActive;
    }

    public function setApiIsActive($apiIsActive) {
        $this->apiIsActive = $apiIsActive;
    }

    public function getApiLastChecked() {
        if (!isset($this->apiLastChecked)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->apiLastChecked;
    }

    public function setApiLastChecked($apiLastChecked) {
        $this->apiLastChecked = $apiLastChecked;
    }

    public function getApiLastCode() {
        if (!isset($this->apiLastCode)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->apiLastCode;
    }

    public function setApiLastCode($apiLastCode) {
        $this->apiLastCode = $apiLastCode;
    }

    public function getApiLastMessage() {
        if (!isset($this->apiLastMessage)) {
            $this->retrieveUserFromDB($this->getMurmurUserID());
        }
        return $this->apiLastMessage;
    }

    public function setApiLastMessage($apiLastMessage) {
        $this->apiLastMessage = $apiLastMessage;
    }

    public function getServer() {
        return $this->server;
    }

    public function setServer($server) {
        $this->server = $server;
    }

    public function getBlues() {
        return $this->blues;
    }

    public function setBlues($blues) {
        $this->blues = $blues;
    }

    public function getMurmurUsers() {
        return $this->murmurUsers;
    }

    public function setMurmurUsers($murmurUsers) {
        $this->murmurUsers = $murmurUsers;
    }

    public function getDbUsers() {
        return $this->dbUsers;
    }

    public function setDbUsers($dbUsers) {
        $this->dbUsers = $dbUsers;
    }

}

?>