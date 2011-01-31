<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

/**
 * @property Registereduser $Registereduser
 */

class Register extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->helper(array('html', 'form'));
        $this->load->model('Registereduser');
        $this->load->library('form_validation');
    }

    function index() {
        $title['title'] = 'Mumble Registration';
        if ($this->form_validation->run('register1') == FALSE) {
            $this->load->view('includes/html_head', $title);
            $this->load->view('register/form_1');
            $this->load->view('register/form_close');
            $this->load->view('includes/html_foot');
        } elseif ($this->form_validation->run('register2') == FALSE) {
            $this->Registereduser->getCharacters($this->input->post('userid'), $this->input->post('apikey'));
            // If API is OK show the character selection, else show API request
            if ($characters != FALSE) {
                $this->load->view('includes/html_head', $title);
                $this->load->view('register/form_1');
                $this->load->view('register/form_2', $characters);
                $this->load->view('register/form_close');
                $this->load->view('includes/html_foot');
            } else {
                $this->load->view('includes/html_head', $title);
                $this->load->view('register/form_1');
                $this->load->view('register/form_close');
                $this->load->view('includes/html_foot');
            }
        } else {
            // TODO: _getdata to populate registered view
            // Do this in the if statement
            $this->load->view('includes/html_head', $title);
            $this->load->view('register/registered');
            $this->load->view('includes/html_foot');
        }
    }

    function submit() {
        $userID = $this->input->post('userid');
        $apiKey = $this->input->post('apikey');
        $charID = $this->input->post('username');
        $password = $this->input->post('password');
        if (!$charID && !$password) {
            log_message('debug', 'Requesting characters for '.$userID);
            $characters = $this->Registereduser->getCharacters($userID, $apiKey);
            if($characters) {
                log_message('debug', 'Got characteres, returning JSON');
                echo json_encode($characters);
            } else {
                log_message('error', 'Pheal: '.$this->Registereduser->errorMessage);
                echo json_encode(array('message' => $this->Registereduser->errorMessage));
            }
        } else {
            echo "This will be the success message :P";
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
            if (!empty($data['data']['errorMessage'])) {
                $data['main_content'] = 'registerview';
            } else {
                $data['main_content'] = 'registeredview';
            }
            $this->load->view('includes/template', $data);
        } else {
            $data['main_content'] = 'registerview';
            $data['title'] = 'Mumble Registration';
            $this->load->view('includes/template', $data);
        }
    }

}