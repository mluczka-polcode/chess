<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Queen extends Tile
{
    public function getMoves($x, $y)
    {
        return $this->getLongMoves($x, $y, array_merge($this->straightMoves, $this->diagonalMoves));
    }
}
