<?php

return [

    // Home directory (multi-user mode supported)
    // Example: $homeDirectory = '/tmp';
    //          $homeDirectory = array('user1' => '/home/user1', 'user2' => '/home/user2');
    'homeDirectory' => '',


    // Password hash algorithm (password must be hashed)
    // Example: $passwordHashAlgorithm = 'md5';
    //          $passwordHashAlgorithm = 'sha256';
    'passwordHashAlgorithm' => 'md5',


    // Multi-user credentials
    // Example: $accounts = array('user1' => 'password1', 'user2' => 'password2');
    'accounts' => ['mostafa' => '5ebe2294ecd0e0f08eab7690d2a6ee69'], // secret

    'noLogin' => true,

];
