<?php

/**
 * Pheal_model - Handles data retrieval and processing of EvE API
 * @author Ben Cole <wengole@gmail.com>
 * @property Murmur_model $Murmur_model
 */
class Pheal_model extends CI_Model {

    var $blues;
    var $errorMessage;

    function __construct() {
        parent::__construct();
        $this->load->library('pheal/Pheal');
        spl_autoload_register('Pheal::classload');
        PhealConfig::getInstance()->cache = new PhealFileCache($this->config->item('phealCache'));
        PhealConfig::getInstance()->log = new PhealFileLog($this->config->item('phealLog'));
    }

    /**
     * getCharacters - Retrieves list of characters on account that are allowed to register
     *
     * @param int $userID EVE User ID for API
     * @param String $apiKey EVE API Key
     * @return Array Array of characters that are allowed to register (blue)
     */
    function getCharacters($userID = NULL, $apiKey = NULL) {
        $params = array('userid' => $userID, 'key' => $apiKey, 'scope' => 'account');
        $pheal = new Pheal($params);
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->Characters(): ' . $userID . ':' . $apiKey);
            $result = $pheal->accountScope->Characters();
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $characters = array();
        foreach ($result->characters as $character) {
            log_message('info', '<' . __FUNCTION__ . '> ' . $character->characterID . ' : ' . $character->name);
            if ($this->isBlue($character->characterID)) {
                log_message('info', '<' . __FUNCTION__ . '> ...is blue');
                $characters[] = array('charid' => (int) $character->characterID, 'name' => (string) $character->name);
            } else {
                log_message('info', '<' . __FUNCTION__ . '> ...is not blue');
            }
        }
        log_message('info', '<' . __FUNCTION__ . '> Returning characters');
        return $characters;
    }

    /**
     * updateBlues - Update the stored list of contacts in the DB from API
     *
     * @return bool Did update complete sucessfully?
     */
    function updateBlues() {
        $this->db->order_by('timestamp', 'DESC');
        $query = $this->db->get_where('actionLog', array('action' => 'Updated blues'));
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $lastCheck = new DateTime($row->timestamp);
            $date = new DateTime();
            $hour_ago = $date->sub(new DateInterval('PT1H'));
            if ($lastCheck > $hour_ago)
                return TRUE;
        }
        log_message('info', '<' . __FUNCTION__ . '> Updating contacts');
        $params = array('userid' => $this->config->item('blueUserID'), 'key' => $this->config->item('blueApiKey'));
        $pheal = new Pheal($params);
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->ContactList(): ' . $this->config->item('blueCharID'));
            $result = $pheal->corpScope->ContactList(array('characterID' => $this->config->item('blueCharID')));
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $contacts = array();
        if ($this->config->item('corpOnly')) {
            foreach ($result->corporateContactList as $contact) {
                $contacts[] = $contact;
            }
        } else {
            foreach ($result->allianceContactList as $contact) {
                $contacts[] = $contact;
            }
        }
        if (!empty($contacts)) {
            $this->db->trans_start();
            $this->db->delete('contact', array('contactID >' => 0));
            log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
            $mysqlError = mysql_error();
            if ($mysqlError != "")
                log_message('error', '<' . __FUNCTION__ . '> ' . $mysqlError);
            foreach ($contacts as $contact) {
                $mysqlError = "";
                $this->db->insert('contact', $contact);
                log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
                $mysqlError = mysql_error();
                if ($mysqlError != "")
                    log_message('error', '<' . __FUNCTION__ . '> ' . $mysqlError);
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                log_message('error', '<' . __FUNCTION__ . '> ' . $this->errorMessage . ': ' . mysql_error());
                return FALSE;
            }
        }
        log_message('info', '<' . __FUNCTION__ . '> Updating DB action log');
        $this->db->trans_start();
        $this->db->insert('actionLog', array('action' => 'Updated blues'));
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE)
            log_message('error', '<' . __FUNCTION__ . '> Failed to update action log');
        return TRUE;
    }

    /**
     * isBlue - Determine if a given EvE character is blue from it's ID
     *
     * @param int $charID ID of character to check
     * @return bool Is the character blue? NULL on API error 
     */
    function isBlue($charID) {
        if (empty($this->blues)) {
            log_message('info', '<' . __FUNCTION__ . '> Loading blues to check characters');
            $this->blues = $this->loadBlues();
        }
        $params = array('userid' => NULL, 'key' => NULL, 'scope' => 'account');
        $pheal = new Pheal($params);
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->CharacterInfo(): ' . $charID);
            $charInfo = $pheal->eveScope->CharacterInfo(array('characterID' => $charID));
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return NULL;
        }
        if (in_array($charID, $this->blues) || in_array($charInfo->corporationID, $this->blues)
                || in_array($charInfo->allianceID, $this->blues) || $charInfo->corporationID == $this->config->item('corpID')
                || $charInfo->allianceID == $this->config->item('allianceID')) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * loadBlues - Get list of blues fom database and store in local variable
     *
     * @return Array Array of contactIDs where standing is > 0
     */
    function loadBlues() {
        log_message('info', '<' . __FUNCTION__ . '> Loading blues to array');
        $blues = array();
        $this->db->select('contactID')->where('standing >', 0);
        $query = $this->db->get('contact');
        log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
        foreach ($query->result_array() as $blue) {
            $blues[] = $blue['contactID'];
        }
        return $blues;
    }

    /**
     * updateUserDetails - Updates the eveUser table with as much data as possible from the API
     * 
     * @param int $murmurUserID Murmur User ID
     * @param int $eveCharID EVE API User ID
     * @return bool Did the update succeed?
     */
    function updateUserDetails($murmurUserID = NULL, $eveCharID = NULL) {
        if (isset($murmurUserID) && !isset($eveCharID)) {
            log_message('info', '<' . __FUNCTION__ . '> Getting characterID from DB for ' . $murmurUserID);
            $this->db->select('eveCharID')->from('eveUser')->where('murmurUserID', $murmurUserID);
            $query = $this->db->get();
            $row = $query->row();
            if ($query->num_rows() < 1) {
                log_message('error', '<' . __FUNCTION__ . '> Murmur User ' . $murmurUserID . ' not in DB');
                $userInfo = $this->Murmur_model->getUserInfo($murmurUserID);
                if (preg_match('/\s(\[.+\]|\<.+\>\[.+\])$/', $userInfo['username']) > 0) {
                    log_message('debug', '<' . __FUNCTION__ . '> Username has ticker');
                    preg_match_all('/.+(?=\s(\[|\<))/', $userInfo['username'], $matches);
                    $charName = $matches[0][0];
                } else {
                    log_message('debug', '<' . __FUNCTION__ . '> Username doesn\'t have ticker');
                    $charName = $userInfo['username'];
                }
                $eveCharID = $this->lookupCharID($charName);
                if (!$eveCharID) {
                    log_message('error', '<' . __FUNCTION__ . '> Unable to lookup character ID');
                    return FALSE;
                }
            } else {
                $eveCharID = $row->eveCharID;
            }
        } elseif (!isset($murmurUserID) && !isset($eveCharID)) {
            log_message('error', '<' . __FUNCTION__ . '> Missing parameter for updateUserDetails');
            return FALSE;
        }
        $pheal = new Pheal();
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->CharacterInfo(): ' . $eveCharID);
            $charInfo = $pheal->eveScope->CharacterInfo(array('characterID' => $eveCharID));
            log_message('info', '<' . __FUNCTION__ . '> Pheal->CorporationSheet(): ' . $charInfo->corporationID);
            $corpSheet = $pheal->corpScope->CorporationSheet(array('corporationID' => $charInfo->corporationID));
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            $update = array(
                'apiLastChecked' => date('Y-m-d H:i:s'),
                'apiLastCode' => $exc->code,
                'apiLastMessage' => $exc->getMessage()
            );
            $this->db->trans_start();
            $this->db->where('murmurUserID', $murmurUserID);
            $this->db->update('eveUser', $update);
            $this->db->trans_complete();
            log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
            $errMsg = mysql_error();
            if ($this->db->trans_status() === FALSE || !empty($errMsg))
                log_message('error', '<' . __FUNCTION__ . '> Failed to update DB: ' . mysql_error());
            return FALSE;
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $update = array(
            'eveCharName' => $charInfo->characterName,
            'eveCorpID' => $charInfo->corporationID,
            'eveCorpName' => $charInfo->corporation,
            'eveCorpTicker' => $corpSheet->ticker,
            'eveAllyID' => $charInfo->allianceID,
            'eveAllyName' => $charInfo->alliance,
            'apiLastChecked' => date('Y-m-d H:i:s')
        );
        $this->updateAllianceList();
        $query = $this->db->get_where('eveAlliance', array('allianceID' => $charInfo->allianceID));
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $update['eveAllyTicker'] = $row->shortName;
        }
        log_message('info', '<' . __FUNCTION__ . '> Updating DB for ' . $murmurUserID);
        $query = $this->db->get_where('eveUser', array('murmurUserID' => $murmurUserID));
        if ($query->num_rows > 0) {
            $this->db->trans_start();
            $this->db->where('murmurUserID', $murmurUserID);
            $this->db->update('eveUser', $update);
            $this->db->trans_complete();
            log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
            $errMsg = mysql_error();
        }
        if ($this->db->trans_status() === FALSE || !empty($errMsg) || $query->num_rows < 1) {
            log_message('error', '<' . __FUNCTION__ . '> Failed to update DB, trying INSERT: ' . mysql_error());
            $update['murmurUserID'] = $murmurUserID;
            $update['eveCharID'] = $eveCharID;
            $this->db->insert('eveUser', $update);
            log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
            if ($this->db->trans_status() === FALSE) {
                log_message('error', '<' . __FUNCTION__ . '> Failed to insert into DB: ' . mysql_error());
                return FALSE;
            }
        }
        log_message('debug', '<' . __FUNCTION__ . '> Successfully updated DB for ' . $murmurUserID);
        return TRUE;
    }

    /**
     * lookupCharID - Fetch character ID from API when all we have is a name
     *
     * @param String $charName Character Name
     * @return int|bool Character ID or FALSE on error
     */
    function lookupCharID($charName) {
        $pheal = new Pheal();
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->CharacterID(): ' . $charName);
            $result = $pheal->eveScope->CharacterID(array('names' => $charName));
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $charID = $result->characters[0]->characterID;
        return $charID;
    }

    /**
     * lookupCharName - Fetch character name from API when all we have is an ID
     *
     * @param int $charID Character ID (apparently also works with other IDs)
     * @return int|bool Character name or FALSE on error
     */
    function lookupCharName($charID) {
        $pheal = new Pheal();
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->CharacterName(): ' . $charID);
            $result = $pheal->eveScope->CharacterInfo(array('characterID' => $charID));
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $charID = $result->characterName;
        return $charID;
    }

    /**
     * updateAllianceList - Retrieve list of alliances from CCP and store in DB for faster lookups
     *
     * @return bool Update successful?
     */
    function updateAllianceList() {
        $this->db->order_by('timestamp', 'DESC');
        $query = $this->db->get_where('actionLog', array('action' => 'Updated alliance list'));
        if ($query->num_rows() > 0) {
            $row = $query->row();
            $lastCheck = new DateTime($row->timestamp);
            $date = new DateTime();
            $hour_ago = $date->sub(new DateInterval('PT1H'));
            if ($lastCheck > $hour_ago)
                return TRUE;
        }
        $pheal = new Pheal();
        try {
            log_message('info', '<' . __FUNCTION__ . '> Pheal->AllianceList()');
            $alliances = $pheal->eveScope->AllianceList();
        } catch (PhealAPIException $exc) {
            log_message('error', '<' . __FUNCTION__ . '> Pheal: ' . $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $alliances = $alliances->alliances;
        $this->db->trans_start();
        $this->db->truncate('eveAlliance');
        log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
        foreach ($alliances as $alliance) {
            $insert = array(
                'allianceID' => $alliance->allianceID,
                'name' => $alliance->name,
                'shortName' => $alliance->shortName,
                'executorCorpID' => $alliance->executorCorpID,
                'memberCount' => $alliance->memberCount,
                'startDate' => $alliance->startDate
            );
            $this->db->insert('eveAlliance', $insert);
            log_message('info', '<' . __FUNCTION__ . '> ' . $this->db->last_query());
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            log_message('error', '<' . __FUNCTION__ . '> Failed to update alliance list: ' . mysql_error());
            return FALSE;
        } else {
            log_message('debug', '<' . __FUNCTION__ . '> Sucessfully updated alliance list');
            log_message('info', '<' . __FUNCTION__ . '> Updating DB action log');
            $this->db->trans_start();
            $this->db->insert('actionLog', array('action' => 'Updated alliance list'));
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE)
                log_message('error', '<' . __FUNCTION__ . '> Failed to update action log');
            return TRUE;
        }
    }

}

?>