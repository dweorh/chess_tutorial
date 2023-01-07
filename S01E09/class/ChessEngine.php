<?php
namespace dweorh\Chess;
class ChessEngine {
    const MOVE_UNKNOWN = -1;
    const MOVE_STANDARD = 0;
    const MOVE_ATTACK = 1;
    const MOVE_FRIENDLY_FIRE = 2;
    const MOVE_ROADBLOCK = 3;
    const MOVE_ILLEGAL = 4;

    protected $player_1_turn;

    protected $player_1_castling_figures;
    protected $player_2_castling_figures;

    protected $game_board = [];
    protected $board = [
        'r', 'n', 'b', 'q', 'k', 'b', 'n', 'r',
        'p', 'p', 'p', 'p', 'p', 'p', 'p', 'p', // player 2
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', // player 1
        'R', 'N', 'B', 'Q', 'K', 'B', 'N', 'R'
    ];

    protected static $moves = [
        'r' => ['+'],
        'n' => ['L'],
        'b' => ['X'],
        'k' => ['*','%'],
        'q' => ['+', 'X'],
        'p' => ['i', 'v']
    ];

    public function __construct()
    {
        $this->reset();
    }

    public function reset() : ChessEngine {
        $this->game_board = $this->board;
        $this->player_1_turn = true;

        $this->player_1_castling_figures = [ true, true, true ]; // rock, king, rock
        $this->player_2_castling_figures = [ true, true, true ];
        return $this;
    }

    public function get_game_board() : array
    {
        return $this->game_board;
    }

    public static function valid_move_axis(ChessMove $move) : bool
    {
        $moves = self::$moves[strtolower($move->figure_from)];
        $direction = $move->player1 ? -1 : 1;
        $valid = false;

        foreach($moves as $_move) {
            switch ($_move) {
                case '+': 
                    $valid = $valid || (
                        ($move->from_col == $move->to_col && $move->from_row != $move->to_row)
                        || ($move->from_col != $move->to_col && $move->from_row == $move->to_row)
                    );
                    break;
                case 'L':
                    $y = abs($move->to_row - $move->from_row);
                    $x = abs($move->to_col - $move->from_col);
                    $valid = $valid || (
                        ($y == 2 && $x == 1)
                        || ($y == 1 && $x == 2)
                    );
                    break;
                case 'X': 
                    $valid = $valid || (
                        ($move->from_row != $move->to_row && $move->from_col != $move->to_col) && (
                            ($move->from_row - $move->from_col == $move->to_row - $move->to_col) ||
                            ($move->from_row + $move->from_col == $move->to_row + $move->to_col)
                        )
                    );
                    break;
                /* king */
                case '*':
                    $row_move = $move->to_row - $move->from_row;
                    $col_move = $move->to_col - $move->from_col;
                    $valid = $valid || (
                        $row_move >= -1 && $row_move <= 1 && $col_move >= -1 && $col_move <= 1 && ($row_move != 0 || $col_move != 0)
                    );
                    break;
                case '%': // castling: long | short
                    $row_origin = $move->player1 ? 1 : 8;
                    $col_origin = 3;

                    $valid = $valid || (
                        $move->from_row == $move->to_row && $move->from_row == $row_origin &&
                        $move->from_col == $col_origin && abs($col_origin - $move->to_col) == 2
                    );
                    break;
                /* pawn */
                case 'i':
                        $valid = $valid || (
                            $move->from_col == $move->to_col && 
                            (
                                $move->from_row == $move->to_row + 1 * $direction ||
                                $move->from_row == $move->to_row + 2 * $direction
                            )
                        );
                    break;
                case 'v':
                        $valid = $valid || (
                            $move->from_row == $move->to_row + 1 * $direction && 
                            abs($move->to_col - $move->from_col) == 1
                        );
                    break;
            }
        }

        return $valid;
    }

    public function is_player_1_turn() : bool
    {
        return $this->player_1_turn;
    }

    public function is_current_player_king_in_check()
    {
        $figure_to = $this->player_1_turn ? 'K' : 'k';
        return $this->is_king_in_check($figure_to);
    }

    public function is_king_in_check($king)
    {
        $king_pos = $this->find_king($king);
        $move = new ChessMove(0, 0, $king_pos['col'], $king_pos['row'], '', $king);
        foreach($this->game_board as $pos => $figure) {
            if ( $figure == ' ' || 
                ($this->player_1_turn && ord($figure) <= 90) ||
                (!$this->player_1_turn && ord($figure) >= 97) ) 
            {
                continue;
            }
            $move->from_col = $pos % 8;
            $move->from_row = 8 - (int) floor($pos / 8);
            $move->figure_from = $figure;
            $move->player1 = ord($figure) <= 90;

            $valid_axis = self::valid_move_axis($move);
            if ($valid_axis) {
                $collision = $this->collisions($move);
                if ($collision === self::MOVE_ATTACK) {
                    return true;
                }
            }
        }
        return false;
    }

    public function movement(ChessMove $move) : void
    {
        $this->game_board[(8 - $move->from_row) * 8 + $move->from_col] = ' ';
        $this->game_board[(8 - $move->to_row) * 8 + $move->to_col] = $move->figure_from;
    }

    public static function valid_move_range($action) : bool
    {
        return preg_match('/^[a-hA-H][1-8]:[a-hA-H][1-8]$/', $action);
    }

    public function collisions(ChessMove $move) : int
    {
        $moves = self::$moves[strtolower($move->figure_from)];
        $start = (8 - $move->from_row) * 8 + $move->from_col;
        $end = (8 - $move->to_row) * 8 + $move->to_col;
        foreach($moves as $_move) {
            switch ($_move) {
                case '+': 
                    if ($move->from_col != $move->to_col && $move->from_row != $move->to_row) {
                        break;
                    }
                    $step = $move->from_col == $move->to_col ? 8 : 1;
                    if ($start < $end) {
                        for ($i = $start + $step; $i < $end; $i += $step) {
                            $figure = $this->game_board[$i];
                            if ($figure == ' ') {
                                continue;
                            }
                            return (!$move->player1  && ord($figure) >= 97) || ($move->player1 && ord($figure) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ROADBLOCK;
                        }
                    } else {
                        for ($i = $start - $step; $end < $i; $i -= $step) {
                            $figure = $this->game_board[$i];
                            if ($figure == ' ') {
                                continue;
                            }
                            return (!$move->player1 && ord($figure) >= 97) || ($move->player1 && ord($figure) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ROADBLOCK;
                        }
                    }
                    return $move->figure_to == ' ' ? self::MOVE_STANDARD : (
                        (!$move->player1 && ord($move->figure_to) >= 97) || ($move->player1 && ord($move->figure_to) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ATTACK
                    );
                break;
                case 'L': 
                    return $move->figure_to == ' ' ? self::MOVE_STANDARD : (
                        (!$move->player1 && ord($move->figure_to) >= 97) || ($move->player1 && ord($move->figure_to) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ATTACK
                    );
                break;
                case 'X': 
                    if ($move->from_col == $move->to_col || $move->from_row == $move->to_row) {
                        break;
                    }
                    if ($start < $end) {
                        $step = $move->from_col > $move->to_col ? 7 : 9;
                        for ($i = $start + $step; $i < $end; $i += $step) {
                            $figure = $this->game_board[$i];
                            if ($figure == ' ') {
                                continue;
                            }
                            return (!$move->player1 && ord($figure) >= 97) || ($move->player1 && ord($figure) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ROADBLOCK;
                        }
                    } else {
                        $step = $move->from_col > $move->to_col ? 9 : 7;
                        for ($i = $start - $step; $end < $i; $i -= $step) {
                            $figure = $this->game_board[$i];
                            if ($figure == ' ') {
                                continue;
                            }

                            return (!$move->player1 && ord($figure) >= 97) || ($move->player1 && ord($figure) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ROADBLOCK;
                        }
                    }

                    return $move->figure_to == ' ' ? self::MOVE_STANDARD : (
                        (!$move->player1 && ord($move->figure_to) >= 97) || ($move->player1 && ord($move->figure_to) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ATTACK
                    );
                    break;
                /* the king */
                case '*':
                    if (abs($move->from_col - $move->to_col) > 1) {
                        break;
                    }
                    return $move->figure_to == ' ' ? self::MOVE_STANDARD : (
                        (!$move->player1 && ord($move->figure_to) >= 97) || ($move->player1 && ord($move->figure_to) < 97) ? self::MOVE_FRIENDLY_FIRE : self::MOVE_ATTACK
                    );
                case '%':
                    if (abs($move->from_col - $move->to_col) != 2) {
                        break;
                    }
                    $_start = $start;
                    $_end = $end;
                    if ($start > $end) {
                        $_start = $end;
                        $_end = $start;
                    }
                    for($i = $_start; $i <= $_end; $i++) {
                        $figure = $this->game_board[$i];
                        if ($figure == ' ' || $figure == $move->figure_from) {
                            continue;
                        }
                        return self::MOVE_ILLEGAL;
                    }

                    $game_board_copy = $this->game_board;
                    $valid = true;
                    for($i = $_start; $i <= $_end; $i++) {
                        $this->game_board[$i] = $move->figure_from;
                        if ($this->is_current_player_king_in_check()) {
                            $valid = false;
                            break;
                        }
                    }
                    
                    $this->game_board = $game_board_copy;
                    if ($valid) {
                        $move->castling = $start < $end ? -1 : 1;
                    }
                    return $valid ? self::MOVE_STANDARD : self::MOVE_ILLEGAL;
                /* the pawn moves */
                case 'i': // forward
                    if ($move->from_col != $move->to_col) {
                        break;
                    }
                    if (($move->player1 && $move->from_row <> 2 && $move->to_row - $move->from_row > 1) ||
                        (!$move->player1 && $move->from_row <> 7 && $move->from_row - $move->to_row > 1)
                        ) {
                            return self::MOVE_ILLEGAL;
                    }
                    return $move->figure_to == ' ' ? self::MOVE_STANDARD : self::MOVE_ILLEGAL;
                case 'v': 
                    if ($move->from_col == $move->to_col) {
                        break;
                    }
                    return (!$move->player1 && ord($move->figure_to) >= 97) || 
                            ($move->player1 && ord($move->figure_to) < 97) || 
                            $move->figure_to == ' ' ? self::MOVE_ILLEGAL : self::MOVE_ATTACK;
            }
        }
        return self::MOVE_UNKNOWN;
    }

    public function move_details(string $action) : ?ChessMove
    {
        if(!self::valid_move_range($action)) {
            return null;
        }
        list($from, $to) = explode(':', $action);

        $from = strtolower($from);
        $to = strtolower($to);
        $from_col = ord($from[0]) - 97; // 'a1' $from[0] == 'a' $from[1] == '1'
        $from_row = $from[1];
        $to_col = ord($to[0]) - 97;
        $to_row = $to[1];
        
        $figure_from = $this->game_board[(8 - $from_row) * 8 + $from_col];
        $figure_to = $this->game_board[(8 - $to_row) * 8 + $to_col];

        return new ChessMove($from_col, $from_row, $to_col, $to_row, $figure_from, $figure_to);
    }

    public function move(string $action): ?ChessMove
    {
        $move = $this->move_details($action);
        if(!$move) {
            return null;
        }

        $move->correct_player_moves = $move->player1 == $this->player_1_turn;
        if(!$move->correct_player_moves) {
            return $move;
        }

        $move->valid_axis = self::valid_move_axis($move);
        if($move->valid_axis) {
            $move->collision = $this->collisions($move);
            if($move->collision == self::MOVE_STANDARD || $move->collision == self::MOVE_ATTACK) {
                $game_board_copy = $this->game_board;
                $this->game_board[(8 - $move->from_row) * 8 + $move->from_col] = ' ';
                $this->game_board[(8 - $move->to_row) * 8 + $move->to_col] = $move->figure_from;
                if ($this->is_current_player_king_in_check()) {
                    $move->king_in_check = true;
                    $this->game_board = $game_board_copy;
                } else {
                    $valid = $move->castling === 0 || ($move->castling !== 0 && $this->do_castling($move));
                    if ($valid) {
                        if (strtolower($move->figure_from) == 'r') {
                            $side = $move->from_col == 1 ? -1 : 1;
                            if ($move->player1) {
                                $this->player_1_castling_figures[1 + $side] = false;
                            } else {
                                $this->player_2_castling_figures[1 + $side] = false;
                            }
                        }

                        if (strtolower($move->figure_from) == 'k') {
                            if ($move->player1) {
                                $this->player_1_castling_figures[1] = false;
                            } else {
                                $this->player_2_castling_figures[1] = false;
                            }
                        }
                        $this->player_1_turn = !$this->player_1_turn;
                    } else {
                        $this->game_board = $game_board_copy;
                        $move->collision = self::MOVE_ILLEGAL;
                    }
                }
            }
        }
        return $move;
    }

    protected function do_castling(ChessMove $move) : bool
    {
        $side = $move->castling;
        if ($move->player1) {
            if (!$this->player_1_castling_figures[1] || !$this->player_1_castling_figures[ 1 + $side]){
                return false;
            }
        } else if (!$this->player_2_castling_figures[1] || !$this->player_2_castling_figures[ 1 + $side]){
            return false;
        }

        if ($move->player1) {
            $this->player_1_castling_figures = [false, false, false];
            $i = $side < 0 ? 63 : 56;
            $this->game_board[$i] = ' ';
            $this->game_board[(8 - $move->to_row) * 8 + $move->to_col + $side ] = 'R';
        } else {
            $this->player_2_castling_figures = [false, false, false];
            $i = $side < 0 ? 7 : 0;
            $this->game_board[$i] = ' ';
            $this->game_board[(8 - $move->to_row) * 8 + $move->to_col + $side ] = 'r';
        }
        return true;
    }

    protected function find_king($king) : array
    {
        $pos = array_search($king, $this->game_board, true);
        
        if ($pos === false) {
            throw new \Exception('King not found');
        }
        
        return ['row' => 8 - (int)floor($pos / 8), 'col' => $pos % 8];
    }
}