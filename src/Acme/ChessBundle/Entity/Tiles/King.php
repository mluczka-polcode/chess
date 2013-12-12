<?php

namespace Acme\ChessBundle\Entity\Tiles;

class King extends Tile
{
    public function getName()
    {
        return 'king';
    }

    public function getMoves($mode = 'all')
    {
        $moves = array();

        for($i = -1; $i <= 1; $i++)
        {
            for($j = -1; $j <= 1; $j++)
            {
                $destination = array(
                    'x' => $this->x + $i,
                    'y' => $this->y + $j,
                );
                if($this->canMoveOrBeat($destination))
                {
                    $moves[] = $destination;
                }
            }
        }

        if(in_array($mode, array('move', 'all')))
        {
            if($this->isCastlingPossible('short'))
            {
                $moves[] = array('x' => $this->x + 2, 'y' => $this->y);
            }

            if($this->isCastlingPossible('long'))
            {
                $moves[] = array('x' => $this->x - 2, 'y' => $this->y);
            }
        }

        return $moves;
    }

    private function isCastlingPossible($direction)
    {
        $castlings = $this->board->getCastlings();
        $player = $this->getOwner();

        if(!in_array($castlings[$player], array($direction, 'both')))
        {
            return false;
        }

        $kingX = 4;
        $y = $this->board->getFirstLine($player);

        if($this->x != $kingX || $this->y != $y || $this->board->isFieldAttacked($this->getCoords(), $player))
        {
            return false;
        }

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
            $coords = array(
                'x' => $x,
                'y' => $y,
            );

            if(!$this->board->isFieldEmpty($coords))
            {
                return false;
            }

            if($x > 1 && $this->board->isFieldAttacked($coords, $player))
            {
                return false;
            }
        }

        return true;
    }

    protected function afterMove()
    {
        $castlings = $this->board->getCastlings();
        $castlings[$this->getOwner()] = 'none';
        $this->board->setCastlings($castlings);
    }

    protected function updateMoveLog()
    {
        $destination = $this->getDestination();
        $toX = $destination['x'];
        $toY = $destination['y'];

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
            parent::updateMoveLog();
        }
    }

    protected function updatePosition()
    {
        $destination = $this->getDestination();
        $toX = $destination['x'];
        $toY = $destination['y'];

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

        parent::updatePosition();
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
