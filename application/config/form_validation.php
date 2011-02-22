<?php

$config = array(
    'register1' => array(
        array(
            'field' => 'userid',
            'label' => 'User ID',
            'rules' => 'required|trim|min_length[5]|max_length[10]|numeric'
        ),
        array(
            'field' => 'apikey',
            'label' => 'API Key',
            'rules' => 'required|trim|exact_length[64]|alpha_numeric'
        )
    ),
    'register2' => array(
        array(
            'field' => 'password',
            'label' => 'Password',
            'rules' => 'required|min_length[3]|matches[password2]|alpha_numeric'
        )
    )
);
?>
