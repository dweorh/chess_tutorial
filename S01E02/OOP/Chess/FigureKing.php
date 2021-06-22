<?php
namespace Chess\Figures;

class King extends Figure {

    public function move (\Chess\Position $to) {
        if ( $this->validateMoveAsterix($to) ) {
            $this->position = $to;
        }
    }
}