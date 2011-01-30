<?php

class Registereduser extends CI_Model {

    var $errorMessage;
    var $server;

    function Registereduser() {
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
            log_message('debug','Pheal->Characters()');
            $result = $pheal->Characters();
        } catch (PhealAPIException $exc) {
            log_message('error', $exc->getMessage());
            $this->errorMessage = $exc->getMessage();
            return FALSE;
        }
        $characters = array();
        foreach ($result->characters as $character) {
            $characters[] = array('charid' => (string)$character->characterID, 'name' => (string)$character->name);
            log_message('info',$character->characterID.' : '.$character->name);
        }
        log_message('debug','Returning characters');
        return $characters;
        //$this->updateBlues();
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
                // TODO: Check for errors and return false
                $this->db->delete('contacts');
                foreach ($contacts as $contact) {
                    // TODO: Check for errors and return false
                    $this->db->insert('contacts', $contact);
                }
            }
        } else {
            foreach ($result->allianceContactList as $contact) {
                $contacts[] = $contact;
            }
            if (!empty($contacts)) {
                // TODO: Check for errors and return false
                $this->db->delete('contacts');
                foreach ($contacts as $contact) {
                    // TODO: Check for errors and return false
                    $this->db->insert('contacts', $contact);
                }
            }
        }
        return TRUE;
    }

}

?>