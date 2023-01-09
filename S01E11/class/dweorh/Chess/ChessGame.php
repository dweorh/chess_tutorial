<?php
namespace dweorh\Chess;

use Exception;

class ChessGame implements \JsonSerializable {
    protected ?ChessPlayer $player1 = null;
    protected ?ChessPlayer $player2 = null;
    protected ?ChessPlayer $winner = null;
    protected ChessEngine $engine;
    protected string $id;
    protected array $moves = [];


    public function __construct(string $id = null)
    {
        if (empty($id)) {
            $id = \dweorh\Utils\Generators::uuidv4();
        }
        $this->id = $id;
        $this->engine = new ChessEngine();
    }

    public function id() : string
    {
        return $this->id;
    }

    public function get_game_board() : array
    {
        return $this->engine->get_game_board();
    }

    public function set_game_board(array $board) : ChessGame
    {
        $this->engine->set_game_board($board);
        return $this;
    }

    public function set_player_1(ChessPlayer $player) : ChessGame
    {
        $this->player1 = $player;
        return $this;
    }

    public function set_player_2(ChessPlayer $player) : ChessGame
    {
        $this->player2 = $player;
        return $this;
    }

    public function set_castling_figures(array $player_1, array $player_2) : ChessGame
    {
        $this->engine->set_castling_figures($player_1, $player_2);
        return $this;
    }

    public function set_moves(array $moves) : ChessGame
    {
        $this->moves = $moves;
        return $this;
    }

    public function set_player_1_turn(bool $turn) : ChessGame
    {
        $this->engine->set_player_1_turn($turn);
        return $this;
    }

    public function player_1() : ChessPlayer
    {
        return $this->player1;
    }

    public function player_2(): ?ChessPlayer
    {
        return $this->player2;
    }

    public function current_player() : ?ChessPlayer 
    {
        return $this->engine->is_player_1_turn() ? $this->player1 : $this->player2;
    }

    public function opponent_player() : ?ChessPlayer 
    {
        return $this->engine->is_player_1_turn() ? $this->player2 : $this->player1;
    }

    public function winner() : ?ChessPlayer
    {
        return $this->winner;
    }

    public function resign()
    {
        $this->winner = $this->opponent_player();
    }

    public function mate()
    {
        if ($this->is_current_player_king_in_check()) {
            $this->winner = $this->opponent_player();
        } else {
            throw new Exception('King is not in check.');
        }
    }

    public function move(string $action) : ?ChessMove
    {
        if ($this->winner) {
            throw new Exception('Match already finished.');
        }

        $move = $this->engine->move($action);
        if ($move && ($move->collision == ChessEngine::MOVE_STANDARD || $move->collision == ChessEngine::MOVE_ATTACK)) {
            // do some game logic here
            $this->moves[] = $action;
        }
        return $move;
    }

    public function is_current_player_king_in_check()
    {
        return $this->engine->is_current_player_king_in_check();
    }

    public function export_data() : array
    {
        $data = [
            'id' => $this->id,
            'player_1' => $this->player1?->id(),
            'player_2' => $this->player2 ? $this->player2->id() : '',
            'moves' => $this->moves,
            'castling' => $this->engine->get_castling_figures(),
            'board' => $this->engine->get_game_board()
        ];

        $data['current_player'] = $this->engine->is_player_1_turn() ? $data['player_1'] : $data['player_2'];
        return $data;
    }

    public function jsonSerialize() : array
    {
        return $this->export_data();
    }
}