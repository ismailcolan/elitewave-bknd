<?php
function get_status($id){
    switch($id){
        case 1:
            $s =  "<b>Picked Up</b>";
            break;
        case 2:
            $s = "Delivered";
            break;
            default:
            $s = "default Status";
                break;
    }
    return $s;
    
    }

?>