<?php

class User extends CI_Model {

    var $errorMessage;
    var $server;
    var $blues;

    function User() {
        parent::__construct();
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
        log_message('debug', 'Updating blues to check characters');
        //$this->updateBlues();
        //$this->blues = $this->loadBlues();
    }

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
                log_message('debug', '...is blue');
                $characters[] = array('charid' => (int) $character->characterID, 'name' => (string) $character->name);
            }
        }
        log_message('debug', 'Returning characters');
        return $characters;
    }

    /**
     * updateBlues - Updated the stored list of contacts in the DB from API
     * @return bool Did update complete sucessfully?
     */
    function updateBlues() {
        $this->errorMessage = "";
        $params = array('userid' => $this->config->item('blueUserID'), 'key' => $this->config->item('blueApiKey'), 'scope' => 'corp');
        $pheal = new Pheal($params);
        try {
            $result = $pheal->ContactList(array('characterID' => $this->config->item('blueCharID')));
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
                $this->db->delete('contacts');
                foreach ($contacts as $contact) {
                    $this->db->insert('contacts', $contact);
                }
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    $this->errorMessage = "Failed to update contacts";
                    log_message('error', $this->errorMessage.': '.$this->db->show_error());
                    return FALSE;
                }
            }
        } else {
            foreach ($result->allianceContactList as $contact) {
                $contacts[] = $contact;
            }
            if (!empty($contacts)) {
                $this->db->trans_start();
                $this->db->delete('contacts');
                foreach ($contacts as $contact) {
                    $this->db->insert('contacts', $contact);
                }
                $this->db->trans_complete();
                if ($this->db->trans_status() === FALSE) {
                    $this->errorMessage = "Failed to update contacts";
                    log_message('error', $this->errorMessage.': '.$this->db->show_error());
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

    /**
     * isBlue - Determine if a given EvE character is blue from it's ID
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
        if (in_array($charID, $this->blues) || in_array($charInfo->corporationID, $this->blues) || in_array($charInfo->allianceID, $this->blues)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * loadBlues - Get list of blues fom database and store in local variable
     * @return Array Array of contactIDs where standing is > 0
     */
    function loadBlues() {
        log_message('debug', 'Loading blues to array');
        $blues = array();
        $query = $this->db->select('contactID')->where('standing >', 0);
        foreach ($query->result_array() as $blue) {
            $blues[] = $blue['contactID'];
        }
        return $blues;
    }

}

?>