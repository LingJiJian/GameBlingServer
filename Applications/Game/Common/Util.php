<?php

function array_remove($data, $key){  
    if(!array_key_exists($key, $data)){  
        return $data;  
    }  
    $keys = array_keys($data);  
    $index = array_search($key, $keys);  
    if($index !== FALSE){  
        array_splice($data, $index, 1);  
    }  
    return $data;  
}

function get_pokers($card_num) 
{
	$cards=$tmp=array(); 
	for($i=0;$i<$card_num;$i++){ 
         $tmp[$i]=$i; 
     }
     for($i=0;$i<$card_num;$i++){ 
         $index=rand(0,$card_num-$i-1); 
         $cards[$i]=$tmp[$index]; 
         unset($tmp[$index]); 
         $tmp=array_values($tmp); 
     } 
     return $cards; 
} 

