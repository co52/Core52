<?php

# Grab the core
$dir = dirname(dirname(__DIR__));
require($dir.'/core/initialize.php');

# Optional session initialization, uncomment as needed
/*
Session::Initialize(array(
        'cookie_uri' => Router::domain('.', FALSE),
        'hit' => TRUE,
        'salt' => Config::get('salt'),
        'login_controller' => '/login',
        'user_class' => 'User',
        'prevent_xsrf' => TRUE,
        'stronger_tokens' => FALSE,
        'expiration_threshold' => '+12 hour',
));
*/