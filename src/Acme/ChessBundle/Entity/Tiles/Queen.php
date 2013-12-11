<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Queen extends Tile
{
    public function getName()
    {
        return 'queen';
    }

    public function getMoves($mode = 'all')
    {
        return $this->getLongMoves(array_merge($this->straightMoves, $this->diagonalMoves));
    }
}
