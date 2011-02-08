<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * admin - Main controller of all admin fucntionality
 *
 * @author Ben Cole <wengole@gmail.com>
 * @property Pheal_model $Pheal_model
 * @property Murmur_model $Murmur_model
 */
class Admin extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->model(array('Pheal_model', 'Murmur_model'));
    }

    function import() {
        // Murmur vServer to import from
        $importFrom = 4;
        $murmurUsers = $this->Murmur_model->getUserNames($importFrom);
        foreach ($murmurUsers as $murmurUser) {
            $eveUser = $this->Pheal_model->getUserFromOldDB($murmurUser['murmurUserID']);
        }
    }

}

?>
