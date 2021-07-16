<?php
class ChessMove {
    public $from_col;
    public $from_row;
    public $to_col;
    public $to_row;
    public $figure_from;
    public $figure_to;

    public function __construct(int $from_col, int $from_row, int $to_col, int $to_row, string $figure_from, string $figure_to)
    {
        $this->from_col = $from_col;
        $this->from_row = $from_row;
        $this->to_col = $to_col;
        $this->to_row = $to_row;
        $this->figure_from = $figure_from;
        $this->figure_to = $figure_to;
    }
}