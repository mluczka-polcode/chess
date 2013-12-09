<?php

namespace Acme\ChessBundle\Entity\Tiles;

use Acme\ChessBundle\Entity;

abstract class Tile
{
    const BOARD_SIZE = 8;
    const PLAYER_WHITE = 'white';
    const PLAYER_BLACK = 'black';

    protected $position = array();

    protected $player = null;

    protected $knightMoves = array(
        array('x' =>  1, 'y' =>  2),
        array('x' =>  2, 'y' =>  1),
        array('x' =>  2, 'y' => -1),
        array('x' =>  1, 'y' => -2),
        array('x' => -1, 'y' => -2),
        array('x' => -2, 'y' => -1),
        array('x' => -2, 'y' =>  1),
        array('x' => -1, 'y' =>  2),
    );

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

    protected $lastMove = null;

    public function setPosition($position)
    {
        $this->position = $position;
    }

    public function setCurrentPlayer($player)
    {
        $this->player = $player;
    }

    public function setLastMove($move)
    {
        $this->lastMove = $move;
    }

    public function getLastMove()
    {
        return $this->lastMove;
    }

    abstract public function getMoves($x, $y);

    protected function getCurrentPlayer()
    {
        return $this->player;
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

    protected function getLongMoves($x, $y, $directions)
    {
        $moves = array();

        foreach($directions as $move)
        {
            $i = 1;
            $toX = $x + ($i * $move['x']);
            $toY = $y + ($i * $move['y']);

            while($this->isEmptyField($toX, $toY))
            {
                $moves[] = array('x' => $toX, 'y' => $toY);
                $i += 1;
                $toX = $x + ($i * $move['x']);
                $toY = $y + ($i * $move['y']);
            }

            if($this->isEnemyTile($toX, $toY))
            {
                $moves[] = array('x' => $toX, 'y' => $toY);
            }
        }

        return $moves;
    }

    protected function isPawn($x, $y)
    {
        return($this->isValidField($x, $y) && strtolower($this->position[$y][$x]) == 'p');
    }

    protected function isKnight($x, $y)
    {
        return($this->isValidField($x, $y) && strtolower($this->position[$y][$x]) == 'k');
    }

    protected function isBishop($x, $y)
    {
        return($this->isValidField($x, $y) && strtolower($this->position[$y][$x]) == 'b');
    }

    protected function isRook($x, $y)
    {
        return($this->isValidField($x, $y) && strtolower($this->position[$y][$x]) == 'r');
    }

    protected function isQueen($x, $y)
    {
        return($this->isValidField($x, $y) && strtolower($this->position[$y][$x]) == 'q');
    }

    protected function isKing($x, $y)
    {
        return($this->isValidField($x, $y) && strtolower($this->position[$y][$x]) == 'x');
    }

}
