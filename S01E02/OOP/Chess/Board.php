<?php
namespace Chess;

class Board {
    protected $figures = [];

    public function add_figure(Figures\Figure $figure) {
        $this->figures[] = $figure;
    }
}