<?php
use Jenssegers\Agent\Agent;
$agent = new Agent();

if ($agent->is("iPhone")) {
//if(1==1){
    header("Pragma: no-cache");
    header("Cache-Control: no-store, no-cache, must-revalidate, post-check=0, pre-check=0");

    header("Content-type: application/vnd.apple.pkpass; charset=UTF-8");
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition:attachment; filename=' . basename($serialNumber .'.pkpass'));
    header('Content-Transfer-Encoding: binary');

    flush();
    readfile(\URL::to('/') . "/Output.raw/" . $serialNumber.'.pkpass');
} else if ($agent->is("Samsung")) {
    // reader android file
} else {
    echo "123";
    // other template such as web
}


?>
