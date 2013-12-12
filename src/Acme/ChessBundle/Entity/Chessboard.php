<?php

namespace Acme\ChessBundle\Entity;

use Acme\ChessBundle\Entity\Tiles;

class Chessboard
{
    const BOARD_SIZE = 8;

    const PLAYER_WHITE = 'white';
    const PLAYER_BLACK = 'black';

    const MAX_POSITION_REPEATS = 3;
    const MAX_REVERSIBLE_MOVES = 50;

    private $position = array();

    private $tiles = array();

    private $lastMove;

    private $castlings;

    private $moveLog = '';

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
                $tile->setOwner($this->getFieldOwner($field));

                $this->tiles[] = $tile;
            }
        }
    }

    public function getPosition()
    {
        return $this->position;
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
        $this->updateCastlings();
        $this->setMoveLog($tile->getMoveLog());
    }

    public function isKingAttacked($player)
    {
        $kingCoords = $this->getKingCoords($player);
        return $this->isFieldAttacked($kingCoords, $player);
    }

    public function validCoords($coords)
    {
        return $this->isValidCoord($coords['x']) && $this->isValidCoord($coords['y']);
    }

    public function isFieldEmpty($coords)
    {
        return $this->validCoords($coords) && $this->position[$coords['y']][$coords['x']] == '_';
    }

    public function getTileOwner($coords)
    {
        if(!$this->validCoords($coords))
        {
            return '';
        }
        $tile = $this->getTileByCoords($coords);
        return $tile->getOwner();
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

    public function getOpponent($player)
    {
        return $player == self::PLAYER_WHITE ? self::PLAYER_BLACK : self::PLAYER_WHITE;
    }

    public function getFirstLine($player)
    {
        return $player == self::PLAYER_WHITE ? 0 : self::BOARD_SIZE - 1;
    }

    public function getLastLine($player)
    {
        return $player == self::PLAYER_WHITE ? self::BOARD_SIZE - 1 : 0;
    }

    public function getLastColumn()
    {
        return self::BOARD_SIZE - 1;
    }

    public function isWhitePlayer($player)
    {
        return $player == self::PLAYER_WHITE;
    }

    public function isTie()
    {
        return (
            !$this->sufficientTiles()
            || $this->positionRepeatsCount() >= self::MAX_POSITION_REPEATS
            || $this->reversibleMovesCount() >= self::MAX_REVERSIBLE_MOVES
        );
    }

    private function sufficientTiles()
    {
        $lightTileAlreadyFound = false;

        foreach($this->tiles as &$tile)
        {
            $name = $tile->getName();

            if(in_array($name, array('pawn', 'rook', 'queen')))
            {
                return true;
            }

            if(in_array($name, array('knight', 'bishop')))
            {
                if($lightTileAlreadyFound)
                {
                    return true;
                }

                $lightTileAlreadyFound = true;
            }
        }

        return false;
    }

    private function positionRepeatsCount()
    {
        // TODO: implement
        return 0;
    }

    private function reversibleMovesCount()
    {
        // TODO: implement
        return 0;
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

    private function getFieldOwner($field)
    {
        if($field == '_')
        {
            return '';
        }

        return strtoupper($field) == $field ? self::PLAYER_WHITE : self::PLAYER_BLACK;
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

        throw new \Exception('Invalid tile coords: ' . $coords['x'] . ', ' . $coords['y']);
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

    private function isValidCoord($coord)
    {
        return preg_match('/\d/', $coord) && $coord >= 0 && $coord < self::BOARD_SIZE;
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

    private function updateCastlings()
    {
        $position = $this->getPosition();

        if($position[0][0] != 'R')
        {
            $this->blockCastling(self::PLAYER_WHITE, 'long');
        }

        if($position[0][7] != 'R')
        {
            $this->blockCastling(self::PLAYER_WHITE, 'short');
        }

        if($position[7][0] != 'r')
        {
            $this->blockCastling(self::PLAYER_BLACK, 'long');
        }

        if($position[7][7] != 'r')
        {
            $this->blockCastling(self::PLAYER_BLACK, 'short');
        }
    }

    public function blockCastling($player, $direction)
    {
        $castlings = $this->getCastlings();
        $castlings[$player] = $castlings[$player] == 'both' ? $this->oppositeCastling($direction) : 'none';
        $this->setCastlings($castlings);
    }

    private function oppositeCastling($direction)
    {
        return $direction == 'short' ? 'long' : 'short';
    }

}
