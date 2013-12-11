<?php

namespace Acme\ChessBundle\Entity\Tiles;

use Acme\ChessBundle\Entity;

abstract class Tile
{
    const BOARD_SIZE = 8;
    const PLAYER_WHITE = 'white';
    const PLAYER_BLACK = 'black';

    protected $board;

    protected $position = array();

    protected $x = null;

    protected $y = null;

    protected $moveLog = '';

    protected $columnLetters = 'abcdefgh';

    protected $diagonalMoves = array(
        array('x' => 1,  'y' =>  1),
        array('x' => 1,  'y' => -1),
        array('x' => -1, 'y' =>  1),
        array('x' => -1, 'y' => -1),
    );

    protected $straightMoves = array(
        array('x' =>  1, 'y' =>  0),
        array('x' => -1, 'y' =>  0),
        array('x' =>  0, 'y' =>  1),
        array('x' =>  0, 'y' => -1),
    );

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
print_r($this->getName());
echo "<br />\n";
print_r($this->getCoords());
echo "<br />\n";
print_r($this->getDestination());
echo "<br />\n";
print_r($this->getMoves());
echo "<br />\n";
print_r($this->board->getLastMove());
echo "<br />\n";
            throw new \Exception('Invalid move!');
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
        $x = $coords['x'];
        $y = $coords['y'];
        return ( $this->isValidField($x, $y) && ($this->isEmptyField($x, $y) || $this->isEnemyTile($x, $y)) );
    }

    protected function isValidField($x, $y)
    {
        return isset($this->position[$y], $this->position[$y][$x]);
    }

    protected function isEmptyField($x, $y)
    {
        return ( $this->isValidField($x, $y) && $this->position[$y][$x] == '_' );
    }

    protected function isOwnTile($x, $y)
    {
        if(!$this->isValidField($x, $y) || $this->isEmptyField($x, $y))
        {
            return false;
        }

        $player = $this->getOwner();
        if($player == self::PLAYER_WHITE)
        {
            return $this->position[$y][$x] == strtoupper($this->position[$y][$x]);
        }
        elseif($player == self::PLAYER_BLACK)
        {
            return $this->position[$y][$x] == strtolower($this->position[$y][$x]);
        }
    }

    protected function isEnemyTile($x, $y)
    {
        return ( $this->isValidField($x, $y) && !$this->isEmptyField($x, $y) && !$this->isOwnTile($x, $y) );
    }

    protected function getLongMoves($directions)
    {
        $moves = array();

        foreach($directions as $move)
        {
            $i = 1;
            $toX = $this->x + ($i * $move['x']);
            $toY = $this->y + ($i * $move['y']);

            while($this->isEmptyField($toX, $toY))
            {
                $moves[] = array('x' => $toX, 'y' => $toY);
                $i += 1;
                $toX = $this->x + ($i * $move['x']);
                $toY = $this->y + ($i * $move['y']);
            }

            if($this->isEnemyTile($toX, $toY))
            {
                $moves[] = array('x' => $toX, 'y' => $toY);
            }
        }

        return $moves;
    }

    protected function convertNumberToLetter($number)
    {
        return $this->columnLetters[$number];
    }

}
