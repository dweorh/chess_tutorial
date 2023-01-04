<?php
class ChessEngine {
    protected $game_board = [];
    protected $board = [
        'r', 'n', 'b', 'k', 'q', 'b', 'n', 'r',
        'p', 'p', 'p', 'p', 'p', 'p', 'p', 'p', // player 2
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', // player 1
        'R', 'N', 'B', 'K', 'Q', 'B', 'N', 'R'
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
        $this->game_board = $this->board;
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

    public function movement(ChessMove $move) : void
    {
        $this->game_board[(8 - $move->from_row) * 8 + $move->from_col] = ' ';
        $this->game_board[(8 - $move->to_row) * 8 + $move->to_col] = $move->figure_from;
    }

    public static function valid_move_range($action) : bool
    {
        return preg_match('/^[a-hA-H][1-8]:[a-hA-H][1-8]$/', $action);
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
}