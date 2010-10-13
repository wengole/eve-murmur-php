<?php

class Register extends Controller {

	function Register()
	{
		parent::Controller();
	}

	function index()
	{
		$this->load->view('welcome_message');
	}
}

/* End of file welcome.php */
/* Location: ./system/application/controllers/welcome.php */