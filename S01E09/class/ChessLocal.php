<?php
namespace dweorh\Chess;

class ChessLocal {
    protected $file_players = 'players.json';
    protected $players = [];
    protected ?ChessPlayer $current_player = null;
    
    public function load_players() : ChessLocal
    {
        if (file_exists($this->file_players)) {
            $content = file_get_contents($this->file_players);
            $json = json_decode($content, true);
            if ($json) {
                foreach($json['players'] as $entry) {
                    $player = new ChessPlayer($entry['name'], $entry['id']);
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
            'default' => $this->current_player->id(),
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
}