<?php

require_once './class/ChessEngine.php';
require_once './class/ChessLocal.php';
require_once './class/ChessMove.php';
require_once './class/ChessPlayer.php';
require_once './class/Generators.php';

use dweorh\Chess\ChessEngine;
use dweorh\Chess\ChessLocal;
use dweorh\Chess\ChessPlayer;
const SCREEN_HEIGHT = 26;

$game = new ChessEngine;
$local = new ChessLocal;

$local->load_players();

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
function print_board(array $board) : string
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

function clear_line() {
    echo "\e[0G\e[K";
}

function clear_screen() {
    echo "\e[" . SCREEN_HEIGHT . "A";
    for($i = 0; $i <= SCREEN_HEIGHT; $i++) {
        echo "\e[1B";
        clear_line();
    } 
}

function perform_action(ChessEngine $game, $action){
    $message = '';
    $move = $game->move($action);
    if($move) {
        if (!$move->correct_player_moves) {
            $message = 'Wrong player moves!';
        } else if ($move->valid_axis) {
            if ($move->king_in_check) {
                $message = 'The King in check! ' . $move->figure_from . ' -> ' . $action;
            } else if ($move->collision == ChessEngine::MOVE_UNKNOWN) {
                $message = 'Unknow move! ' . $move->figure_from . ' -> ' . $action;
            } else if ($move->collision == ChessEngine::MOVE_FRIENDLY_FIRE) {
                $message = 'Friendly fire! ' . $move->figure_from . ' -> ' . $action;
            } else if ($move->collision == ChessEngine::MOVE_ILLEGAL) {
                $message = 'Illegal move! ' . $move->figure_from . ' -> ' . $action;
            } else if ($move->collision == ChessEngine::MOVE_ROADBLOCK) {
                $message = 'Roadblock! ' . $move->figure_from . ' -> ' . $action;
            } else {
                $message = 'Good move!';
            }
        } else {
            $message = 'Wrong move: ' . $move->figure_from . ' -> ' . $action;
        }
    }
    return empty($message) ? 100 : $message;
}

function menu(ChessLocal $local, string $action) {
    $cmd = explode(':', $action);
    $messages = [];
    switch($cmd[0]) {
        case 'exit':
            return 0;
        case 'board':
            clear_line();
            echo "\e[0G\e[1A\e[K";
            clear_screen();
            return 200;
            break;
        case 'new_player':
            $name = empty($cmd[1]) ? 'Local Player' : $cmd[1];
            $local->new_player(new ChessPlayer($name));
            $local->save_players();
            $messages[SCREEN_HEIGHT-1] =  'New Player `' . $name . '` created.';
            break;
        case 'local_players':
            $players = $local->list_players();
            if (!empty($players)) {
                $idx = SCREEN_HEIGHT - sizeof($players) - 1;
                if( $idx < 0) {
                    $idx = 0;
                }
                foreach($players as $player) {
                    $messages[$idx] = $player->id() . ' ' . $player->name()
                        . ($local->current_player() && $local->current_player()->id() == $player->id() ? '*' : '');
                    $idx++;
                    if ($idx >= SCREEN_HEIGHT) {
                        break;
                    }
                }
            } else {
                $messages[SCREEN_HEIGHT - 3] = 'No local players.';
            }
            break;
        case 'menu':
        case 'help':
        default:
            $messages[0] = 'Welcome to The Chess Game';
            $messages[SCREEN_HEIGHT-20] = 'game:';
            $messages[SCREEN_HEIGHT-19] = 'a1:a3 - move figure from A1 to A3';
            
            $messages[SCREEN_HEIGHT-11] = 'commands:';
            $messages[SCREEN_HEIGHT-10] = 'board - show the board';
            $messages[SCREEN_HEIGHT-9] = 'new_player:<player_name> - create a new player';
            $messages[SCREEN_HEIGHT-8] = 'local_players - list local players';

            $messages[SCREEN_HEIGHT-7] = 'exit - exit from The Chess Game';

            break;
    }
    if (!empty($messages)) {
        /* clear the screen */
        clear_line();
        echo "\e[0G\e[1A\e[K";
        
        clear_screen();

        echo "\e[" . SCREEN_HEIGHT . "A";
        for($i = 0; $i <= SCREEN_HEIGHT-1; $i++) {
            if (!empty($messages[$i])) {
                echo $messages[$i];
            }
            echo "\e[1B\e[0G";
        }
        return 102;
    }
    return 100;
}

$stdin = fopen('php://stdin', 'r');
stream_set_blocking($stdin, 0);

$stty_config = shell_exec('stty -g');

system('stty cbreak -echo');

$action = '';
$result = '';
$is_menu = true;
menu($local, 'help');
echo "\e[0G*menu action: " . $action;
$exit = false;
while(!$exit) {
    $keypress = fgetc($stdin);
    if ($keypress) {
        $result = '';
        if (ord($keypress) == 10) {
            try {
                $result = perform_action($game, $action);
            } catch (Exception $e) {
                $result = $e->getMessage();
            }

            if ($result === 100) {
                $is_menu = true;
                $result = menu($local, $action);
                if ($result === 0) {
                    $exit = true;
                    $result = '';
                } else if ($result != 100) {
                    $action = '';
                    $keypress = '';
                }
                if ($result >= 200) {
                    $is_menu = false;
                }
            }

            if (!$is_menu) {
                if (is_numeric($result)) {
                    $result = '';
                }
                /* clear the screen */
                clear_line();
                echo "\e[" . SCREEN_HEIGHT . "A";
                echo print_board($game->get_game_board());
                $action = '';
                $keypress = '';
            }
        }

        if (ord($keypress) == 127){ // backpace
            clear_line();
            $action = substr($action, 0, -1);
            $keypress = '';
        }

        clear_line();
        $action .= $keypress;
        if (!$is_menu) {
            echo "\e[0Gaction [". ( $game->is_player_1_turn() ? 
            ( $local->current_player()?->name() ?? 'P1' ) : 'P2' ) .
                "]> " . $action . $result;
        } else {
            echo "\e[0G*menu action: " . $action;
        }
    }
}

system('stty ' . $stty_config);