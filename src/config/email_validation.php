<?php
class email
{
    public function isValid($email)
    { 
        $verifica = filter_var($email, FILTER_VALIDATE_EMAIL);
        if ($verifica != false) {
            return true;
        } else {
            return   $verifica;
        }
    }
}
