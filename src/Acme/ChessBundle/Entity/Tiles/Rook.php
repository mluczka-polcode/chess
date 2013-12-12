<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Rook extends Tile
{
    private $moves = array(
        array('x' =>  1, 'y' =>  0),
        array('x' => -1, 'y' =>  0),
        array('x' =>  0, 'y' =>  1),
        array('x' =>  0, 'y' => -1),
    );

    public function getName()
    {
        return 'rook';
    }

    public function getMoves($mode = 'all')
    {
        return $this->getLongMoves($this->moves);
    }

    protected function afterMove()
    {
        $destination = $this->getDestination();
        $toX = $destination['x'];
        $toY = $destination['y'];

        $player = $this->getOwner();

        if($this->x == 0 && $this->y == $this->board->getFirstLine($player))
        {
            $this->board->blockCastling($player, 'long');
        }
        elseif($this->x == $this->board->getLastColumn() && $this->y == $this->board->getFirstLine($player))
        {
            $this->board->blockCastling($player, 'short');
        }
    }

}
