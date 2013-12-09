<?php

namespace Acme\ChessBundle\Entity\Tiles;

class King extends Tile
{
    public function getMoves($x, $y)
    {
        $moves = array();

        for($i = -1; $i <= 1; $i++)
        {
            for($j = -1; $j <= 1; $j++)
            {
                $toX = $x + $i;
                $toY = $y + $j;
                if($this->canMoveOrBeat($toX, $toY))// && !$this->isAttacked($toX, $toY))
                {
                    $moves[] = array('x' => $toX, 'y' => $toY);
                }
            }
        }

        if($this->isCastlingPossible('short'))
        {
            $moves[] = array('x' => $x + 2, 'y' => $y);
        }

        if($this->isCastlingPossible('long'))
        {
            $moves[] = array('x' => $x - 2, 'y' => $y);
        }

        return $moves;
    }

    private function isCastlingPossible($direction)
    {
//         $castlings = $this->getCastlings();
        $player = $this->getCurrentPlayer();

//         if(!in_array($castlings[$player], array($direction, 'both')))
//         {
//             return false;
//         }

        $kingX = $player == self::PLAYER_WHITE ? 4 : 3;
        $y = 0;

//         if($this->isAttacked($kingX, $y))
//         {
//             return false;
//         }

        if($direction == 'short')
        {
            $xMin = 5;
            $xMax = 6;
        }
        else
        {
            $xMin = 1;
            $xMax = 3;
        }

        for($x = $xMin; $x <= $xMax; $x++)
        {
            if(!$this->isEmptyField($x, $y) && $x != $kingX)
            {
                return false;
            }

//             if($x > 1 && $this->isAttacked($x, $y))
//             {
//                 return false;
//             }
        }

        return true;
    }

}
