<?php

class Register extends Controller {

	function Register()
	{
		parent::Controller();
	}

	function index()
	{
            $this->load->model('User','',TRUE);
            $this->load->helper('html');
            $this->load->view('registerview');
	}
}
