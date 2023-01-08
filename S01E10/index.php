<?php

require_once './class/ChessEngine.php';
require_once './class/ChessGame.php';
require_once './class/ChessLocal.php';
require_once './class/ChessMove.php';
require_once './class/ChessPlayer.php';
require_once './class/Encryption.php';
require_once './class/Generators.php';

use dweorh\Chess\ChessEngine;
use dweorh\Chess\ChessLocal;
use dweorh\Chess\ChessPlayer;
const SCREEN_HEIGHT = 26;

$local = new ChessLocal;

$local->load_players();
$local->load_games();

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

function perform_action(ChessLocal $local, $action){
    if (!$local->current_game()) {
        return 100;
    }

    if (!$local->current_game()->current_player()) {
        return 103;
    }
    
    $game = $local->current_game();

    $cmd = explode(':', $action);
    $message = '';
    switch($cmd[0]) {
        case 'resign':
            $game->resign();
            return 1;
        case 'mate': 
            try{
                $game->mate();
                return 2;
            } catch (Exception $e) {
                $message = $e->getMessage();
            }
    }

    if (!$game->winner()) {
        $move = $game->move($action);
        if($move) {
            $start = (8 - $move->from_row) * 8 + $move->from_col;
            $end = (8 - $move->to_row) * 8 + $move->to_col;
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
                    $message = '`' . strtolower($move->figure_from) . ';' . $start . ':' . $end . '`';
                }
            } else {
                $message = 'Wrong move: ' . $move->figure_from . ' -> ' . $action;
            }
        }
    }
    return empty($message) ? 100 : $message;
}

function menu(ChessLocal &$local, string $action) {
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
        case 'new_game':       
            if (!$local->current_game() || $local->current_game()->winner()) {
                switch(sizeof($cmd)){
                    case 1: // no params
                        $player_1_id = $local->current_player() ? $local->current_player()->id() : '';
                        $player_1 = $local->find_player($player_1_id);
                        if($player_1) {
                            $local->new_game()
                                ->set_player_1($player_1);
                            $messages[SCREEN_HEIGHT-3] = 'New game for `' . $player_1->name() . '` created.';    
                        } else {
                            $messages[SCREEN_HEIGHT-3] = 'Default player not found.';    
                        }
                        break;
                    case 2: // one player id provided
                        $player_1 = $local->find_player($cmd[1]);
                        if($player_1) {
                            $local->new_game()
                                ->set_player_1($player_1);
                            $messages[SCREEN_HEIGHT-3] = 'New game for `' . $player_1->name() . '` created.';
                        } else {
                            $messages[SCREEN_HEIGHT-3] = 'Player `' . $cmd[1] . '` not found.';    
                        }
                        break;
                    case 3: // two player ids provided
                        $player_1 = $local->find_player($cmd[1]);
                        $player_2 = $local->find_player($cmd[2]);
                        if($player_1 && $player_2) {
                            $local->new_game()
                                ->set_player_1($player_1)
                                ->set_player_2($player_2);
                            $messages[SCREEN_HEIGHT-3] = 'New game for `' . $player_1->name() . '` and `' . $player_2->name() . '` created.';
                        } else {
                            $messages[SCREEN_HEIGHT-4] = 'Player `' . $cmd[1] . '` ' . ($player_1 ? '' : 'not '). 'found.';    
                            $messages[SCREEN_HEIGHT-3] = 'Player `' . $cmd[2] . '` ' . ($player_2 ? '' : 'not '). 'found.';       
                        }
                        break;
                    default:
                        $messages[SCREEN_HEIGHT-3] = 'You need to provide 0-2 player ids.';

                }
            } else {
                $messages[SCREEN_HEIGHT-3] = 'Finish the current game first.';
            }
            break;
        case 'missing_player':
            if ($local->current_game()) {
                if($local->current_game()->player_1() && $local->current_game()->player_2()) {
                    $messages[SCREEN_HEIGHT-3] = 'Both players already set.';
                } else {
                    $player = empty($cmd[1]) ? $local->current_player() : $local->find_player($cmd[1]);
                    if ($player) {
                        if (!$local->current_game()->player_1()) {
                            $local->current_game()->set_player_1($player);
                            $messages[SCREEN_HEIGHT-3] = 'Player 1 one set as `'.$player->name().'`.';
                        } else if (!$local->current_game()->player_2()) {
                            $local->current_game()->set_player_2($player);
                            $messages[SCREEN_HEIGHT-3] = 'Player 2 one set as `'.$player->name().'`.';
                        }
                    } else {
                        $messages[SCREEN_HEIGHT-3] = 'Player not found.';
                    }
                }
            } else {
                $messages[SCREEN_HEIGHT-3] = 'No running game.';
            }
            break;
        case 'load_game':
            if (empty($cmd[1])) {
                $messages[SCREEN_HEIGHT-3] = 'Provide the game id to load.';
            } else {
                if ($local->load_game($cmd[1]) ) {
                    $messages[SCREEN_HEIGHT-3] = 'Game loaded.';
                } else {
                    $messages[SCREEN_HEIGHT-3] = 'Game could not be loaded.';
                }
            }
            break;
        case 'export_game':
            $game = $local->current_game();
            if (!$game) {
                $messages[SCREEN_HEIGHT-3] = 'No running game to export.';
            } else {
                $local->export_current_game();
                $local->save_games();
                $messages[SCREEN_HEIGHT-3] = 'Current game exported.';
            }
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
        case 'local_games':
            $games = $local->list_games();
            if (!empty($games)) {
                $idx = SCREEN_HEIGHT - sizeof($games) - 1;
                if( $idx < 0) {
                    $idx = 0;
                }
                foreach($games as $game) {
                    $messages[$idx] = $game->id();
                    $messages[$idx] .= $game->id() == $local->current_game()?->id() ? '*' : '';
                    $idx++;
                    if ($idx >= SCREEN_HEIGHT) {
                        break;
                    }
                }
            } else {
                $messages[SCREEN_HEIGHT - 3] = 'No local games.';
            }
            break;
        case 'menu':
        case 'help':
        default:
            $messages[0] = 'Welcome to The Chess Game';
            $messages[SCREEN_HEIGHT-20] = 'game:';
            $messages[SCREEN_HEIGHT-19] = 'a1:a3 - move figure from A1 to A3';
            $messages[SCREEN_HEIGHT-18] = 'mate - confirm check when your king is in check';
            $messages[SCREEN_HEIGHT-17] = 'resign - end the current game as a loser.';
            
            $messages[SCREEN_HEIGHT-11] = 'commands:';
            $messages[SCREEN_HEIGHT-10] = 'board - show the board';
            $messages[SCREEN_HEIGHT-9] = 'new_player:<player_name> - create a new player';
            $messages[SCREEN_HEIGHT-8] = 'new_game[:<player 1 id>[:<player 2 id>]] - create a new game for <player 1 id> and <player 2 id>';
            $messages[SCREEN_HEIGHT-7] = 'load_game:<game id> - load selected game';
            $messages[SCREEN_HEIGHT-6] = 'missing_player[:<player id>] - set missing player in the current game';
            $messages[SCREEN_HEIGHT-5] = 'local_players - list local players';
            $messages[SCREEN_HEIGHT-4] = 'local_games - list local games';
            $messages[SCREEN_HEIGHT-3] = 'export_game - export the current game';

            $messages[SCREEN_HEIGHT-2] = 'exit - exit from The Chess Game';

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
    $keypress = fgets($stdin);
    if ($keypress) {
        $result = '';
        if (ord($keypress) == 10) {
            try {
                $result = perform_action($local, $action);

                if ($result != 100 && $local->current_game()) {
                    if ($local->current_game()->winner()) {
                        $result = 'Winner is: '. $local->current_game()->winner()->name();
                    } else if (!$local->current_game()->current_player()) {
                        if (ChessEngine::valid_move_range($action)) {
                            $result = 'Export the game, no second player!';
                        } else {
                            $result = 100;
                        }
                    }
                }

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

            if(!$local->current_game() && !$is_menu) {
                $is_menu = true;
            }

            if (!$is_menu) {
                if (is_numeric($result)) {
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
                "]> " . $action . $result;
        } else {
            echo "\e[0G*menu action: " . $action;
        }
    }
}

system('stty ' . $stty_config);