<?php

namespace Chess;

class Position {
    public function __construct(public $row, public $col)
    {
        
    }

    public function subtract(Position $dest) : Position
    {
        $this->row -= $dest->row;
        $this->col -= $dest->col;
        return $this;
    }
}