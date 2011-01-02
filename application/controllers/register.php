<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

class Register extends Controller {

    function __construct() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        $this->load->model('Registration');
    }

    function index() {
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $this->_getData();
        $this->load->view('includes/template', $data);
    }

    function add() {
        $this->Registration->setUserID(trim($this->input->post('userid')));
        $this->Registration->setApikey(trim($this->input->post('apikey')));
        $this->Registration->setSelectedUser($this->input->post('username'));
        $userID = $this->Registration->getUserID();
        $apiKey = $this->Registration->getApiKey();
        if ($this->input->post('apikey') != $this->Registration->getApiKey() || $this->input->post('userid') != $this->Registration->getUserID()
                || (empty($unameArray) && !empty($userID) && !empty($apiKey))) {
            $this->Registration->populateCharacters();
            $unameArray = $this->Registration->getUnameArray();
        }
        if (!empty($unameArray)) {
            $this->Registration->setUsername($this->Registration->getSelectedUser());
        }
        $this->Registration->setPassword($this->input->post('password'));
        $this->Registration->setPassword2($this->input->post('password2'));
        if (preg_match("/^[A-Za-z0-9-._]*\z/", $this->Registration->getPassword()) && $this->Registration->getPassword() != ""
                && $this->Registration->getPassword() == $this->Registration->getPassword2()) {
            $this->Registration->registerUser();
            $data['title'] = 'Mumble Registration';
            $data['data'] = $this->_getData();
            if (!empty($data['data']['errorMessage'])) {
                $data['main_content'] = 'registerview';
            } else {
                $data['main_content'] = 'registeredview';
            }
            $this->load->view('includes/template', $data);
        } else {
            $data['main_content'] = 'registerview';
            $data['title'] = 'Mumble Registration';
            $data['data'] = $this->_getData();
            $this->load->view('includes/template', $data);
        }
    }

    function _getData() {
        $data = array(
            'userID' => $this->Registration->getUserID(),
            'apiKey' => $this->Registration->getApiKey(),
            'unameArray' => $this->Registration->getUnameArray(),
            'selectedUser' => $this->Registration->getSelectedUser(),
            'username' => $this->Registration->getUsername(),
            'password' => $this->Registration->getPassword(),
            'password2' => $this->Registration->getPassword2(),
            'errorMessage' => $this->Registration->getErrorMessage(),
            'host' => $this->Registration->getHost(),
            'port' => $this->Registration->getPort(),
            'successMessage' => $this->Registration->getSuccessMessage()
        );
        return $data;
    }

}
