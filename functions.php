<?php

function uni2ord($string) {
    $ords = array();
    $string = mb_convert_encoding($string, 'UCS-4BE', 'UTF-8');
    
    for ($i = 0; $i < mb_strlen($string, 'UCS-4BE'); ++$i) {
        $val = unpack('N', mb_substr($string, $i, 1, 'UCS-4BE'));
        $ords[] = $val[1];
    }
    
    return($ords);
}

function ord2uni($ords) {
    $string = null;
    for ($i = 0; $i < sizeof($ords); ++$i) {
        $string .= pack('N', $ords[$i]);
    }
    
    return mb_convert_encoding($string, 'UTF-8', 'UCS-4BE');
}

function expand($bytes, $string) {
    if ($bytes == 1) $len = 7 - strlen($string);
    elseif ($bytes == 2) $len = 11 - strlen($string);
    elseif ($bytes == 3) $len = 16 - strlen($string);
    elseif ($bytes == 4) $len = 21 - strlen($string);
    else return $string;
    
    $output = null;
    for ($i = 0; $i < $len; ++$i) $output .= '0';
    return $output . $string;
}

function bytes($bytes, $str, $char) {
    $char = '**' . $char;
    $str = expand($bytes, $str);
    if ($bytes == 1) return '0' . $str;
    elseif ($bytes == 2) return '110' . substr($str, 0, 5).$char . '10' . substr($str, 5, 6);
    elseif ($bytes == 3) return '1110' . substr($str, 0, 4).$char . '10' . substr($str, 4, 6) . $char . '10' . substr($str, 10, 6);
    elseif ($bytes == 4) return '11110' . substr($str, 0, 3).$char . '10' . substr($str, 3, 6) . $char . '10' . substr($str, 9, 6) . $char . '10' . substr($str, 15, 6);
    else return $str;
}

function enlarge($var,$num) {
    $output = null;
    for ($i = 0; $i < $num - strlen($var); ++$i) $output .= '0';
    return $output . $var;
}

function arr2str($arr,$char) {
    $output = null;
    foreach ($arr as $val) $output .= $char . $val;
    return mb_substr($output, mb_strlen($char));
}

function text2bin($str, $method, $char) {
    $str = uni2ord($str);
    
    foreach ($str as $key => $val) $hex[$key] = strtoupper('U+' . enlarge(dechex($val), 4));
    
    if ($method == 16) return arr2str($hex, $char);
    
    foreach ($str as $key => $val) $bin[$key] = decbin($val);
    
    foreach ($bin as $key => $val) { //Counts byte length
        if(strlen($val) < 8) $bytes[$key] = 1;
        elseif(strlen($val) < 12) $bytes[$key] = 2;
        elseif(strlen($val) < 17) $bytes[$key] = 3;
        elseif(strlen($val) < 22) $bytes[$key] = 4;
        else $bytes[$key] = 0;
    }
    
    foreach ($bytes as $key => $val) $bin[$key] = bytes($val, $bin[$key], $char);
    
    if($method == 2) return arr2str(str_replace('**', '', $bin), $char);
    
    foreach ($bin as $key => $val) {
        $pol = explode('**' . $char, $val);
        foreach($pol as &$byt) $byt = '0x' . enlarge(strtoupper(dechex(bindec($byt))), 2);
        $val = implode($char, $pol);
        $oct[$key] = $val;
    }
    
    if($method == 8) return arr2str($oct, $char);
    
    return array(arr2str(str_replace('**', '', $bin), $char), arr2str($oct, $char), arr2str($hex, $char));
}

function bin2text($binary) {
    $example = mb_substr($binary, 0, 8);
    
    if(preg_match('@^[01]{8}$@i', $example)) $method = 2;
    elseif(preg_match('@U\+$@i', $example)) $method = 16;
    elseif(preg_match('@0[0-9a-f]{3}|1[0-9a-f]{3}@i', $example)) $method = 16;
    elseif(preg_match('@0x@i', $example)) $method = 8;
    elseif(preg_match('@[1-9a-f][0-9a-f].*[1-9a-f][0-9a-f]@i', $example)) $method = 8;
    else return 'Error: Data encoding not recognized..';
    
    if($method == 16) {
        $arr = parse($binary, 16);
        foreach($arr as &$val) $val = hexdec($val);
    }
    else {
        $arr = parse($binary, $method);
        foreach($arr as &$val) $val = bindec($val);
    }
    
    return ord2uni($arr);
}

function byteCombine($arr) {
    $res = array();
    while (($item = array_shift($arr)) != null) {
        $drop = false;
        
        if (substr($item, 0, 5) == '11110') {
            if (($next = array_shift($arr)) != null && ($second = array_shift($arr)) != null && ($third = array_shift($arr)) != null) $item = substr($item, 5) . substr($next, 2) . substr($second, 2) . substr($third, 2);
            else $drop = true;
        }
        elseif (substr($item, 0, 4) == '1110') {
            if (($next = array_shift($arr)) != null && ($second = array_shift($arr)) != null) $item = substr($item, 4) . substr($next, 2).substr($second, 2);
            else $drop = true;
        }
        elseif (substr($item, 0, 3) == '110') {
            if (($next = array_shift($arr)) != null) $item = substr($item, 3) . substr($next, 2);
            else $drop = true;
        }
        else $item = substr($item,1);
        
        if (!$drop) $res[] = round($item); //pokud nebyla chyba, uložit..
    }
    
    return $res;
}

function parse($input, $type) {
    $len = strlen($input);
    
    if($type == 16) {
        preg_match_all('@(?:U\+)?([0-9a-f]{4})@i', $input, $matches); //(?:U\+)?([0-9a-f]{4,6})[^0-9a-f]|
        $arr = $matches[1];
    }
    elseif($type == 8) {
        preg_match_all('@(?:0x)?([0-9a-f]{2})@i', $input, $matches);
        $arr = $matches[1];
        foreach ($arr as &$val) $val = enlarge(decbin(hexdec($val)), 8);
        $arr = byteCombine($arr);
    }
    elseif($type == 2) {
        preg_match_all('@[01]{8}@i',$input,$matches);
        $arr = byteCombine($matches[0]);
    }
    
    return $arr;
}

#$input = 'Žluťoučký kůň pěl ďábelské ódy. € ¢';
#$result = text2bin($input,26,',');
#print_r($result);

#$input2 = 'U+017d U+006c U+0075 U+0165 U+006f U+0075 U+010d U+006b U+00fd U+0020 U+006b U+016f U+0148 U+0020 U+0070 U+011b U+006c U+0020 U+010f U+00e1 U+0062 U+0065 U+006c U+0073 U+006b U+00e9 U+0020 U+00f3 U+0064 U+0079 U+002e U+0020 U+20ac U+0020 U+00a2';
#$input2 = 'U+017d,U+006c,U+0075,U+0165,U+006f,U+0075,U+010d,U+006b,U+00fd,U+0020,U+006b,U+016f,U+0148,U+0020,U+0070,U+011b,U+006c,U+0020,U+010f,U+00e1,U+0062,U+0065,U+006c,U+0073,U+006b,U+00e9,U+0020,U+00f3,U+0064,U+0079,U+002e,U+0020,U+20ac,U+0020,U+00a2';
#$input2 = 'U+017dU+006cU+0075U+0165U+006fU+0075U+010dU+006bU+00fdU+0020U+006bU+016fU+0148U+0020U+0070U+011bU+006cU+0020U+010fU+00e1U+0062U+0065U+006cU+0073U+006bU+00e9U+0020U+00f3U+0064U+0079U+002eU+0020U+20acU+0020U+00a2';
#$input2 = '017d 006c 0075 0165 006f 0075 010d 006b 00fd 0020 10006b 016f 0148 0020 0070 011b 006c 0020 010f 00e1 0062 0065 006c 0073 006b 00e9 0020 00f3 0064 0079 002e 0020 20ac 0020 00a2';
#$input2 = '017d,006c,0075,0165,006f,0075,010d,006b,00fd,0020,006b,016f,0148,0020,0070,011b,006c,0020,010f,00e1,0062,0065,006c,0073,006b,00e9,0020,00f3,0064,0079,002e,0020,20ac,0020,00a2';
#$input2 = '017d006c00750165006f0075010d006b00fd0020006b016f014800200070011b006c0020010f00e100620065006c0073006b00e9002000f300640079002e002020ac002000a2';
#$input2 = '0xC5 0xBD 0x6C 0x75 0xC5 0xA5 0x6F 0x75 0xC4 0x8D 0x6B 0xC3 0xBD 0x20 0x6B 0xC5 0xAF 0xC5 0x88 0x20 0x70 0xC4 0x9B 0x6C 0x20 0xC4 0x8F 0xC3 0xA1 0x62 0x65 0x6C 0x73 0x6B 0xC3 0xA9 0x20 0xC3 0xB3 0x64 0x79 0x2E 0x20 0xE2 0x82 0xAC 0x20 0xC2 0xA2';
#$input2 = '0xC5,0xBD,0x6C,0x75,0xC5,0xA5,0x6F,0x75,0xC4,0x8D,0x6B,0xC3,0xBD,0x20,0x6B,0xC5,0xAF,0xC5,0x88,0x20,0x70,0xC4,0x9B,0x6C,0x20,0xC4,0x8F,0xC3,0xA1,0x62,0x65,0x6C,0x73,0x6B,0xC3,0xA9,0x20,0xC3,0xB3,0x64,0x79,0x2E,0x20,0xE2,0x82,0xAC,0x20,0xC2,0xA2';
#$input2 = '0xC50xBD0x6C0x750xC50xA50x6F0x750xC40x8D0x6B0xC30xBD0x200x6B0xC50xAF0xC50x880x200x700xC40x9B0x6C0x200xC40x8F0xC30xA10x620x650x6C0x730x6B0xC30xA90x200xC30xB30x640x790x2E0x200xE20x820xAC0x200xC20xA2';
#$input2 = 'C5 BD 6C 75 C5 A5 6F 75 C4 8D 6B C3 BD 20 6B C5 AF C5 88 20 70 C4 9B 6C 20 C4 8F C3 A1 62 65 6C 73 6B C3 A9 20 C3 B3 64 79 2E 20 E2 82 AC 20 C2 A2';
#$input2 = 'C5,BD,6C,75,C5,A5,6F,75,C4,8D,6B,C3,BD,20,6B,C5,AF,C5,88,20,70,C4,9B,6C,20,C4,8F,C3,A1,62,65,6C,73,6B,C3,A9,20,C3,B3,64,79,2E,20,E2,82,AC,20,C2,A2';
#$input2 = 'C5BD6C75C5A56F75C48D6BC3BD206BC5AFC5882070C49B6C20C48FC3A162656C736BC3A920C3B364792E20E282AC20C2A2';
#$input2 = '11000101 10111101 01101100 01110101 11000101 10100101 01101111 01110101 11000100 10001101 01101011 11000011 10111101 00100000 01101011 11000101 10101111 11000101 10001000 00100000 01110000 11000100 10011011 01101100 00100000 11000100 10001111 11000011 10100001 01100010 01100101 01101100 01110011 01101011 11000011 10101001 00100000 11000011 10110011 01100100 01111001 00101110 00100000 11100010 10000010 10101100 00100000 11000010 10100010';
#$input2 = '11000101,10111101,01101100,01110101,11000101,10100101,01101111,01110101,11000100,10001101,01101011,11000011,10111101,00100000,01101011,11000101,10101111,11000101,10001000,00100000,01110000,11000100,10011011,01101100,00100000,11000100,10001111,11000011,10100001,01100010,01100101,01101100,01110011,01101011,11000011,10101001,00100000,11000011,10110011,01100100,01111001,00101110,00100000,11100010,10000010,10101100,00100000,11000010,10100010';
#$input2 = '11000101101111010110110001110101110001011010010101101111011101011100010010001101011010111100001110111101001000000110101111000101101011111100010110001000001000000111000011000100100110110110110000100000110001001000111111000011101000010110001001100101011011000111001101101011110000111010100100100000110000111011001101100100011110010010111000100000111000101000001010101100001000001100001010100010';
#echo bin2text($input2);
