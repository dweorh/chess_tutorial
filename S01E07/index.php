<?php

require_once './class/ChessEngine.php';
require_once './class/ChessMove.php';

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

function perform_action(ChessEngine $game, $action){
    if ($action == 'exit') {
        return 0;
    }

    $move_details = $game->move_details($action);
    if(!empty($move_details)) {
        if(ChessEngine::valid_move_axis($move_details)) {
            $game->movement($move_details);
        } else {
            return 'Wrong move: ' . $move_details->figure_from . ' -> ' . $action;
        }
    } else {
        return 'Invalid move: ' . $action;
    }
}

$game = new ChessEngine;


echo print_board($game->get_game_board());

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
            $result = perform_action($game, $action);
            if ($result === 0) {
                $exit = true;
                $result = '';
            } else {
                echo "\e[0G\e[K";
                echo "\e[26A";
                echo print_board($game->get_game_board());
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