<?php
class email
{
    public function isValid($email)
    { 
        $verifica = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($verifica != FALSE) {
            return true;
        } else {
            return   $verifica;
        }
    }
}
