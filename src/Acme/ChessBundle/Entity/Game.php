<?php

namespace Acme\ChessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

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
    private $position;

    /**
     * @var string
     */
    private $log = '';

    /**
     * @var string
     */
    private $castling = 'white both, black both';

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
        if(is_array($position))
        {
            foreach($position as &$row)
            {
                $row = implode('', $row);
            }
            $position = implode("\n", $position);
        }

        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return string
     */
    public function getPosition()
    {
        return $this->position;
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
     * @return string
     */
    public function getLog()
    {
        return $this->log;
    }

    /**
     * Set castling
     *
     * @param string $castling
     * @return Game
     */
    public function setCastling($castling)
    {
        $this->castling = $castling;

        return $this;
    }

    /**
     * Get castling
     *
     * @return string
     */
    public function getCastling()
    {
        return $this->castling;
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

    public function getStartPosition()
    {
       return "RKBQXBKR\nPPPPPPPP\n________\n________\n________\n________\npppppppp\nrkbqxbkr";
    }

    public function getPositionAsArray($player = null)
    {
        if(!$player)
        {
            $player = $this->getCurrentPlayer();
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

        return $result;
    }

    public function getLogAsArray()
    {
        $result = array();
        $log = explode("\n", str_replace("\r", '', trim($this->log)));
        for($i = 0; $i < count($log); $i += 2)
        {
            $result[] = $log[$i] . (!empty($log[$i+1]) ? ' ' . $log[$i+1] : '');
        }
        return $result;
    }

    public function getCurrentPlayer()
    {
        if(empty($this->log))
        {
            return self::PLAYER_WHITE;
        }

        $log = explode("\n", trim($this->log));
        if(count($log) % 2)
        {
            return self::PLAYER_BLACK;
        }
        else
        {
            return self::PLAYER_WHITE;
        }
    }

    public function getLastMove($player)
    {
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

        $this->updatePosition();

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
            $position = $this->getPositionAsArray(self::PLAYER_WHITE);
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

        // check or mate
        if($this->enemyKingAttacked())
        {
            $log .= $this->isCheckmate() ? 'x' : '+';
        }

        $log .= "\n";

        return $log;
    }

    private function getCastlingType()
    {
        // TODO: implement
        return false;
    }

    private function updatePosition()
    {
        $position = $this->getPositionAsArray(self::PLAYER_WHITE);
        $tile = $position[$this->fromY][$this->fromX];

        if(strtolower($tile) == 'p' && in_array($this->toY, array(0, self::BOARD_SIZE - 1)))
        {
            $tile = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 'Q' : 'q';
        }

        $position[$this->toY][$this->toX] = $tile;
        $position[$this->fromY][$this->fromX] = '_';

        $this->setPosition($position);
    }

    private function isValidMove()
    {
        // TODO: implement
        return true;
    }

    private function ownKingAttacked()
    {
        // TODO: implement
        return false;
    }

    private function enemyKingAttacked()
    {
        // TODO: implement
        return false;
    }

    private function isCheckmate()
    {
        // TODO: implement
        return false;
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
