<?php
spl_autoload_register(function ($class) {
    $class_path = str_replace('\\', '/', $class);
    require __DIR__ . '/class/' . $class_path . '.php';
});

use dweorh\Chess\ChessEngine;
use dweorh\Chess\ChessLocal;
use dweorh\Chess\ChessLocalErrorCodes;

const SCREEN_HEIGHT = 26;

$local = new ChessLocal;

$local->load_players();
$local->load_games();


require './menu.php';
require './game.php';
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
    $keypress = fgets($stdin);
    if ($keypress) {
        $result = '';
        if (ord($keypress) == 10) {
            try {
                $result = perform_action($local, $action);

                if (!ChessLocalErrorCodes::PASS->equals($result) && $local->current_game()) {
                    if ($local->current_game()->winner()) {
                        $result = 'Winner is: '. $local->current_game()->winner()->name();
                    } else if (!$local->current_game()->current_player()) {
                        if (ChessEngine::valid_move_range($action)) {
                            $result = 'Export the game, no second player!';
                        } else {
                            $result = ChessLocalErrorCodes::PASS;
                        }
                    }
                }

            } catch (Exception $e) {
                $result = $e->getMessage();
            }

            if (ChessLocalErrorCodes::PASS->equals($result)) {
                $is_menu = true;
                $result = menu($local, $action);
                if (ChessLocalErrorCodes::EXIT->equals($result)) {
                    $exit = true;
                    $result = '';
                } else if (!ChessLocalErrorCodes::PASS->equals($result)) {
                    $action = '';
                    $keypress = '';
                }
                if (ChessLocalErrorCodes::BOARD_ACTION->lte($result)) {
                    $is_menu = false;
                }
            }

            if(!$local->current_game() && !$is_menu) {
                $is_menu = true;
            }

            if (!$is_menu) {
                if (is_numeric($result) || $result instanceof ChessLocalErrorCodes) {
                    $result = '';
                }
                /* clear the screen */
                clear_line();
                echo "\e[" . SCREEN_HEIGHT . "A";
                echo print_board($local->current_game()?->get_game_board());
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
            echo "\e[0Gaction [".
                $local->current_game()->current_player() .
                ($local->current_game()->is_current_player_king_in_check() ? '*' : '') .
                "]> " . $action . ($result instanceof ChessLocalErrorCodes ? $result->print() : $result);
        } else {
            echo "\e[0G*menu action: " . $action;
        }
    }
}

system('stty ' . $stty_config);