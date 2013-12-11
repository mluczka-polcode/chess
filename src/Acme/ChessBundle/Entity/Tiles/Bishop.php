<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Bishop extends Tile
{
    public function getName()
    {
        return 'bishop';
    }

    public function getMoves($mode = 'all')
    {
        return $this->getLongMoves($this->diagonalMoves);
    }
}
