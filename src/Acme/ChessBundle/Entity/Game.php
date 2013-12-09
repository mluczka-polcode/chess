<?php

namespace Acme\ChessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Acme\ChessBundle\Entity\Tiles;

/**
 * Game
 */
class Game
{
    const BOARD_SIZE = 8;
    const PLAYER_WHITE = 'white';
    const PLAYER_BLACK = 'black';

    private $statusValues = array(
        'in_progress',
        'white_won',
        'black_won',
        'tie',
    );

    private $columnLetters = 'abcdefgh';

    /**
     * @var integer
     */
    private $fromX = null, $fromY = null, $toX = null, $toY = null;

    private $positionArray = array();

    private $currentPlayer = null;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var string
     */
    private $tableId;

    /**
     * @var string
     */
    private $position = "RKBQXBKR\nPPPPPPPP\n________\n________\n________\n________\npppppppp\nrkbqxbkr";

    /**
     * @var string
     */
    private $log = '';

    /**
     * @var string
     */
    private $castlings = 'white both, black both';

    /**
     * @var string
     */
    private $tieProposal = '';

    /**
     * @var string
     */
    private $status = 'in_progress';

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set tableId
     *
     * @param string $tableId
     * @return Game
     */
    public function setTableId($tableId)
    {
        $this->tableId = $tableId;

        return $this;
    }

    /**
     * Get tableId
     *
     * @return string
     */
    public function getTableId()
    {
        return $this->tableId;
    }

    /**
     * Set position
     *
     * @param string $position
     * @return Game
     */
    public function setPosition($position)
    {
        foreach($position as &$row)
        {
            $row = implode('', $row);
        }
        $this->position = implode("\n", $position);

        return $this;
    }

    /**
     * Get position
     *
     * @return array
     */
    public function getPosition($player = null)
    {
        if(!$player)
        {
            $player = self::PLAYER_WHITE;
        }

        if(!empty($this->positionArray[$player]))
        {
            return $this->positionArray[$player];
        }

        $result = array();

        $position = explode("\n", trim(str_replace("\r", '', $this->position)));
        foreach($position as $row)
        {
            if($player == self::PLAYER_BLACK)
            {
                $row = strrev($row);
            }
            $result[] = str_split($row);
        }

        if($player == self::PLAYER_BLACK)
        {
            $result = array_reverse($result);
        }

        $this->positionArray[$player] = $result;

        return $result;
    }

    /**
     * Set log
     *
     * @param string $log
     * @return Game
     */
    public function setLog($log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Get log
     *
     * @return array
     */
    public function getLog()
    {
        $result = array();
        $log = explode("\n", str_replace("\r", '', trim($this->log)));
        for($i = 0; $i < count($log); $i += 2)
        {
            $result[] = $log[$i] . (!empty($log[$i+1]) ? ' ' . $log[$i+1] : '');
        }
        return $result;
    }

    /**
     * Set castlings
     *
     * @param string $castlings
     * @return Game
     */
    public function setCastlings($castlings)
    {
        $this->castlings = $castlings;

        return $this;
    }

    /**
     * Get castlings
     *
     * @return string
     */
    private function getCastlings()
    {
        $result = array(
            'white' => 'both',
            'black' => 'both',
        );

        $castlings = explode(',', $this->castlings);
        foreach($castlings as $row)
        {
            list($color, $status) = explode(' ', trim($row));
            $result[$color] = $status;
        }

        return $result;
    }

    /**
     * Set tieProposal
     *
     * @param string $player
     * @param string $status
     * @return Game
     */
    public function setTieProposal($player, $status = 'proposed')
    {
        $this->tieProposal = $player . ' ' . $status;

        return $this;
    }

    /**
     * Get tieProposal
     *
     * @return string
     */
    public function getTieProposal()
    {
        return $this->tieProposal;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Game
     */
    public function setStatus($status)
    {
        if(!in_array($status, $this->statusValues))
        {
            throw new \Exception('Invalid game status: ' . $status);
        }

        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    public function getGameState($player)
    {
        return array(
            'tableId'       => $this->tableId,
            'color'         => $player,
            'position'      => $this->getPosition($player),
            'possibleMoves' => $this->getPossibleMoves(),
            'log'           => $this->getLog(),
            'status'        => $this->getStatus(),
            'lastMove'      => $this->getLastMove($player),
            'castlings'     => $this->getCastlings(),
            'tieProposal'   => $this->getTieProposal(),
            'currentPlayer' => $this->getCurrentPlayer(),
        );
    }

    private function getPossibleMoves()
    {
        $moves = array();

        $position = $this->getPosition();

        foreach($position as $y => $row)
        {
            foreach($row as $x => $field)
            {
                if($this->isOwnTile($x, $y))
                {
                    $moves[$x][$y] = $this->getPossibleMovesForTile($x, $y);
                }
            }
        }

        if($this->getCurrentPlayer() == self::PLAYER_BLACK)
        {
            return $this->reverseCoords($moves);
        }

        return $moves;
    }

    private function getPossibleMovesForTile($x, $y)
    {
        $result = array();

        $position = $this->getPosition();
        $tile = strtolower($position[$y][$x]);

        $entities = $this->getTilesEntities();

        if(empty($entities[$tile]))
        {
            throw new \Exception('Invalid tile: ' . $tile);
        }

        $tile = $entities[$tile];
        $tile->setPosition($position);
        $tile->setCurrentPlayer($this->getCurrentPlayer());
        $tile->setLastMove($this->getLastMove(self::PLAYER_WHITE));
        $moves = $tile->getMoves($x, $y);

        foreach($moves as $move)
        {
            $positionAfterMove = $this->getPositionAfterMove($x, $y, $move['x'], $move['y']);

            $game = new Game();
            $game->setCurrentPlayer($this->getCurrentPlayer());
            $game->setPosition($positionAfterMove);
            if(!$game->isOwnKingAttacked())
            {
                $result[] = $move;
            }
        }

        return $result;
    }

    private function getTilesEntities()
    {
        return array(
            'p' => new Tiles\Pawn,
            'k' => new Tiles\Knight,
            'b' => new Tiles\Bishop,
            'r' => new Tiles\Rook,
            'q' => new Tiles\Queen,
            'x' => new Tiles\King,
        );
    }

    private function reverseCoords($data)
    {
        $out = array();

        foreach($data as $y => $row)
        {
            foreach($row as $x => $field)
            {
                foreach($field as $move)
                {
                    $out[self::BOARD_SIZE - 1 - $y][self::BOARD_SIZE - 1 - $x][] = array(
                        'x' => self::BOARD_SIZE - 1 - $move['x'],
                        'y' => self::BOARD_SIZE - 1 - $move['y'],
                    );
                }
            }
        }

        return $out;
    }

    private function getPositionAfterMove($fromX, $fromY, $toX, $toY)
    {
        $position = $this->getPosition();
        $tile = $position[$fromY][$fromX];

        if(strtolower($tile) == 'p' && in_array($toY, array(0, self::BOARD_SIZE - 1)))
        {
            $tile = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 'Q' : 'q';
        }

        $position[$toY][$toX] = $tile;
        $position[$fromY][$fromX] = '_';

        // castling
        if(strtolower($tile) == 'x' && $toX - $fromX == 2)
        {
            $position[$toY][$toX - 1] = $position[$toY][$fromX];
            $position[$toY][$fromX] = '_';
        }
        elseif(strtolower($tile) == 'x' && $toX - $fromX == -2)
        {
            $position[$toY][$toX + 1] = $position[$fromY][$fromX];
            $position[$toY][$fromX] = '_';
        }

        // TODO: en passant

        return $position;
    }

    private function isOwnKingAttacked()
    {
        $player = $this->getCurrentPlayer();
        $position = $this->getPosition();

        $x = $y = -1;
        foreach($position as $i => $row)
        {
            foreach($row as $j => $field)
            {
                if(($player == self::PLAYER_WHITE && $field === 'X') || ($player == self::PLAYER_BLACK && $field === 'x'))
                {
                    $x = $j;
                    $y = $i;
                }
            }
        }

        return $this->isAttacked($x, $y);
    }

    private function isValidField($x, $y)
    {
        $position = $this->getPosition();
        return isset($position[$y][$x]);
    }

    private function isEmptyField($x, $y)
    {
        $position = $this->getPosition();
        return ( $this->isValidField($x, $y) && $position[$y][$x] == '_' );
    }

    private function isOwnTile($x, $y)
    {
        $player = $this->getCurrentPlayer();
        $position = $this->getPosition();
        if(!$this->isValidField($x, $y) || $this->isEmptyField($x, $y))
        {
            return false;
        }

        if($player == self::PLAYER_WHITE)
        {
            return $position[$y][$x] == strtoupper($position[$y][$x]);
        }
        elseif($player == self::PLAYER_BLACK)
        {
            return $position[$y][$x] == strtolower($position[$y][$x]);
        }
    }

    private function isEnemyTile($x, $y)
    {
        return ( $this->isValidField($x, $y) && !$this->isEmptyField($x, $y) && !$this->isOwnTile($x, $y) );
    }

    private function isAttacked($x, $y)
    {
        $entities = $this->getTilesEntities();

        $position = $this->getPosition();
        foreach($position as $tileX => $row)
        {
            foreach($row as $tileY => $field)
            {
                if($this->isEnemyTile($tileX, $tileY))
                {
                    $tile = strtolower($position[$tileY][$tileX]);
                    if(empty($entities[$tile]))
                    {
                        throw new \Exception('Invalid tile: '.$tile);
                    }

                    $tile = $entities[$tile];
                    $tile->setPosition($position);
                    $tile->setCurrentPlayer($this->getCurrentPlayer());
                    $tile->setLastMove($this->getLastMove());

                    $moves = $tile->getMoves($tileX, $tileY);
                    if(in_array(array($x, $y), $moves))
                    {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    public function getCurrentPlayer()
    {
        if(!$this->currentPlayer)
        {
            if(trim($this->log)=='')
            {
                $this->currentPlayer = self::PLAYER_WHITE;
            }
            else
            {
                $log = explode("\n", trim($this->log));
                if(count($log) % 2)
                {
                    $this->currentPlayer = self::PLAYER_BLACK;
                }
                else
                {
                    $this->currentPlayer = self::PLAYER_WHITE;
                }
            }
        }

        return $this->currentPlayer;
    }


    public function setCurrentPlayer($player)
    {
        $this->currentPlayer = $player;
    }

    private function getLastMove($player = null)
    {
        if(!$player)
        {
            $player = $this->getCurrentPlayer();
        }

        if($this->log == '')
        {
            return null;
        }

        $log = substr($this->log, strrpos(rtrim($this->log, "\n"), "\n"));
        if(strpos($log, 'O-O')!==false)
        {
            // TODO: castling
            return array(
                //...
            );
        }

        $delimiter = strpos($log, '-')===false ? ':' : '-';
        $log = explode($delimiter, $log);

        $posStart = substr($log[0], -2);
        $fromX = $this->convertLetterToNumber($posStart[0]);
        $fromY = $posStart[1] - 1;

        $posEnd = substr($log[1], 0, 2);
        $toX = $this->convertLetterToNumber($posEnd[0]);
        $toY = $posEnd[1] - 1;

        if($player == self::PLAYER_BLACK)
        {
            $fromX = (self::BOARD_SIZE - 1) - $fromX;
            $fromY = (self::BOARD_SIZE - 1) - $fromY;
            $toX   = (self::BOARD_SIZE - 1) - $toX;
            $toY   = (self::BOARD_SIZE - 1) - $toY;
        }

        return array(
            'fromX' => $fromX,
            'fromY' => $fromY,
            'toX' => $toX,
            'toY' => $toY,
        );
    }

    public function setMoveCoords($fromX, $fromY, $toX, $toY)
    {
        foreach(array($fromX, $fromY, $toX, $toY) as $coord)
        {
            if(!preg_match('/\d/', $coord) || $coord < 0 || $coord >= self::BOARD_SIZE)
            {
                throw new \Exception('Invalid move coords!');
            }
        }

        if($this->getCurrentPlayer() == self::PLAYER_BLACK)
        {
            $fromX = (self::BOARD_SIZE - 1) - $fromX;
            $fromY = (self::BOARD_SIZE - 1) - $fromY;
            $toX   = (self::BOARD_SIZE - 1) - $toX;
            $toY   = (self::BOARD_SIZE - 1) - $toY;
        }

        $this->fromX = $fromX;
        $this->fromY = $fromY;
        $this->toX   = $toX;
        $this->toY   = $toY;
    }

    public function moveTile()
    {
        if(!$this->isValidMove())
        {
            throw new \Exception('Invalid move!');
        }

        $moveLog = $this->getLogForMove();

        $position = $this->getPositionAfterMove($this->fromX, $this->fromY, $this->toX, $this->toY);
        $this->setPosition($position);

        $possibleMoves = $this->getPossibleMoves();
        if(empty($possibleMoves))
        {
            if(isOwnKingAttacked())
            {
                $status = $player == self::PLAYER_WHITE ? 'black_won' : 'white_won';
            }
            else
            {
                $status = 'tie';
            }
            $this->setStatus($status);
        }

        $this->log .= $moveLog;
    }

    private function getLogForMove()
    {
        $log = '';

        $castling = $this->getCastlingType();
        if($castling == 'long')
        {
            $log .= 'O-O-O';
        }
        elseif($castling == 'short')
        {
            $log .= 'O-O';
        }
        else // no castling
        {
            // tile name
            $position = $this->getPosition();
            $tile = $position[$this->fromY][$this->fromX];
            if(strtolower($tile) != 'p')
            {
                $log .= strtoupper($tile);
            }

            // source field
            $log .= $this->convertNumberToLetter($this->fromX) . ($this->fromY + 1);

            // move or beat
            $log .= $position[$this->toY][$this->toX] == '_' ? '-' : ':';

            // destination field
            $log .= $this->convertNumberToLetter($this->toX) . ($this->toY + 1);

            // przemiana
            if(($tile == 'p' && $this->toY == 0) || ($tile == 'P' && $this->toY == self::BOARD_SIZE - 1))
            {
                $log .= 'Q';
            }
        }

        $log .= "\n";

        return $log;
    }

    private function getCastlingType()
    {
        // TODO: implement
        return false;
    }

    private function isValidMove()
    {
        // TODO: implement
        return true;
    }

    private function convertNumberToLetter($number)
    {
        return $this->columnLetters[$number];
    }

    private function convertLetterToNumber($letter)
    {
        return strpos($this->columnLetters, $letter);
    }

}
