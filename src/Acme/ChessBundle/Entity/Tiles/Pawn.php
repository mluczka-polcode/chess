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

        $modifier = $this->getOwner() == self::PLAYER_WHITE ? 1 : -1;

        if(in_array($mode, array('move', 'all')))
        {
            if($this->isEmptyField($x, $y + $modifier))
            {
                $moves[] = array(
                    'x' => $x,
                    'y' => $y + $modifier,
                );

                if($this->isStartingLine($y) && $this->isEmptyField($x, $y + (2 * $modifier)))
                {
                    $moves[] = array(
                        'x' => $x,
                        'y' => $y + (2 * $modifier),
                    );
                }
            }
        }

        if(in_array($mode, array('beat', 'all')))
        {
            $beatMoves = array(
                array('x' => $x - 1, 'y' => $y + $modifier),
                array('x' => $x + 1, 'y' => $y + $modifier),
            );
            foreach($beatMoves as $move)
            {
                if($this->isEnemyTile($move['x'], $move['y']) || $this->enPassantPossible($move['x'], $move['y']))
                {
                    $moves[] = array(
                        'x' => $move['x'],
                        'y' => $move['y'],
                    );
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

        if($this->isLastLine($toY))
        {
            $this->position[$toY][$toX] = $this->getOwner() == self::PLAYER_WHITE ? 'Q' : 'q';
            $this->position[$this->y][$this->x] = '_';
            return;
        }

        if($this->enPassantPossible($toX, $toY))
        {
            $this->position[$toY][$toX] = $this->position[$this->y][$this->x];
            $this->position[$this->y][$this->x] = '_';
            $lastMove = $this->board->getLastMove();
            $this->position[$lastMove['toY']][$lastMove['toX']] = '_';
            return;
        }

        parent::updatePosition($destination);
    }

    private function isStartingLine($y)
    {
        if($this->getOwner() == self::PLAYER_WHITE && $y == 1)
        {
            return true;
        }

        if($this->getOwner() == self::PLAYER_BLACK && $y == self::BOARD_SIZE - 2)
        {
            return true;
        }

        return false;
    }

    private function isLastLine($y)
    {
        if($this->getOwner() == self::PLAYER_WHITE && $y == self::BOARD_SIZE - 1)
        {
            return true;
        }

        if($this->getOwner() == self::PLAYER_BLACK && $y == 0)
        {
            return true;
        }

        return false;
    }

    private function enPassantPossible($x, $y)
    {
        $lastMove = $this->board->getLastMove();
        if(!$lastMove)
        {
            return false;
        }

        return (
            $lastMove['fromX'] == $x
            && $lastMove['fromY'] + $lastMove['toY'] == 2 * $y
            && $this->isEnemyTile($lastMove['toX'], $lastMove['toY'])
            && strtolower($this->position[$lastMove['toY']][$lastMove['toX']]) == 'p'
            && abs($lastMove['toY'] - $lastMove['fromY']) == 2
        );
    }

}
