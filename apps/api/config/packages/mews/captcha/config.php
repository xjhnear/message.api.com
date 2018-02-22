<?php

return array(

    'id' => 'captcha',
    'fontsizes' => array(18, 18, 18, 20, 18,18),
    'length' => 4,
    'width' => 60,
    'height' => 30,
    'space' => 8,
    'type' => 'custom', // alpha or alnum or custom
    //'custom_chars' => '23456789abcdefghjkmnopqrstuvwxyzABCDEFGHJKLMNOPQRSTUVWXYZ',
    'custom_chars'=>'0123456789abcdefghijkmnpqrstuvwxyz',
    'colors' => array('128,23,23', '128,23,22', '33,67,87', '67,70,83', '31,56,163', '48,109,22', '165,42,166', '18,95,98', '213,99,8'),
    'sensitive' => false // case sensitive (params: true, false)

);