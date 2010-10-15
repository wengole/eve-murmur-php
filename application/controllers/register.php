<?php

class Register extends Controller {

	function Register()
	{
		parent::Controller();
                $this->load->helper(array('html', 'form'));
	}

	function index()
	{
            $this->load->model('User','',TRUE);
            $this->load->model('Registration');
            $form_config = array (
                'uname_array' => $this->Registration->getUname_array(),
                'selected_user' => $this->Registration->getSelected_user(),
                'userid' => $this->Registration->getUserid(),
                'apikey' => $this->Registration->getApikey()
            );
            $this->load->view('registerview', $form_config);
	}
}
