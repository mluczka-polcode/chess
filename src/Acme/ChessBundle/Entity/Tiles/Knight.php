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

    public function getMoves()
    {
        $moves = array();

        foreach($this->moves as $move)
        {
            $toX = $this->x + $move['x'];
            $toY = $this->y + $move['y'];
            if($this->canMoveOrBeat($toX, $toY))
            {
                $moves[] = array(
                    'x' => $toX,
                    'y' => $toY,
                );
            }
        }

        return $moves;
    }
}
