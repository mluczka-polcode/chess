<?php

namespace Acme\ChessBundle\Entity\Tiles;

use Acme\ChessBundle\Entity;

abstract class Tile
{
    const BOARD_SIZE = 8;
    const PLAYER_WHITE = 'white';
    const PLAYER_BLACK = 'black';

    protected $game;

    protected $position = array();

    protected $x = null;

    protected $y = null;

    protected $player = null;

    protected $lastMove = null;

    protected $castlings = array();

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

    public function init($game)
    {
        $this->game      = $game;
        $this->position  = $game->getPosition();
        $this->lastMove  = $game->getLastMove(self::PLAYER_WHITE);
        $this->castlings = $game->getCastlings();
    }

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function getPosition()
    {
        return $this->position;
    }

    public function setCoords($x, $y)
    {
        $this->x = $x;
        $this->y = $y;

        $tile = $this->position[$this->y][$this->x];
        $this->currentPlayer = strtolower($tile) == $tile ? self::PLAYER_BLACK : self::PLAYER_WHITE;
    }

    public function setLastMove($move)
    {
        $this->lastMove = $move;
    }

    public function getLastMove()
    {
        return $this->lastMove;
    }

    public function setCastlings($castlings)
    {
        $this->castlings = $castlings;
    }

    public function getCastlings()
    {
        return $this->castlings;
    }

    public function isAttacked()
    {
        // TODO: implement

        return false;
    }

    public function move($toX, $toY)
    {
        $this->validateMove($toX, $toY);
        $this->updateMoveLog($toX, $toY);
        $this->updatePosition($toX, $toY);
    }

    public function getMoveLog()
    {
        return $this->moveLog;
    }

    abstract public function getMoves();

    protected function validateMove($toX, $toY)
    {
        if(!in_array(array('x' => $toX, 'y' => $toY), $this->getMoves()))
        {
            throw new \Exception('Invalid move!');
        }
    }

    protected function updateMoveLog($toX, $toY)
    {
        $this->moveLog = strtoupper($this->position[$this->y][$this->x]);

        // source field
        $this->moveLog .= $this->convertNumberToLetter($this->x) . ($this->y + 1);

        // move or beat
        $this->moveLog .= $this->position[$toY][$toX] == '_' ? '-' : ':';

        // destination field
        $this->moveLog .= $this->convertNumberToLetter($toX) . ($toY + 1);
    }

    protected function updatePosition($toX, $toY)
    {
        $this->position[$toY][$toX] = $this->position[$this->y][$this->x];
        $this->position[$this->y][$this->x] = '_';
    }

    protected function getCurrentPlayer()
    {
        return $this->currentPlayer;
    }

    protected function canMoveOrBeat($x, $y)
    {
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

        $player = $this->getCurrentPlayer();
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
