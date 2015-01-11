<?php

function uni2ord($string) {
    $ordinals = array();
    $string = mb_convert_encoding($string, 'UCS-4BE', 'UTF-8');
    
    for ($i = 0; $i < mb_strlen($string, 'UCS-4BE'); ++$i) {
        $value = unpack('N', mb_substr($string, $i, 1, 'UCS-4BE'));
        $ordinals[] = $value[1];
    }
    
    return $ordinals;
}

function ord2uni($ordinals) {
    $string = null;
    for ($i = 0; $i < sizeof($ordinals); ++$i) {
        $string .= pack('N', $ordinals[$i]);
    }
    
    return mb_convert_encoding($string, 'UTF-8', 'UCS-4BE');
}

function expand($bytes, $string) {
    switch($bytes) {
        case 1:
             $lenght = 7;
             break;
             
        case 2:
            $lenght = 11;
            break;
            
        case 3:
            $lenght = 16;
            break;
            
        case 4:
            $lenght = 21;
            break;
        
        default:
            return $string;
    }
    
    $lenght -= strlen($string);
    return str_repeat('0', $lenght) . $string;
}

function bytes($bytes, $string) {
    $string = expand($bytes, $string);
    
    switch ($bytes) {
        case 1: // Single-byte
            return array(
                '0' . $string);
        
        case 2:
            return array(
                '110' . substr($string, 0, 5),
                '10' . substr($string, 5, 6));
    
        case 3:
            return array(
                '1110' . substr($string, 0, 4),
                '10' . substr($string, 4, 6),
                '10' . substr($string, 10, 6),);
    
        case 4:
            return array(
                '11110' . substr($string, 0, 3),
                '10' . substr($string, 3, 6),
                '10' . substr($string, 9, 6),
                '10' . substr($string, 15, 6));
    }
    
    return null;
}

function enlarge($variable, $length) {
    return str_repeat('0', $length - strlen($variable)) . $variable;
}

function space($bits) {
    if ($bits < 8) {
        return 1;
    }
    elseif ($bits < 12) {
        return 2;
    }
    elseif ($bits < 17) {
        return 3;
    }
    elseif ($bits < 22) {
        return 4;
    }
    
    return 0;
}

function text2bin($string2, $method, $divider) {
    $ordinals = uni2ord($string2);
    
    switch ($method) {
        case 16:
            
            $hexadecimal = array();
            foreach ($ordinals as $value) {
                $hexadecimal[] = strtoupper('U+' . enlarge(dechex($value), 4));
            }
            
            return implode($divider, $hexadecimal);
            
        case 2:
        case 8:
            break;
        
        default:
            return null;    
    }
    
    $binary = $bytes = $octal = array();
    foreach ($ordinals as $value) {
        $binary[] = decbin($value);
    }

    // Counting byte-length
    foreach ($binary as $value) {
        $bytes[] = space(strlen($value));
    }
    
    foreach ($bytes as $key => $value) {
        $binary[$key] = bytes($value, $binary[$key]);
    }
    
    // Binary-output requested
    if ($method == 2) {
        foreach ($binary as &$value) {
            if (is_array($value)) {
                $value = implode($divider, $value);
            }
            else {
                $value = '?'; // Invalid character
            }
        }
        
        return implode($divider, $binary);
    }
    
    // Octal-output requested
    foreach ($binary as $value) {
        if (is_array($value)) {
            foreach($value as &$byte) {
                $byte = '0x' . enlarge(strtoupper(dechex(bindec($byte))), 2);
            }
            
            $octal[] = implode($divider, $value);
        }
        else {
            $octal[] = '?'; // Invalid character
        }
    }
    
    return implode($divider, $octal);
}

function guess($prefix) {
    if (preg_match('/^[01]{8}$/i', $prefix)) {
        return 2;
    }
    elseif (preg_match('/U\+$/i', $prefix)) {
        return 16;
    }
    elseif (preg_match('/0[0-9a-f]{3}|1[0-9a-f]{3}/i', $prefix)) {
        return 16;
    }
    elseif (preg_match('/0x/i', $prefix)) {
        return 8;
    }
    elseif (preg_match('/[1-9a-f][0-9a-f].*[1-9a-f][0-9a-f]/i', $prefix)) {
        return 8;
    }
    
    return null;
}

function bin2text($binary) {
    $method = guess(mb_substr($binary, 0, 8));
    
    switch ($method) {
        case 16:
            $array = parse($binary, 16);
            foreach($array as &$value) $value = hexdec($value);
            return ord2uni($array);
            
        case 8:
        case 2:
            $array = parse($binary, $method);
            foreach($array as &$value) $value = bindec($value);
            return ord2uni($array);
    }
    
    return null;
}

function byteCombine(array $array) {
    $result = array();
    while (($item = array_shift($array)) != null) {
        $drop = false;
        
        // Four-byte characters
        if (substr($item, 0, 5) == '11110') {
            if (($next = array_shift($array)) != null
                && ($second = array_shift($array)) != null
                && ($third = array_shift($array)) != null) {
                $item = substr($item, 5) . substr($next, 2) . substr($second, 2) . substr($third, 2);
            }
            else {
                $drop = true;
            }
        }
        elseif (substr($item, 0, 4) == '1110') {
            if (($next = array_shift($array)) != null
                && ($second = array_shift($array)) != null) {
                $item = substr($item, 4) . substr($next, 2).substr($second, 2);
            }
            else {
                $drop = true;
            }
        }
        elseif (substr($item, 0, 3) == '110') {
            if (($next = array_shift($array)) != null) {
                $item = substr($item, 3) . substr($next, 2);
            }
            else {
                $drop = true;
            }
        }
        // Single-byte character
        else {
            $item = substr($item, 1);
        }
        
        if (!$drop) {
            $result[] = round($item); // Processing correct ones
        }
    }
    
    return $result;
}

function parse($input, $type) {
    if ($type == 16) {
        preg_match_all('/(?:U\+)?([0-9a-f]{4})/i', $input, $matches);
        $array = $matches[1];
    }
    elseif ($type == 8) {
        preg_match_all('/(?:0x)?([0-9a-f]{2})/i', $input, $matches);
        $array = $matches[1];
        foreach ($array as &$value) {
            $value = enlarge(decbin(hexdec($value)), 8);
        }
        
        $array = byteCombine($array);
    }
    elseif ($type == 2) {
        preg_match_all('/[01]{8}/i',$input,$matches);
        $array = byteCombine($matches[0]);
    }
    
    return $array;
}
