<?php

class User extends CI_Model {

    var $errorMessage;
    var $server;

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
    }

    function getCharacters($userID = NULL, $apiKey = NULL) {
        $this->errorMessage = "";
        $params = array('userid' => $userID, 'key' => $apiKey, 'scope' => 'account');
        $pheal = new Pheal($params);
        try {
            log_message('debug', 'Pheal->Characters()');
            $result = $pheal->Characters();
        } catch (PhealAPIException $exc) {
            log_message('error', $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $characters = array();
        foreach ($result->characters as $character) {
            $characters[] = array('charid' => (int) $character->characterID, 'name' => (string) $character->name);
            log_message('info', $character->characterID . ' : ' . $character->name);
        }
        log_message('debug','Updating blues to check characters');
        if ($this->updateBlues()) {
            log_message('debug','Loading blues to array');
            $this->db->get_where('contact',array('standing >' => 10));
        } else {
            
        }
        log_message('debug', 'Returning characters');
        return $characters;
        
    }

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
                    return FALSE;
                }
            }
        }
        return TRUE;
    }

}

?>