<?php

namespace Acme\ChessBundle\Entity\Tiles;

class King extends Tile
{
    public function getMoves()
    {
        $moves = array();

        for($i = -1; $i <= 1; $i++)
        {
            for($j = -1; $j <= 1; $j++)
            {
                $toX = $this->x + $i;
                $toY = $this->y + $j;
                if($this->canMoveOrBeat($toX, $toY))
                {
                    $moves[] = array('x' => $toX, 'y' => $toY);
                }
            }
        }

        if($this->isCastlingPossible('short'))
        {
            $moves[] = array('x' => $this->x + 2, 'y' => $this->y);
        }

        if($this->isCastlingPossible('long'))
        {
            $moves[] = array('x' => $this->x - 2, 'y' => $this->y);
        }

        return $moves;
    }

    private function isCastlingPossible($direction)
    {
        $castlings = $this->getCastlings();
        $player = $this->getCurrentPlayer();

        if(!in_array($castlings[$player], array($direction, 'both')))
        {
            return false;
        }

        $kingX = 4;
        $y = $player == self::PLAYER_WHITE ? 0 : 7;

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
            $xMax = 4;
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

    public function move($toX, $toY)
    {
        $castlings = $this->getCastlings();
        $castlings[$this->getCurrentPlayer()] = 'none';
        $this->game->setCastlings($castlings);

        parent::move($toX, $toY);
    }

    protected function updateMoveLog($toX, $toY)
    {
        if($this->isShortCastling($toX, $toY))
        {
            $this->moveLog .= 'O-O';
        }
        elseif($this->isLongCastling($toX, $toY))
        {
            $this->moveLog .= 'O-O-O';
        }
        else
        {
            parent::updateMoveLog($toX, $toY);
        }
    }

    protected function updatePosition($toX, $toY)
    {
        if($this->isShortCastling($toX, $toY))
        {
            $this->position[$toY][$toX - 1] = $this->position[$this->y][7];
            $this->position[$this->y][7] = '_';
        }
        elseif($this->isLongCastling($toX, $toY))
        {
            $this->position[$toY][$toX + 1] = $this->position[$this->y][0];
            $this->position[$this->y][0] = '_';
        }

        parent::updatePosition($toX, $toY);
    }

    protected function isShortCastling($toX, $toY)
    {
        return ( $this->x == 4 && $toX == 6);
    }

    protected function isLongCastling($toX, $toY)
    {
        return ( $this->x == 4 && $toX == 2);
    }

}
