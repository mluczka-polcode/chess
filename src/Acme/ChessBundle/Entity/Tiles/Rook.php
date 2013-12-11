<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Rook extends Tile
{
    public function getName()
    {
        return 'rook';
    }

    public function getMoves($mode = 'all')
    {
        return $this->getLongMoves($this->straightMoves);
    }

    protected function afterMove()
    {
        $destination = $this->getDestination();
        $toX = $destination['x'];
        $toY = $destination['y'];

        $player = $this->getOwner();

        if($this->x == 0 && $this->y == $this->getFirstLine())
        {
            $castlings = $this->board->getCastlings();
            $castlings[$player] = in_array($castlings[$player], array('both', 'short')) ? 'short' : 'none';
            $this->board->setCastlings($castlings);
        }
        elseif($this->x == self::BOARD_SIZE - 1 && $this->y == $this->getFirstLine())
        {
            $castlings = $this->board->getCastlings();
            $castlings[$player] = in_array($castlings[$player], array('both', 'long')) ? 'long' : 'none';
            $this->board->setCastlings($castlings);
        }
    }

    private function getFirstLine()
    {
        return ( $this->getOwner() == self::PLAYER_WHITE ? 0 : self::BOARD_SIZE - 1 );
    }

}
