<?php

class Register extends Controller {

    function Register() {
        parent::Controller();
        $this->load->helper(array('html', 'form'));
        $this->load->library('Registration');
        $reg = new Registration();
    }

    function index() {
        $data['main_content'] = 'registerview';
        $data['title'] = 'Mumble Registration';
        $data['data'] = $reg;
        $this->load->view('includes/template', $data);
    }

    function add() {
        $reg->userid = $this->input('userid');
        $reg->apikey = $this->input('apikey');
        $reg->username = $this->input('username');
        if(empty ($reg->uname_array) && !empty ($reg->userid) && !empty ($reg->apikey))
                $reg->uname_array = $this->getCharacters();
        if(!empty ($reg->username))
                $reg->selected_user = $reg->getSelectedUser();
        $reg->password = $this->input('password');
        $reg->password2 = $this->input('password2');
    }

    function getCharacters() {
        //TODO: Create uname_array with key of charID and value of charName
        //
    }
}
