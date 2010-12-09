<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

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
            $this->setErrorMessage("><h4>Username already exists</h4>");
        }
        if (isset($murmurUserID)) {
            $charid = $pheal->CharacterID(array('names' => $_POST['username']));
            $charid = $charid->characters[0]['characterID'];
            $pheal->scope = "char";
            $charsheet = $pheal->CharacterSheet(array('characterID' => $charid));
            $pheal->scope = "corp";
            $corpsheet = $pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
            $corpID = $corpsheet->corporationID;
            $qry = "INSERT INTO utilMurmur (`murmurUserID`, `userID`, `characterID`)
                        VALUES (" . $murmur_userid . "," . $_POST['userid'] . "," . $charid . ")
			ON DUPLICATE KEY UPDATE characterID = $charid, userID = " . $_POST['userid'];
            if (!mysql_query($qry, $conn))
                echo "<h4>Failed to INSERT into utilMurmur.<br />
                          $qry</h4>";
            $qry = "INSERT INTO utilRegisteredUser (`activeAPI`, `isActive`, `limitedApiKey`, `userID`)
                        VALUES ('Characters AccountBalance AssetList CharacterSheet ContactList ContactNotifications IndustryJobs KillLog MailingLists MailMessages MarketOrders Notifications Research SkillInTraining SkillQueue Standings WalletJournal WalletTransactions',
                        1, '" . $_POST['apikey'] . "'," . $_POST['userid'] . ")
			ON DUPLICATE KEY UPDATE limitedApiKey = '" . $_POST['apikey'] . "', userID = " . $_POST['userid'] . ", isActive = 1";
            if (!mysql_query($qry, $conn))
                echo "<h4>Failed to INSERT into utilRegisteredUser.<br />
                          $qry</h4>";
            $qry = "INSERT INTO utilRegisteredCharacter (`activeAPI`, `characterID`, `corporationID`, `isActive`, `userID`)
                        VALUES ('CharacterSheet', $charid, $corpID, 1, " . $_POST['userid'] . ")
			ON DUPLICATE KEY UPDATE characterID = $charid, corporationID = $corpID, userID = " . $_POST['userid'] . ", isActive = 1";
            if (!mysql_query($qry, $conn))
                echo "<h4>Failed to INSERT into utilRegisteredCharacter.<br />
                          $qry</h4>";
            $qry = "INSERT INTO utilRegisteredCorporation (`activeAPI`, `characterID`, `corporationID`, `isActive`)
                        VALUES ('CorporationSheet', $charid, $corpID, 1)
			ON DUPLICATE KEY UPDATE characterID = $charid, corporationID = $corpID, isActive = 1";
            if (!mysql_query($qry, $conn))
                echo "<h4>Failed to INSERT into utilRegisteredCorporation.<br />
                          $qry</h4>";
            $server->getACL(0, $acls, $groups, $inherit);
            foreach ($groups as $group) {
                if ($group->name == "blues" && $corpsheet->allianceID != $allianceID) {
                    echo "<p>You have been added as a blue</p>";
                    $add = $group->members;
                    $add[] = $murmur_userid;
                    $group->add = $add;
                    $server->setACL(0, $acls, $groups, $inherit);
                    continue;
                } elseif ($group->name == "ceos" && $corpsheet->ceoID == $charid && $corpsheet->allianceID == $allianceID) {
                    echo "<p>You have been added as an admin</p>";
                    $add = $group->members;
                    $add[] = $murmur_userid;
                    $group->add = $add;
                    $server->setACL(0, $acls, $groups, $inherit);
                }
            }
        }
    }

    public function populateCharacters() {
        if (preg_match('/^[0-9]*\z/', $this->userID)) {
            $params = array('userid' => $this->userID, 'key' => $this->apiKey);
            $this->load->library('pheal/Pheal', $params);
        } else {
            
        }
        spl_autoload_register("Pheal::classload");
        PhealConfig::getInstance()->cache = new PhealFileCache($this->config->item('phealCache'));
        PhealConfig::getInstance()->log = new PhealFileLog($this->config->item('phealLog'));
        // On API errors switch to using cache files only
        try {
            $this->pheal->scope = 'account';
            $characters = $this->pheal->Characters();
        } catch (Exception $exc) {
            PhealConfig::getInstance()->cache = new PhealFileCacheForced($this->config->item('phealCache'));
            try {
                $characters = $this->pheal->Characters();
            } catch (Exception $exc) {
                $this->setErrorMessage("<h4>You must provide a valid userID and API Key</h4>\n
                    <h4>User ID is a 6+ digit number<br />API Key is a 64 character string</h4>");
            }
        }

        if (isset($characters)) {
            foreach ($characters->characters as $character) {
                $this->pheal->scope = "char";
                $charsheet = $this->pheal->CharacterSheet(array('characterID' => $character->characterID));
                $this->pheal->scope = "corp";
                $corpsheet = $this->pheal->CorporationSheet(array('corporationID' => $charsheet->corporationID));
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
        return $this->unameArray[$this->selectedUser];
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

    public function getBlues() {
        return $this->blues;
    }
}

?>
