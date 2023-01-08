<?php
namespace dweorh\Chess;

class ChessLocal {
    protected $file_players = 'players.json';
    protected $file_games = 'games.json';
    protected $single_game_file_pattern = 'game_%s.json';
    protected $players = [];
    protected $games = [];
    protected ?ChessPlayer $current_player = null;
    protected ?ChessGame $current_game = null;
    
    public function load_players() : ChessLocal
    {
        if (file_exists($this->file_players)) {
            $content = file_get_contents($this->file_players);
            $json = json_decode($content, true);
            if ($json) {
                foreach($json['players'] as $entry) {
                    $player = new ChessPlayer($entry['name'], $entry['id'], $entry['keys']['private'], $entry['keys']['public']);
                    $this->players[] = $player;
                    if ($player->id() == $json['default']) {
                        $this->current_player = $player;
                    }
                }
            }
        }
        return $this;
    }

    public function new_player($name) : ChessPlayer
    {
        $player = new ChessPlayer($name);
        $this->players[] = $player;
        if (sizeof($this->players) == 1) {
            $this->current_player = $player;
        }
        return $player;
    }

    public function save_players() : ChessLocal
    {
        $data = [
            'default' => $this->current_player?->id(),
            'players' => $this->players
        ];
        file_put_contents($this->file_players, json_encode($data));
        return $this;
    }
    
    public function list_players() : array
    {
        return $this->players;
    }
    
    public function current_player() : ?ChessPlayer
    {
        return $this->current_player;
    }

    public function set_current_player(ChessPlayer $player) : ChessLocal
    {
        $this->current_player = $player;
        return $this;
    }

    public function find_player(string $id) : ?ChessPlayer
    {
        foreach ($this->players as $player) {
            if($player->id() == $id)
            {
                return $player;
            }
        }
        return null;
    }

    public function load_game(string $id) : ?ChessGame
    {
        $file_name = sprintf($this->single_game_file_pattern, $id);
        if (file_exists($file_name)) {
            $content = file_get_contents($file_name);
            $data = json_decode($content, true);
            $game_data = $data['game'];
            $game = new ChessGame($game_data['id']);
            $game->set_game_board($game_data['board'])
                ->set_castling_figures($game_data['castling']['player_1'], $game_data['castling']['player_2'])
                ->set_moves($game_data['moves'])
                ->set_player_1_turn($game_data['current_player'] == $game_data['player_1']);

            $player_1 = $this->find_player($game_data['player_1']);
            $player_2 = $this->find_player($game_data['player_2']);

            if (!$player_1) {
                $player_1  = new ChessPlayer('Remote 1', $game_data['player_1']);
            }
            
            $game->set_player_1($player_1);

            if (!$player_2 && $game_data['player_2']) {
                $player_2  = new ChessPlayer('Remote 2', $game_data['player_2']);
            }

            if ($player_2) {
                $game->set_player_2($player_2);
            }
            
            $this->games[ $this->find_game_index($game->id()) ] = $game;
            $this->current_player = $game_data['current_player'] == $game_data['player_1'] ? $player_1 : $player_2;
            $this->current_game = $game;
            return $game;
        }
        return null;
    }

    public function find_game_index(string $id) : int
    {
        foreach($this->games as $idx => $game) {
            if ($game->id() === $id) {
                return $idx;
            }
        }
        return sizeof($this->games);
    }

    public function load_games() : ChessLocal
    {
        if (file_exists($this->file_games)) {
            $content = file_get_contents($this->file_games);
            $json = json_decode($content, true);
            if ($json) {
                foreach($json as $entry) {
                    $game = new ChessGame($entry['id']);
                    $game->set_game_board($entry['board'])
                        ->set_castling_figures($entry['castling']['player_1'], $entry['castling']['player_2'])
                        ->set_moves($entry['moves'])
                        ->set_player_1_turn($entry['current_player'] == $entry['player_1']);
                    $this->games[] = $game;
                }
            }
        }
        return $this;
    }

    public function new_game() : ChessGame
    {
        $game = new ChessGame();
        $this->games[] = $game;
        $this->current_game = $game;
        return $game;
    }

    public function current_game() : ?ChessGame
    {
        return $this->current_game;
    }

    public function export_current_game() : string
    {
        $game_data = $this->current_game()->export_data();
        $private_data = [
            'board' => sha1(serialize($game_data['board'])),
            'current_player' => $game_data['current_player'],
            'moves' => sha1(serialize($game_data['moves'])),
            'castling' => $game_data['castling']
        ];

        $export_data = [
            'game' => $game_data,
            'player_1' => '',
            'player_2' => ''
        ];

        $player_1 = $this->current_game()->player_1();
        if ($this->find_player($player_1->id())) {
            $export_data['player_1'] = \dweorh\Utils\Encryption::encrypt(json_encode($private_data), $player_1->public_key());
        }
        $player_2 = $this->current_game()->player_2();
        if ($player_2 && $this->find_player($player_2->id())) {
            $export_data['player_2'] = \dweorh\Utils\Encryption::encrypt(json_encode($private_data), $player_2->public_key());
        }
        $file_name = sprintf($this->single_game_file_pattern, $this->current_game()->id());
        file_put_contents($file_name, json_encode($export_data));
        return $file_name;
    }

    public function save_games() : ChessLocal
    {
        file_put_contents($this->file_games, json_encode($this->games));
        return $this;
    }

    public function list_games() : array
    {
        return $this->games;
    }
}