<?php
use dweorh\Chess\ChessLocal;
use dweorh\Chess\ChessEngine;
use dweorh\Chess\ChessLocalErrorCodes;

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

function perform_action(ChessLocal $local, string $action) : ChessLocalErrorCodes | string{
  if (!$local->current_game()) {
      return ChessLocalErrorCodes::PASS;
  }

  if (!$local->current_game()->current_player()) {
      return ChessLocalErrorCodes::MENU_ACTION_INCOMPLETE_GAME;
  }
  
  $game = $local->current_game();

  $cmd = explode(':', $action);
  $message = '';
  switch($cmd[0]) {
      case 'resign':
          $game->resign();
          return ChessLocalErrorCodes::RESIGN;
      case 'mate': 
          try{
              $game->mate();
              return ChessLocalErrorCodes::MATE;
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
  return empty($message) ? ChessLocalErrorCodes::PASS : $message;
}