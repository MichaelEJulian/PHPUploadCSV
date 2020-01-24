<?php

$output = "";

for ($x=1; $x <= 100; $x++){

    if ($output != ""){$output .= ", ";}

    if (($x % 3 === 0) && ($x % 5 === 0)){
        $output .= "foobar";
    }
    elseif ($x % 3 === 0){
        $output .= "foo";
    }
    elseif ($x % 5 === 0){
        $output .= "bar";
    }
    else{
        $output .= $x;
    }
}

echo $output;

?>