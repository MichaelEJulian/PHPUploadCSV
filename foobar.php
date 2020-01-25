<?php

class FooBar{

    static public function show($num){
        if (is_int($num)){
            $output = "";

            for ($x=1; $x <= $num; $x++){

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

        }
        else{
            echo "Number is not an integer";
        }
    }    
}

FooBar::show(100);

?>