<?php


namespace App\EventListener;


class AuthenticationSuccessListener
{

    private $tokenTtl;

    public function __construct($tokenTtl)
    {
        $this->tokenTtl = $tokenTtl;
    }

}
