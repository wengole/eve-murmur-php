<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

class Register extends Controller {

    function __construct() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        $this->load->model('registereduser');
        $this->load->library('form_validation');
    }

    function index() {
        $title['title'] = 'Mumble Registration';
        if ($this->form_validation->run('register1') == FALSE) {
            $this->load->view('includes/html_head', $title);
            $this->load->view('register/form_1');
            $this->load->view('register/form_close');
            $this->load->view('includes/html_foot');
        } elseif($this->form_validation->run('register2') == FALSE) {
            // TODO: Get username array
            $this->load->view('includes/html_head', $title);
            $this->load->view('register/form_1');
            $this->load->view('register/form_2');
            $this->load->view('register/form_close');
            $this->load->view('includes/html_foot');
        } else {
            // TODO: _getdata to populate registered view
            // Do this in the if statement
            $this->load->view('includes/html_head', $title);
            $this->load->view('register/registered');
            $this->load->view('includes/html_foot');
        }
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