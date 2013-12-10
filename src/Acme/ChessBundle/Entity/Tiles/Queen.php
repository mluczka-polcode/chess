<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Queen extends Tile
{
    public function getMoves()
    {
        return $this->getLongMoves(array_merge($this->straightMoves, $this->diagonalMoves));
    }
}
