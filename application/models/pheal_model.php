<?php
/**
 * Pheal_model - Handles data retrieval and processing of EvE API
 * @author Ben Cole <wengole@gmail.com>
 */
class Pheal_model extends CI_Model {

    var $errorMessage;
    var $blues;

    function __construct() {
        parent::__construct();
        $params = array('userid' => NULL, 'key' => NULL);
        $this->load->library('pheal/Pheal', $params);
        spl_autoload_register('Pheal::classload');
        PhealConfig::getInstance()->cache = new PhealFileCache($this->config->item('phealCache'));
        PhealConfig::getInstance()->log = new PhealFileLog($this->config->item('phealLog'));
        log_message('debug', 'Updating blues to check characters');
        // Don't do this every time
        // $this->updateBlues();
        $this->blues = $this->loadBlues();
    }

    /**
     * getCharacters - Retrieves list of characters on account that are allowed to register
     *
     * @param int $userID EVE User ID for API
     * @param String $apiKey EVE API Key
     * @return Array Array of characters that are allowed to register (blue)
     */
    function getCharacters($userID = NULL, $apiKey = NULL) {
        $this->errorMessage = "";
        $params = array('userid' => $userID, 'key' => $apiKey, 'scope' => 'account');
        $pheal = new Pheal($params);
        try {
            log_message('debug', 'Pheal->Characters()');
            $result = $pheal->accountScope->Characters();
        } catch (PhealAPIException $exc) {
            log_message('error', $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $characters = array();
        foreach ($result->characters as $character) {
            log_message('info', $character->characterID . ' : ' . $character->name);
            if ($this->isBlue($character->characterID)) {
                log_message('info', '...is blue');
                $characters[] = array('charid' => (int) $character->characterID, 'name' => (string) $character->name);
            } else {
                log_message('info', '...is not blue');
            }
        }
        log_message('debug', 'Returning characters');
        return array_unique($characters);
    }

    /**
     * updateBlues - Update the stored list of contacts in the DB from API
     *
     * @return bool Did update complete sucessfully?
     */
    function updateBlues() {
        $this->errorMessage = "";
        $params = array('userid' => $this->config->item('blueUserID'), 'key' => $this->config->item('blueApiKey'));
        $pheal = new Pheal($params);
        try {
            $result = $pheal->corpScope->ContactList(array('characterID' => $this->config->item('blueCharID')));
        } catch (PhealAPIException $exc) {
            log_message('error', $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $contacts = array();
        if ($this->config->item('corpOnly')) {
            foreach ($result->corporateContactList as $contact) {
                $contacts[] = $contact;
            }
            if (!empty($contacts)) {
                $this->db->trans_start();
                $this->db->delete('contact', array('contactID >' => 0));
                log_message('info', $this->db->last_query());
                $mysqlError = mysql_error();
                if ($mysqlError != "")
                    log_message('error', $mysqlError);
                foreach ($contacts as $contact) {
                    $this->db->insert('contact', $contact);
                    log_message('info', $this->db->last_query());
                    $mysqlError = mysql_error();
                    if ($mysqlError != "")
                        log_message('error', $mysqlError);
                }
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    $this->errorMessage = "Failed to update contacts";
                    log_message('error', $this->errorMessage . ': ' . $this->db->show_error());
                    return FALSE;
                }
            }
        } else {
            foreach ($result->allianceContactList as $contact) {
                $contacts[] = $contact;
            }
            if (!empty($contacts)) {
                $this->db->trans_start();
                $this->db->delete('contact', array('contactID >' => 0));
                log_message('info', $this->db->last_query());
                $mysqlError = mysql_error();
                if ($mysqlError != "")
                    log_message('error', $mysqlError);
                foreach ($contacts as $contact) {
                    $this->db->insert('contact', $contact);
                    log_message('info', $this->db->last_query());
                    $mysqlError = mysql_error();
                    if ($mysqlError != "")
                        log_message('error', $mysqlError);
                }
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    $this->errorMessage = "Failed to update contacts";
                    $mysqlError = mysql_error();
                    log_message('error', $this->errorMessage . ': ' . $mysqlError);
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * isBlue - Determine if a given EvE character is blue from it's ID
     *
     * @param int $charID ID of character to check
     * @return bool Is the character blue? NULL on API error 
     */
    function isBlue($charID) {
        $params = array('userid' => NULL, 'key' => NULL, 'scope' => 'account');
        $pheal = new Pheal($params);
        try {
            $charInfo = $pheal->eveScope->CharacterInfo(array('characterID' => $charID));
        } catch (PhealException $exc) {
            log_message('error', $exc->getMessage());
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
        log_message('debug', 'Loading blues to array');
        $blues = array();
        $this->db->select('contactID')->where('standing >', 0);
        $query = $this->db->get('contact');
        log_message('info', $this->db->last_query());
        foreach ($query->result_array() as $blue) {
            $blues[] = $blue['contactID'];
        }
        return $blues;
    }

}

?>