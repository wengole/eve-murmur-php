<?php

class Register extends Controller {

	function Register()
	{
		parent::Controller();
	}

	function index()
	{
            $this->load->helper('html');
            $this->load->view('registerview');
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */