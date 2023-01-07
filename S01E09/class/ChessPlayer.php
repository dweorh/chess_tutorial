<?php
namespace dweorh\Chess;
class ChessPlayer implements \JsonSerializable {
    protected $id;
    protected $name = '';
    protected $private_key;
    protected $public_key;

    public function __construct(string $name, string $id = null, string $private_key = null, string $public_key = null)
    {
        $this->name = $name;
        if (empty($id)) {
            $id = \dweorh\Utils\Generators::uuidv4();
        }
        $this->id = $id;
        if (empty($private_key)) {
            $keys = \dweorh\Utils\Encryption::generate_keys();
            $private_key = $keys['private'];
            $public_key = $keys['public'];
        }
        $this->private_key = $private_key;
        $this->public_key = $public_key;
    }

    public function id() : string
    {
        return $this->id;
    }

    public function name() : string
    {
        return $this->name;
    }

    public function private_key() : string
    {
        return $this->private_key;
    }

    public function public_key() : string
    {
        return $this->public_key;
    }

    public function __toString()
    {
        return $this->name;
    }

    public function jsonSerialize() : array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'keys' => [
                'public' => $this->public_key,
                'private' => $this->private_key
            ]
        ];
    }
}