<?php

class db 
{

    //Paramiters

    
    private $dbuser = 'its_group1';
    private $dbpass = 'its';

    //Connect
    public function connect()
    {
        $conn = oci_connect($this->dbuser, $this->dbpass, '(DESCRIPTION = (ADDRESS_LIST = (ADDRESS = (PROTOCOL = TCP)(HOST = 212.4.10.98)(PORT = 1521)))(CONNECT_DATA = (SERVER = DEDICATED)(SERVICE_NAME = ITS)))');
        return $conn;
    }
    public function query($query){
        
    }
}