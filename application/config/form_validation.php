<?php

$config = array(
    'register1' => array(
        array(
            'field' => 'userid',
            'label' => 'User ID',
            'rules' => 'required|min_length[5]|max_length[10]|numeric|trim'
        ),
        array(
            'field' => 'apikey',
            'label' => 'API Key',
            'rules' => 'required|exact_length[64]|alpha_numeric|trim'
        )
    ),
    'register2' => array(
        array(
            'field' => 'password',
            'label' => 'Password',
            'rules' => 'matches[password2]|alpha_numeric'
        )
    )
);
?>
