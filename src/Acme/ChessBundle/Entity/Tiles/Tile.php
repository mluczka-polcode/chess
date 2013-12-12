<?php

namespace Acme\ChessBundle\Entity\Tiles;

use Acme\ChessBundle\Entity;
use Acme\ChessBundle\Exception\ChessException;

abstract class Tile
{
    protected $board;

    protected $position = array();

    protected $x = null;

    protected $y = null;

    protected $moveLog = '';

    protected $columnLetters = 'abcdefgh';

    public function setBoard($board)
    {
        $this->board = $board;
        $this->setPosition($board->getPosition());
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setCoords($coords)
    {
        $this->x = $coords['x'];
        $this->y = $coords['y'];
    }

    public function getCoords()
    {
        return array(
            'x' => $this->x,
            'y' => $this->y,
        );
    }

    public function setOwner($owner)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function canAttack($destination)
    {
        return in_array($destination, $this->getMoves('beat'));
    }

    final public function move($destination)
    {
        $this->setDestination($destination);
        $this->validateMove();
        $this->updateMoveLog();
        $this->updatePosition();
        $this->afterMove();
        $this->setCoords($destination);
    }

    public function getMoveLog()
    {
        return $this->moveLog;
    }

    abstract public function getName();

    abstract public function getMoves($mode = 'all');

    protected function setDestination($destination)
    {
        $this->destination = $destination;
    }

    protected function getDestination()
    {
        return $this->destination;
    }

    protected function validateMove()
    {
        if(!in_array($this->getDestination(), $this->getMoves()))
        {
            throw new ChessException('Invalid move', 1);
        }
    }

    protected function updateMoveLog()
    {
        $destination = $this->getDestination();

        $this->moveLog = strtoupper($this->position[$this->y][$this->x]);

        // source field
        $this->moveLog .= $this->convertNumberToLetter($this->x) . ($this->y + 1);

        // move or beat
        $this->moveLog .= $this->position[$destination['y']][$destination['x']] == '_' ? '-' : ':';

        // destination field
        $this->moveLog .= $this->convertNumberToLetter($destination['x']) . ($destination['y'] + 1);
    }

    protected function afterMove()
    {
    }

    protected function updatePosition()
    {
        $destination = $this->getDestination();

        $this->position[$destination['y']][$destination['x']] = $this->position[$this->y][$this->x];
        $this->position[$this->y][$this->x] = '_';
    }

    protected function canMoveOrBeat($coords)
    {
        return (
            $this->board->validCoords($coords)
            && ( $this->board->isFieldEmpty($coords) || $this->isEnemyTile($coords) )
        );
    }

    protected function isEnemyTile($coords)
    {
        return (
            $this->board->validCoords($coords)
            && $this->board->getTileOwner($coords) == $this->board->getOpponent($this->getOwner())
        );
    }

    protected function getLongMoves($directions)
    {
        $moves = array();

        foreach($directions as $move)
        {
            $i = 1;
            $destination = array(
                'x' => $this->x + ($i * $move['x']),
                'y' => $this->y + ($i * $move['y']),
            );

            while($this->board->isFieldEmpty($destination))
            {
                $moves[] = $destination;

                $i += 1;
                $destination = array(
                    'x' => $this->x + ($i * $move['x']),
                    'y' => $this->y + ($i * $move['y']),
                );
            }

            if($this->isEnemyTile($destination))
            {
                $moves[] = $destination;
            }
        }

        return $moves;
    }

    protected function convertNumberToLetter($number)
    {
        return $this->columnLetters[$number];
    }

}
