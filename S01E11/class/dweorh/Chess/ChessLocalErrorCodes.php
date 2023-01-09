<?php
namespace dweorh\Chess;

enum ChessLocalErrorCodes : int {
  case EXIT = 0;
  case RESIGN = 1;
  case MATE = 2;
  case PASS = 100;
  case MENU_ACTION_MESSAGE = 102;
  case MENU_ACTION_INCOMPLETE_GAME = 103;
  case BOARD_ACTION = 200;

  public function print() : string {
    return $this->value;
  }

  public function equals($value) : bool {
    return $value instanceof self ? $this === $value : $this->value === $value;
  }

  public function lt($value) : bool {
    return $value instanceof self ? $this->value < $value->value :$this->value < $value;
  }

  public function lte($value) : bool {
    return $value instanceof self ? $this->value <= $value->value :$this->value <= $value;
  }
}