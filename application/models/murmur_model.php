<?php

/**
 * Murmur_model - Handles data communication with Mumble server (Murmur)
 * @author Ben Cole <wengole@gmail.com>
 */
class Murmur_model extends CI_Model {

    var $meta;
    var $server;

    function __construct() {
        parent::__construct();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $this->meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
    }

    /**
     * getUserName() - Retrieves associative array of userIDs to usernames from Murmur
     * 
     * @param int $vServerID ID of Murmur virtual server
     * @return Array NameMap userID => username
     */
    function getUserNames($vServerID) {
        $this->server = $this->meta->getServer($vServerID);
        return $this->server->getRegisteredUsers();
    }

}

?>
