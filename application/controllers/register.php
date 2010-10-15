<?php

class Register extends Controller {

    function Register() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        
    }

    function index() {
        $this->load->library('Registration');
        $reg = new Registration();
        $this->load->view('registerview', $reg);
    }

    function add() {
        echo "test";
    }

}
