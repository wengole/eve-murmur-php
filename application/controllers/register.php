<?php

class Register extends Controller {

    function Register() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        
    }

    function index() {
        $this->load->library('Registration');
        $reg = new Registration();
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $reg;
        $this->load->view('includes/template', $data);
    }

    function add() {
        echo "test";
    }

}
