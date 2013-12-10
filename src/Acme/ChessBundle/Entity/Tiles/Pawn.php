<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Pawn extends Tile
{
    public function getMoves()
    {
        $x = $this->x;
        $y = $this->y;

        $moves = array();

        $modifier = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 1 : -1;

        // move
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

        // beat
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

        return $moves;
    }

    protected function updateMoveLog($toX, $toY)
    {
        // source field
        $this->moveLog .= $this->convertNumberToLetter($this->x) . ($this->y + 1);

        // move or beat
        $this->moveLog .= $this->position[$toY][$toX] == '_' && !$this->enPassantPossible($toX, $toY) ? '-' : ':';

        // destination field
        $this->moveLog .= $this->convertNumberToLetter($toX) . ($toY + 1);
    }

    protected function updatePosition($toX, $toY)
    {
        if($this->isLastLine($toY))
        {
            $this->position[$toY][$toX] = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 'Q' : 'q';
            $this->position[$this->y][$this->x] = '_';
            return;
        }

        if($this->enPassantPossible($toX, $toY))
        {
            $this->position[$toY][$toX] = $this->position[$this->y][$this->x];
            $this->position[$this->y][$this->x] = '_';
            $lastMove = $this->getLastMove();
            $this->position[$lastMove['toY']][$lastMove['toX']] = '_';
            return;
        }

        parent::updatePosition($toX, $toY);
    }

    private function isStartingLine($y)
    {
        if($this->getCurrentPlayer() == self::PLAYER_WHITE && $y == 1)
        {
            return true;
        }

        if($this->getCurrentPlayer() == self::PLAYER_BLACK && $y == self::BOARD_SIZE - 2)
        {
            return true;
        }

        return false;
    }

    private function isLastLine($y)
    {
        if($this->getCurrentPlayer() == self::PLAYER_WHITE && $y == self::BOARD_SIZE - 1)
        {
            return true;
        }

        if($this->getCurrentPlayer() == self::PLAYER_BLACK && $y == 0)
        {
            return true;
        }

        return false;
    }

    private function enPassantPossible($x, $y)
    {
        $lastMove = $this->getLastMove();
        if(!$lastMove)
        {
            return false;
        }

        return (
            $lastMove['fromX'] == $x
            && $lastMove['fromY'] + $lastMove['toY'] == 2 * $y
            && $this->isEnemyTile($lastMove['toX'], $lastMove['toY'])
            && $this->isPawn($lastMove['toX'], $lastMove['toY'])
            && abs($lastMove['toY'] - $lastMove['fromY']) == 2
        );
    }

}
