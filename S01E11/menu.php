<?php
use dweorh\Chess\ChessLocal;
use dweorh\Chess\ChessPlayer;
use dweorh\Chess\ChessLocalErrorCodes;

function menu(ChessLocal &$local, string $action) {
  $cmd = explode(':', $action);
  $messages = [];
  switch($cmd[0]) {
      case 'exit':
          return ChessLocalErrorCodes::EXIT;
      case 'board':
          clear_line();
          echo "\e[0G\e[1A\e[K";
          clear_screen();
          return ChessLocalErrorCodes::BOARD_ACTION;
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
      return ChessLocalErrorCodes::MENU_ACTION_MESSAGE;
  }
  return ChessLocalErrorCodes::PASS;
}
