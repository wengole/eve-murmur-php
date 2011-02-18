<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

/**
 * Register - Shows basic EvE API registration form and processes Mumble registration
 *
 * @author Ben Cole <wengole@gmail.com>
 * @property Pheal_model $Pheal_model
 * @property Murmur_model $Murmur_model
 */
class Register extends CI_Controller {

    function __construct() {
        parent::__construct();
        $this->load->helper(array('html'));
        $this->load->model(array('Pheal_model', 'Murmur_model'));
    }

    function index() {
        echo "Work in progress!";
//        $title['title'] = 'Mumble Registration';
//        if ($this->form_validation->run('register1') == FALSE) {
//            $this->load->view('includes/html_head', $title);
//            $this->load->view('register/form_1');
//            $this->load->view('register/form_close');
//            $this->load->view('includes/html_foot');
//        } elseif ($this->form_validation->run('register2') == FALSE) {
//            $this->User->getCharacters($this->input->post('userid'), $this->input->post('apikey'));
//            // If API is OK show the character selection, else show API request
//            if ($characters != FALSE) {
//                $this->load->view('includes/html_head', $title);
//                $this->load->view('register/form_1');
//                $this->load->view('register/form_2', $characters);
//                $this->load->view('register/form_close');
//                $this->load->view('includes/html_foot');
//            } else {
//                $this->load->view('includes/html_head', $title);
//                $this->load->view('register/form_1');
//                $this->load->view('register/form_close');
//                $this->load->view('includes/html_foot');
//            }
//        } else {
//            // TODO: _getdata to populate registered view
//            // Do this in the if statement
//            $this->load->view('includes/html_head', $title);
//            $this->load->view('register/registered');
//            $this->load->view('includes/html_foot');
//        }
    }

    function submit() {
        $userID = $this->input->post('userid');
        $apiKey = $this->input->post('apikey');
        $charID = $this->input->post('charid');
        $password = $this->input->post('password');
        if (empty($charID)) {
            log_message('debug', '<' . __FUNCTION__ . '> Requesting characters for ' . $userID);
            $characters = $this->Pheal_model->getCharacters($userID, $apiKey);
            if ($characters) {
                log_message('debug', '<' . __FUNCTION__ . '> Got characteres, returning JSON');
                echo json_encode($characters);
            } else {
                log_message('error', '<' . __FUNCTION__ . '> ' . $this->Pheal_model->errorMessage);
                echo json_encode(array('type' => 'error', 'message' => $this->Pheal_model->errorMessage));
            }
        } elseif (!empty($charID) && !empty($password)) {
            $name = $this->Pheal_model->lookupCharName($charID);
            log_message('debug', '<' . __FUNCTION__ . '> Registering user: ' . $name);
            log_message('info', '<' . __FUNCTION__ . '> CharID: ' . $charID);
            $userInfo = array();
            $userInfo['UserName'] = $name;
            $murmurUserID = $this->Murmur_model->registerUser($userInfo);
            if (!$murmurUserID) {
                log_message('error', 'Failed to register: ' . $name);
                echo json_encode(array('type' => 'error', 'message' => 'Registration failed\n' . $this->Murmur_model->errorMessage));
            } else {
                echo json_encode(array('type' => 'success', 'message' => 'User registered'));
            }
        } else {
            echo json_encode(array('type' => 'error', 'message' => 'No valid character or password'));
        }
    }

}