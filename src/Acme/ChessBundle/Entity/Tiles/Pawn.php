<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Pawn extends Tile
{
    public function getName()
    {
        return 'pawn';
    }

    public function getMoves($mode = 'all')
    {
        $x = $this->x;
        $y = $this->y;

        $moves = array();

        $modifier = $this->board->isWhitePlayer($this->getOwner()) ? 1 : -1;

        if(in_array($mode, array('move', 'all')))
        {
            $destination = array(
                'x' => $x,
                'y' => $y + $modifier,
            );

            if($this->board->isFieldEmpty($destination))
            {
                $moves[] = $destination;

                $destination = array(
                    'x' => $x,
                    'y' => $y + (2 * $modifier),
                );

                if($y == $this->board->getFirstLine($this->getOwner()) + $modifier && $this->board->isFieldEmpty($destination))
                {
                    $moves[] = $destination;
                }
            }
        }

        if(in_array($mode, array('beat', 'all')))
        {
            $beatMoves = array(
                array(
                    'x' => $x - 1,
                    'y' => $y + $modifier,
                ),
                array(
                    'x' => $x + 1,
                    'y' => $y + $modifier,
                ),
            );

            foreach($beatMoves as $destination)
            {
                if($this->isEnemyTile($destination) || $this->enPassantPossible($destination))
                {
                    $moves[] = $destination;
                }
            }
        }

        return $moves;
    }

    protected function updateMoveLog()
    {
        $destination = $this->getDestination();

        // source field
        $this->moveLog .= $this->convertNumberToLetter($this->x) . ($this->y + 1);

        // move or beat
        $this->moveLog .= $this->position[$destination['y']][$destination['x']] == '_' ? '-' : ':';

        // destination field
        $this->moveLog .= $this->convertNumberToLetter($destination['x']) . ($destination['y'] + 1);
    }

    protected function updatePosition()
    {
        $destination = $this->getDestination();
        $toX = $destination['x'];
        $toY = $destination['y'];

        if($toY == $this->board->getLastLine($this->getOwner()))
        {
            $this->position[$toY][$toX] = $this->board->isWhitePlayer($this->getOwner()) ? 'Q' : 'q';
            $this->position[$this->y][$this->x] = '_';
            return;
        }

        if($this->enPassantPossible($destination))
        {
            $this->position[$toY][$toX] = $this->position[$this->y][$this->x];
            $this->position[$this->y][$this->x] = '_';
            $lastMove = $this->board->getLastMove();
            $this->position[$lastMove['toY']][$lastMove['toX']] = '_';
            return;
        }

        parent::updatePosition($destination);
    }

    private function enPassantPossible($destination)
    {
        $lastMove = $this->board->getLastMove();
        if(!$lastMove)
        {
            return false;
        }

        $lastDestination = array(
            'x' => $lastMove['toX'],
            'y' => $lastMove['toY'],
        );

        return (
            $lastMove['fromX'] == $destination['x']
            && $lastMove['fromY'] + $lastMove['toY'] == 2 * $destination['y']
            && $this->isEnemyTile($lastDestination)
            && $this->isPawn($lastDestination)
            && abs($lastMove['toY'] - $lastMove['fromY']) == 2
        );
    }

    private function isPawn($coords)
    {
        return strtolower($this->position[$coords['y']][$coords['x']]) == 'p';
    }

}
