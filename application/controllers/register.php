<?php

class Register extends Controller {

    private $reg;

    function Register() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        $this->load->library('Registration');
        $this->reg = new Registration();
    }

    function index() {
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $this->reg;
        $this->load->view('includes/template', $data);
    }

    function add() {
        $this->reg->userid = $this->input->post('userid');
        $this->reg->apikey = $this->input->post('apikey');
        $this->reg->username = $this->input->post('username');
        if (empty($this->reg->uname_array) && !empty($this->reg->userid) && !empty($this->reg->apikey))
            $this->reg->uname_array = $this->getCharacters();
        if (!empty($this->reg->username))
            $this->reg->selected_user = $this->reg->getSelectedUser();
        $this->reg->password = $this->input->post('password');
        $this->reg->password2 = $this->input->post('password2');
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $this->reg;
        $this->load->view('includes/template', $data);
    }

    function getCharacters() {
        //TODO: Create uname_array with key of charID and value of charName
        //
    }

}
