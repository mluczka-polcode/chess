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

    public function getMoves($x, $y)
    {
        $moves = array();

        foreach($this->moves as $move)
        {
            $toX = $x + $move['x'];
            $toY = $y + $move['y'];
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
