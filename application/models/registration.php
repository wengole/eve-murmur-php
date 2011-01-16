<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Registration extends Model {

    var $unameArray = array();
    var $selectedUser;
    var $userID;
    var $apiKey;
    var $username;
    var $password;
    var $password2;
    var $host;
    var $port;
    var $errorMessage;
    var $blues;
    var $server;
    var $successMessage;

    function Registration() {
        parent::Model();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
        $this->server = $meta->getServer($this->config->item('vServerID'));
        $params = array('userid' => '123456', 'key' => 'abc123');
        $this->load->library('pheal/Pheal', $params);
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

    public function registerUser() {
        try {
            $regUsers = $this->server->getRegisteredUsers('');
            foreach ($regUsers as $userID => $username) {
                if (preg_match('/.*' . $this->getUsername() . '/', $username))
                    throw new Murmur_InvalidUserException;
            }
            $userinfo = array($this->getUsername(), null, null, null, $this->getPassword());
            $murmurUserID = $this->server->registerUser($userinfo);
        } catch (Murmur_ServerBootedException $exc) {
            $this->setErrorMessage("<h4>Server not running.</h4>");
        } catch (Murmur_InvalidSecretException $exc) {
            $this->setErrorMessage("<h4>Wrong ICE secret.</h4>");
        } catch (Murmur_InvalidUserException $exc) {
            $this->setErrorMessage("<h4>User: $this->username already exists or is invalid</h4>");
        }
        if (isset($murmurUserID)) {
            $params = array('userid' => $this->userID, 'key' => $this->apiKey);
            $pheal = new Pheal($params);
            $charID = $pheal->CharacterID(array('names' => $this->username));
            $charID = $charID->characters[0]['characterID'];
            $pheal->scope = "char";
            $charsheet = $pheal->CharacterSheet(array('characterID' => $charID));
            $pheal->scope = "corp";
            $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
            $corpID = $corpsheet->corporationID;
            $data = array(
                'murmurUserID' => $murmurUserID,
                'userID' => $this->userID,
                'characterID' => $charID
            );
            $this->db->delete('utilMurmur', array('characterID' => $charID));
            $this->db->insert('utilMurmur', $data);
            $data = array(
                'activeAPI' => 'Characters AccountBalance AssetList CharacterSheet ContactList ContactNotifications IndustryJobs KillLog MailingLists MailMessages MarketOrders Notifications Research SkillInTraining SkillQueue Standings WalletJournal WalletTransactions',
                'isActive' => '1',
                'limitedApiKey' => $this->apiKey,
                'userID' => $this->userID
            );
            $this->db->delete('utilRegisteredUser', array('userID' => $this->userID));
            $this->db->insert('utilRegisteredUser', $data);
            $data = array(
                'activeAPI' => 'AccountBalance AssetList CharacterSheet ContactList ContactNotifications IndustryJobs KillLog MailingLists MailMessages MarketOrders Notifications Research SkillInTraining SkillQueue Standings WalletJournal WalletTransactions',
                'isActive' => '1',
                'characterID' => $charID,
                'corporationID' => $corpID,
                'userID' => $this->userID
            );
            $this->db->delete('utilRegisteredCharacter', array('characterID' => $charID));
            $this->db->insert('utilRegisteredCharacter', $data);
            $data = array(
                'activeAPI' => 'CorporationSheet',
                'isActive' => '1',
                'characterID' => $charID,
                'corporationID' => $corpID,
            );
            $this->db->get_where('utilRegisteredCorporation', array('corporationID' => $corpID));
            if ($this->db->count_all_results() > 0) {
                $this->db->delete('utilRegisteredCorporation', array('corporationID' => $corpID, 'isActive' => '0'));
                if ($this->db->affected_rows() > 0)
                    $this->db->insert('utilRegisteredCorporation', $data);
            } else
                $this->db->insert('utilRegisteredCorporation', $data);
            $this->server->getACL(0, $acls, $groups, $inherit);
            foreach ($groups as $group) {
                if ($group->name == "blues" && $corpsheet->allianceID != $this->config->item('allianceID')) {
                    $this->setSuccessMessage('<p>You have been added as a blue</p>');
                    $add = $group->members;
                    $add[] = $murmurUserID;
                    $group->add = $add;
                    $this->server->setACL(0, $acls, $groups, $inherit);
                    continue;
                } elseif ($group->name == "ceos" && $corpsheet->ceoID == $charID && $corpsheet->allianceID == $this->config->item('allianceID')) {
                    $this->setSuccessMessage('<p>You have been added as an admin</p>');
                    $add = $group->members;
                    $add[] = $murmurUserID;
                    $group->add = $add;
                    $this->server->setACL(0, $acls, $groups, $inherit);
                    continue;
                }
            }
        }
    }

    public function populateCharacters() {
        if (preg_match('/^[0-9]*\z/', $this->userID)) {
            $params = array('userid' => $this->userID, 'key' => $this->apiKey);
            $this->pheal = new Pheal($params);
        }
        spl_autoload_register("Pheal::classload");
        PhealConfig::getInstance()->cache = new PhealFileCache($this->config->item('phealCache'));
        PhealConfig::getInstance()->log = new PhealFileLog($this->config->item('phealLog'));
        // On API errors switch to using cache files only
        try {
            $this->pheal->scope = 'account';
            $characters = $this->pheal->Characters();
        } catch (PhealException $exc) {
            PhealConfig::getInstance()->cache = new PhealFileCacheForced($this->config->item('phealCache'));
            try {
                $characters = $this->pheal->Characters();
            } catch (PhealException $exc) {
                $this->setErrorMessage("<h4>You must provide a valid userID and API Key</h4>\n
                    <h4>User ID is a 6+ digit number<br />API Key is a 64 character string</h4>");
            }
        }

        if (isset($characters)) {
            foreach ($characters->characters as $character) {
                $this->pheal->scope = "char";
                try {
                    $charsheet = $this->pheal->CharacterSheet(array('characterID' => $character->characterID));
                } catch (PhealException $exc) {
                    PhealConfig::getInstance()->cache = new PhealFileCacheForced($this->config->item('phealCache'));
                    $characters = $this->pheal->Characters();
                }
                $this->pheal->scope = "corp";
                try {
                    $corpsheet = $this->pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                } catch (PhealException $exc) {
                    PhealConfig::getInstance()->cache = new PhealFileCacheForced($this->config->item('phealCache'));
                    $corpsheet = $this->pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
                }
                switch ($this->config->item('corpOnly')) {
                    case true:
                        if ($corpsheet->corporationID == $this->config->item('corpID') ||
                                in_array($corpsheet->corporationID, $this->blues) || in_array($corpsheet->allianceID, $this->blues))
                            $this->addUsername($character->name);
                        break;
                    default:
                        if ($corpsheet->allianceID == $this->config->item('allianceID') ||
                                in_array($corpsheet->corporationID, $this->blues) || in_array($corpsheet->allianceID, $this->blues))
                            $this->addUsername($character->name);
                        break;
                }
            }
        }
    }

    public function getSelectedUser() {
        if (isset($this->selectedUser) && isset($this->unameArray)) {
            return $this->unameArray[$this->selectedUser];
        } else {
            return NULL;
        }
    }

    public function setSelectedUser($selectedUser) {
        $this->selectedUser = $selectedUser;
    }

    public function addUsername($username) {
        $this->unameArray[] = $username;
    }

    public function getUnameArray() {
        return $this->unameArray;
    }

    public function getUserID() {
        return $this->userID;
    }

    public function setUserID($userID) {
        $this->userID = $userID;
    }

    public function getApiKey() {
        return $this->apiKey;
    }

    public function setApiKey($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function getUsername() {
        return $this->username;
    }

    public function setUsername($username) {
        $this->username = $username;
    }

    public function getPassword() {
        return $this->password;
    }

    public function setPassword($password) {
        $this->password = $password;
    }

    public function getPassword2() {
        return $this->password2;
    }

    public function setPassword2($password2) {
        $this->password2 = $password2;
    }

    public function getHost() {
        return $this->server->getConf('host');
    }

    public function getPort() {
        return $this->server->getConf('port');
    }

    public function getErrorMessage() {
        return $this->errorMessage;
    }

    public function setErrorMessage($errorMessage) {
        $this->errorMessage = $errorMessage;
    }

    public function getSuccessMessage() {
        return $this->successMessage;
    }

    public function setSuccessMessage($successMessage) {
        $this->successMessage = $successMessage;
    }

    public function getBlues() {
        return $this->blues;
    }

}

?>
