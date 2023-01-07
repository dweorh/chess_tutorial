<?php
namespace dweorh\Utils;

class Generators {
    public static function uuidv4() : string
    {
        $n = [8, 9, 'a', 'b'];
        $seed = microtime(true);
        $rand_max = getrandmax();
        $pattern = "xxxxxxxx-xxxx-4xxx-Nxxx-xxxxxxxxxxxx";
        $output = '';
        for($i = 0; $i < strlen($pattern); $i++) {
            $c = $pattern[$i];
            if ($c === 'N') {
                $key = round(rand() / $rand_max * 10 % 3);
                $output  .= (string) $n[$key];
            } else if ($c === '-') {
                $output .= '-';
            } else if ($c === 'x') {
                $seed = $seed / (1 + rand() / $rand_max);
                $output  .= (string) dechex($seed % 16);
            } else {
                $output .= $c;
            }
        }
        return $output;
    }
}