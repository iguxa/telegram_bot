<?php

$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
);


return array(
    'host' => 'db_host',
    'dbname' => 'db_name',
    'user' => 'db_user',
    'password' => 'db_pass',
    'options' => $options
);