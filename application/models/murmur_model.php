<?php

/**
 * Murmur_model - Handles data communication with Mumble server (Murmur)
 * @author Ben Cole <wengole@gmail.com>
 */
class Murmur_model extends CI_Model {

    var $server;

    function __construct() {
        parent::__construct();
        $initData = new Ice_InitializationData;
        $initData->properties = Ice_createProperties();
        $initData->properties->setProperty('Ice.ImplicitContext', 'Shared');
        $ICE = Ice_initialize($initData);
        $meta = Murmur_MetaPrxHelper::checkedCast($ICE->stringToProxy($this->config->item('iceProxy')));
        $this->server = $meta->getServer($this->config->item('vServerID'));
    }

}

?>
