<?php
require 'vendor/autoload.php';
// require_once 'src/trakerperson.php'; i am use autoloading 

use \Traker\Trakerperson as Traker;

$trakerPerson = new Traker;

$msg = $trakerPerson->addDataPerson();

$msg = $trakerPerson->deleting();


echo $msg;





