<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Knight extends Tile
{
    private $moves = array(
        array('x' =>  1, 'y' =>  2),
        array('x' =>  2, 'y' =>  1),
        array('x' =>  2, 'y' => -1),
        array('x' =>  1, 'y' => -2),
        array('x' => -1, 'y' => -2),
        array('x' => -2, 'y' => -1),
        array('x' => -2, 'y' =>  1),
        array('x' => -1, 'y' =>  2),
    );

    public function getName()
    {
        return 'knight';
    }

    public function getMoves($mode = 'all')
    {
        $moves = array();

        foreach($this->moves as $move)
        {
            $destination = array(
                'x' => $this->x + $move['x'],
                'y' => $this->y + $move['y'],
            );
            if($this->canMoveOrBeat($destination))
            {
                $moves[] = $destination;
            }
        }

        return $moves;
    }
}
