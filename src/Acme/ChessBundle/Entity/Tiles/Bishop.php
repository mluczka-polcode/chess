<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Bishop extends Tile
{
    private $moves = array(
        array('x' => 1,  'y' =>  1),
        array('x' => 1,  'y' => -1),
        array('x' => -1, 'y' =>  1),
        array('x' => -1, 'y' => -1),
    );

    public function getName()
    {
        return 'bishop';
    }

    public function getMoves($mode = 'all')
    {
        return $this->getLongMoves($this->moves);
    }
}
