<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once 'Ice.php';
require_once APPPATH . 'libraries/Murmur_1.2.2.php';

/**
 * @property User $User
 */
class Register extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->helper(array('html'));
        $this->load->model('User');
    }

    function index() {
        $title['title'] = 'Mumble Registration';
        if ($this->form_validation->run('register1') == FALSE) {
            $this->load->view('includes/html_head', $title);
            $this->load->view('register/form_1');
            $this->load->view('register/form_close');
            $this->load->view('includes/html_foot');
        } elseif ($this->form_validation->run('register2') == FALSE) {
            $this->User->getCharacters($this->input->post('userid'), $this->input->post('apikey'));
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
        $charID = $this->input->post('charid');
        $password = $this->input->post('password');
        if (!$charID && !$password && $password != "") {
            log_message('debug', 'Requesting characters for ' . $userID);
            $characters = $this->User->getCharacters($userID, $apiKey);
            if ($characters) {
                log_message('debug', 'Got characteres, returning JSON');
                echo json_encode($characters);
            } else {
                log_message('error', 'Pheal: ' . $this->User->errorMessage);
                echo json_encode(array('error' => $this->User->errorMessage));
            }
        } else {
            log_message('debug', 'Registering user');
            log_message('info', 'CharID: '.$charID);
            log_message('info', 'Password: '.$password);
            echo json_encode(array('success' => 'User registered'));
        }
    }

}