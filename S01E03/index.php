<?php

$board = [
    'r', 'n', 'b', 'k', 'q', 'b', 'n', 'r',
    'p', 'p', 'p', 'p', 'p', 'p', 'p', 'p', // player 1
    ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
    ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
    ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
    ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
    'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', // player 2
    'R', 'N', 'B', 'K', 'Q', 'B', 'N', 'R'
];

/*
http://www.inwap.com/pdp10/ansicode.txt
\e equals chr(27)
[0m = Clear all special attributes
[30m = Write with black
[37m = Write with white
[40m = Set background to black (GIGI)
[46m = Set background to cyan
[47m = Set background to white
*/
function print_board($board)
{
    $patterns = [
        ["\e[0;30;47m %s \e[0m", "\e[0;37;40m %s \e[0m"],
        ["\e[0;37;40m %s \e[0m", "\e[0;30;47m %s \e[0m"]
    ];

    $output = '';
    $output .= sprintf("\e[0;46m%s\e[0m\n", "  A  B  C  D  E  F  G  H  ");
    for($i = 0; $i < 64; $i += 8) {
        for($y = 0; $y < 3; $y++) {
            $output .= sprintf("\e[46m%s\e[0m", $y == 1 ? 8 - $i / 8 : ' ');
            for($x = $i; $x <= $i + 7; $x++) {
                $pattern = $x / 8 % 2 ? $patterns[0] : $patterns[1];
                $output .= sprintf($x % 2 ? $pattern[0] : $pattern[1], $y == 1 ? $board[$x] : ' ');
            }
            $output .= "\e[46m \e[0m";
            $output .= "\n";
        }
    }
    $output .= sprintf("\e[0;46m%s\e[0m\n", "  A  B  C  D  E  F  G  H  ");
    return $output;
}

echo print_board($board);