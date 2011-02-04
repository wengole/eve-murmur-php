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
        $this->load->helper(array('html', 'form'));
        $this->load->model('User');
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
        $charID = $this->input->post('username');
        $password = $this->input->post('password');
        if (!$charID && !$password) {
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
            echo "This will be the success message :P";
        }
    }

}