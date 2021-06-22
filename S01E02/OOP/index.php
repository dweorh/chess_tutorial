<?php

use Chess\Board;
use Chess\Figure;

require_once './Chess/Board.php';
require_once './Chess/Figure.php';
require_once './Chess/FigureKing.php';
require_once './Chess/Player.php';
require_once './Chess/Position.php';

$board = new Board;

$player1 = new Chess\Player(1, 'player1');
$player2 = new Chess\Player(2, 'player2');

$king1 = new Chess\Figures\King($player1, new Chess\Position(1, 5));
$king2 = new Chess\Figures\King($player2, new Chess\Position(8, 5));

$board->add_figure($king1);
$board->add_figure($king2);

var_dump($board);



// namespace Chess
// Board
// Figure
// Player