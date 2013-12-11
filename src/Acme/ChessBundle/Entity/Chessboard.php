<?php

namespace Acme\ChessBundle\Entity;

use Acme\ChessBundle\Entity\Tiles;

class Chessboard
{
    const BOARD_SIZE = 8;

    const PLAYER_WHITE = 'white';
    const PLAYER_BLACK = 'black';

    private $position = array();

    private $tiles = array();

    private $lastMove;

    private $castlings;

    private $moveLog = '';

    public function getPosition()
    {
        return $this->position;
    }

    public function setPosition($position)
    {
        $this->position = $position;

        $this->tiles = array();

        foreach($this->position as $y => $row)
        {
            foreach($row as $x => $field)
            {
                $tile = $this->getTileEntity($field);

                $tile->setBoard($this);
                $tile->setCoords(array(
                    'x' => $x,
                    'y' => $y,
                ));
                $tile->setOwner($this->getTileOwner($field));

                $this->tiles[] = $tile;
            }
        }
    }

    public function setLastMove($lastMove)
    {
        $this->lastMove = $lastMove;
    }

    public function getlastMove()
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

    public function setMoveLog($log)
    {
        $this->moveLog = $log;
    }

    public function getMoveLog()
    {
        return $this->moveLog;
    }

    public function getPossibleMoves($player)
    {
        $moves = array();

        foreach($this->tiles as &$tile)
        {
            if($tile->getOwner() == $player)
            {
                $tileCoords = $tile->getCoords();
                $tileMoves = $this->getAllowedMoves($player, $tileCoords, $tile->getMoves());
                if(!empty($tileMoves))
                {
                    $moves[$tileCoords['x']][$tileCoords['y']] = $tileMoves;
                }
            }
        }

        return $moves;
    }

    public function move($source, $destination)
    {
        $tile = $this->getTileByCoords($source);
        $tile->move($destination);

        $this->setPosition($tile->getPosition());
        $this->setMoveLog($tile->getMoveLog());
    }

    private function getTileEntity($tile)
    {
        $tile = strtolower($tile);
        switch($tile)
        {
            case '_': return new Tiles\EmptyField;
            case 'p': return new Tiles\Pawn;
            case 'k': return new Tiles\Knight;
            case 'b': return new Tiles\Bishop;
            case 'r': return new Tiles\Rook;
            case 'q': return new Tiles\Queen;
            case 'x': return new Tiles\King;
            default: throw new \Exception('Invalid tile: ' . $tile);
        }
    }

    private function getTileOwner($field)
    {
        if($field == '_')
        {
            return '';
        }

        return ( strtoupper($field) == $field ? self::PLAYER_WHITE : self::PLAYER_BLACK );
    }

    private function getTileByCoords($coords)
    {
        foreach($this->tiles as &$tile)
        {
            if($tile->getCoords() == $coords)
            {
                return $tile;
            }
        }

        throw new \Exception('Invalid tile coords!');
    }

    private function getAllowedMoves($player, $tileCoords, $tileMoves)
    {
        $result = array();

        foreach($tileMoves as $move)
        {
            $chessboard = new Chessboard();
            $chessboard->setPosition($this->position);
            $chessboard->setLastMove($this->getLastMove());
            $chessboard->setCastlings($this->castlings);
            $chessboard->move($tileCoords, $move);
            if(!$chessboard->isKingAttacked($player))
            {
                $result[] = $move;
            }
        }

        return $result;
    }

    public function isFieldAttacked($coords, $player)
    {
        $opponent = $this->getOpponent($player);
        foreach($this->tiles as &$tile)
        {
            if($tile->getOwner() == $opponent && $tile->canAttack($coords))
            {
                return true;
            }
        }

        return false;
    }

    public function isKingAttacked($player)
    {
        $kingCoords = $this->getKingCoords($player);
        return $this->isFieldAttacked($kingCoords, $player);
    }

    private function getKingCoords($player)
    {
        foreach($this->tiles as &$tile)
        {
            if($tile->getOwner() == $player && $tile->getName() == 'king')
            {
                return $tile->getCoords();
            }
        }

        throw new \Exception('Failed to find king for player "' . $player . '"');
    }

    public function getOpponent($player)
    {
        return ( $player == self::PLAYER_WHITE ? self::PLAYER_BLACK : self::PLAYER_WHITE );
    }

}
