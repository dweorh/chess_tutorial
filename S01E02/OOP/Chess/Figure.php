<?php
namespace Chess\Figures;

abstract class Figure {

    public abstract function move(\Chess\Position $to);

    public function __construct(public \Chess\Player $player, public \Chess\Position $position)
    {
    }

    public function player() : \Chess\Player
    {
        return $this->player;
    }

    protected function validateMoveAsterix(\Chess\Position $to)
    {
        $_position = clone $this->position;
        $_position->subtract($to);
        return abs($_position->row) == 1 || abs($_position->col) == 1;
    }
}