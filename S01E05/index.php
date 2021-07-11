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

[40G = Move to column 40 of current line
[A = Move up one line, stop at top of screen, [9A = move up 9
[K = [0K = Erase from current position to end (inclusive)
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


function movement(array &$board, int $from_col, int $from_row, int $to_col, int $to_row, string $figure_from) : void
{
    $board[(8 - $from_row) * 8 + $from_col] = ' ';
    $board[(8 - $to_row) * 8 + $to_col] = $figure_from;
}

function valid_move_range($action) : bool
{
    return preg_match('/^[a-hA-H][1-8]:[a-hA-H][1-8]$/', $action);
}

function move_details(array &$board, string $action) : array
{
    if(!valid_move_range($action)) {
        return [];
    }
    list($from, $to) = explode(':', $action);

    $from = strtolower($from);
    $to = strtolower($to);
    $from_col = ord($from[0]) - 97; // 'a1' $from[0] == 'a' $from[1] == '1'
    $from_row = $from[1];
    $to_col = ord($to[0]) - 97;
    $to_row = $to[1];
    
    $figure_from = $board[(8 - $from_row) * 8 + $from_col];
    $figure_to = $board[(8 - $to_row) * 8 + $to_col];
    return [
        'from_col' => $from_col,
        'from_row' => $from_row,
        'to_col' => $to_col,
        'to_row' => $to_row,
        'figure_from' => $figure_from,
        'figure_to' => $figure_to
    ];
}

function perform_action(&$board, $action){
    if ($action == 'exit') {
        return 0;
    }

    $move_details = move_details($board, $action);
    if(!empty($move_details)) {
        movement($board, $move_details['from_col'], $move_details['from_row'], $move_details['to_col'], $move_details['to_row'], $move_details['figure_from']);
    } else {
        return 'Invalid move: ' . $action;
    }
}



echo print_board($board);

$stdin = fopen('php://stdin', 'r');
stream_set_blocking($stdin, 0);

$stty_config = shell_exec('stty -g');

system('stty cbreak -echo');

$action = '';
echo "\e[0Gaction > " . $action;
$exit = false;
while(!$exit) {
    $keypress = fgetc($stdin);
    if ($keypress) {
        $result = '';
        if (ord($keypress) == 10) {
            $result = perform_action($board, $action);
            if ($result === 0) {
                $exit = true;
                $result = '';
            } else {
                echo "\e[0G\e[K";
                echo "\e[26A";
                echo print_board($board);
                $action = '';
                $keypress = '';
            }
        }

        if (ord($keypress) == 127){
            echo "\e[0G\e[K";
            $action = substr($action, 0, -1);
            $keypress = '';
        }

        echo "\e[0G\e[K";
        $action .= $keypress;
        echo "\e[0Gaction > " . $action . $result;
    }
}

system('stty ' . $stty_config);