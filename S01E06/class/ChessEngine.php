<?php
class ChessEngine {
    protected $game_board = [];
    protected $board = [
        'r', 'n', 'b', 'k', 'q', 'b', 'n', 'r',
        'p', 'p', 'p', 'p', 'p', 'p', 'p', 'p', // player 1
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        ' ', ' ', ' ', ' ', ' ', ' ', ' ', ' ',
        'P', 'P', 'P', 'P', 'P', 'P', 'P', 'P', // player 2
        'R', 'N', 'B', 'K', 'Q', 'B', 'N', 'R'
    ];

    protected static $moves = [
        'r' => ['+'],
        'k' => ['L'],
        'b' => ['X'],
        'k' => ['*'],
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
        $p1 = ord($move->figure_from) >= 97 ? 1 : -1;
        $valid = false;

        foreach($moves as $_move) {
            switch ($_move) {
                /* pawn */
                case 'i':
                        $valid = $valid || (
                            $move->from_col == $move->to_col && 
                            (
                                $move->from_row == $move->to_row + 1 * $p1 ||
                                $move->from_row == $move->to_row + 2 * $p1
                            )
                        );
                    break;
                case 'v':
                        $valid = $valid || (
                            $move->from_row == $move->to_row + 1 * $p1 && 
                            abs($move->from_col - $move->to_col) == 1
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