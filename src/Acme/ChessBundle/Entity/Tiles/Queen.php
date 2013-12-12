<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Queen extends Tile
{
    private $moves = array(
        array('x' =>  1, 'y' =>  1),
        array('x' =>  1, 'y' =>  0),
        array('x' =>  1, 'y' => -1),
        array('x' =>  0, 'y' => -1),
        array('x' => -1, 'y' => -1),
        array('x' => -1, 'y' =>  0),
        array('x' => -1, 'y' =>  1),
        array('x' =>  0, 'y' =>  1),
    );

    public function getName()
    {
        return 'queen';
    }

    public function getMoves($mode = 'all')
    {
        return $this->getLongMoves($this->moves);
    }
}
