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
        $this->load->helper(array('html', 'url'));
        $this->load->model(array('Pheal_model', 'Murmur_model'));
    }

    function index() {
        $title = array('title' => 'EVE Murmur API Registration');
        $this->load->view('includes/html_head', $title);
        $this->load->view('includes/header');
        $this->load->view('register/registerview');
        $this->load->view('includes/footer');
        $this->load->view('includes/html_foot');
    }

    function submit() {
        $userID = $this->input->post('userid');
        $apiKey = $this->input->post('apikey');
        $charID = $this->input->post('charid');
        $password = $this->input->post('password');
        if (empty($charID)) {
            log_message('info', '<' . __FUNCTION__ . '> Requesting characters for ' . $userID);
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
            log_message('info', '<' . __FUNCTION__ . '> Registering user: ' . $name);
            log_message('info', '<' . __FUNCTION__ . '> Password: ' . $password);
            $userInfo = array(
                $name,
                null,
                null,
                null,
                $password
            );
            $murmurUserID = $this->Murmur_model->registerUser($userInfo);
            if (!$murmurUserID) {
                log_message('error', '<' . __FUNCTION__ . '> Failed to register: ' . $name);
                echo json_encode(array('type' => 'error', 'message' => 'Registration failed<br />' . $this->Murmur_model->errorMessage));
            } else {
                log_message('debug', '<' . __FUNCTION__ . '> Registered: ' . $name);
                $url = 'mumble://' . str_replace(".", "%2E", rawurlencode($name)) . ':' . $password . '@' . $this->Murmur_model->createURL();
                log_message('info', '<' . __FUNCTION__ . '> URL: ' . $url);
                echo json_encode(array('type' => 'success', 'message' => $url));
            }
        } else {
            echo json_encode(array('type' => 'error', 'message' => 'No valid character or password'));
        }
    }

}