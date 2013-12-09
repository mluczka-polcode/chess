<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Rook extends Tile
{
    public function getMoves($x, $y)
    {
        return $this->getLongMoves($x, $y, $this->straightMoves);
    }
}
