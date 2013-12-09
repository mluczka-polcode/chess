<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Bishop extends Tile
{
    public function getMoves($x, $y)
    {
        return $this->getLongMoves($x, $y, $this->diagonalMoves);
    }
}
