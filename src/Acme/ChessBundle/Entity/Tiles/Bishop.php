<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Bishop extends Tile
{
    public function getMoves()
    {
        return $this->getLongMoves($this->diagonalMoves);
    }
}
