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

        $this->positionArray = array();

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
            $result[] = $log[$i] . (!empty($log[$i + 1]) ? ' ' . $log[$i + 1] : '');
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
        if(is_array($castlings))
        {
            foreach($castlings as $key => &$row)
            {
                $row = $key.' '.$row;
            }
            $castlings = implode(', ', $castlings);
        }

        $this->castlings = $castlings;

        return $this;
    }

    /**
     * Get castlings
     *
     * @return string
     */
    public function getCastlings()
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
            'kingAttacked'  => $this->isOwnKingAttacked(),
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
                    $possibleMovesForTile = $this->getPossibleMovesForTile($x, $y);
                    if(!empty($possibleMovesForTile))
                    {
                        $moves[$x][$y] = $possibleMovesForTile;
                    }
                }
            }
        }

        if($this->getCurrentPlayer() == self::PLAYER_BLACK)
        {
            $moves = $this->reverseCoords($moves);
        }

        return $moves;
    }

    private function getPossibleMovesForTile($x, $y)
    {
        $result = array();

        $game = new Game();
        $game->setCastlings($this->getCastlings());
        $game->setCurrentPlayer($this->getCurrentPlayer());
        $game->setPosition($this->getPosition());

        $tile = $this->getTileEntity($x, $y, $game);
        $moves = $tile->getMoves();
        foreach($moves as $move)
        {
            $tile->setPosition($this->getPosition());
            $tile->move($move['x'], $move['y']);

            $game->setPosition($tile->getPosition());
            if(!$game->isOwnKingAttacked())
            {
                $result[] = $move;
            }

            $game->setCastlings($this->getCastlings());
            $game->setCurrentPlayer($this->getCurrentPlayer());
            $game->setPosition($this->getPosition());
        }

        return $result;
    }

    private function getTileEntity($x, $y, $game = null)
    {
        $position = $this->getPosition();
        $tile = strtolower($position[$y][$x]);
        $entities = array(
            'p' => new Tiles\Pawn,
            'k' => new Tiles\Knight,
            'b' => new Tiles\Bishop,
            'r' => new Tiles\Rook,
            'q' => new Tiles\Queen,
            'x' => new Tiles\King,
        );

        if(empty($entities[$tile]))
        {
            throw new \Exception('Invalid tile: ' . $tile);
        }

        $entity = $entities[$tile];
        $entity->init($game ? $game : $this);
        $entity->setCoords($x, $y);

        return $entity;
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

    private function isOwnKingAttacked()
    {
        $player = $this->getCurrentPlayer();
        $position = $this->getPosition();

        $x = $y = -1;
        foreach($position as $y => $row)
        {
            foreach($row as $x => $field)
            {
                if(($player == self::PLAYER_WHITE && $field == 'X') || ($player == self::PLAYER_BLACK && $field == 'x'))
                {
                    return $this->isAttacked($x, $y);
                }
            }
        }

        throw new \Exception('Failed to find ' . $this->getCurrentPlayer() . ' king!');
    }

    private function isValidField($x, $y)
    {
        return ( $x >= 0 && $x < self::BOARD_SIZE && $y >= 0 && $y < self::BOARD_SIZE );
    }

    private function isEmptyField($x, $y)
    {
        $position = $this->getPosition();
        return $position[$y][$x] == '_';
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
        $position = $this->getPosition(self::PLAYER_WHITE);
        foreach($position as $tileX => $row)
        {
            foreach($row as $tileY => $field)
            {
                if($this->isEnemyTile($tileX, $tileY))
                {
                    $tile = $this->getTileEntity($tileX, $tileY);
                    $moves = $tile->getMoves();
                    if(in_array(array('x' => $x, 'y' => $y), $moves))
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

    public function getLastMove($player = null)
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
        if(strpos($log, 'O-O-O') !== false)
        {
            $fromX = 4;
            $toX   = 2;
            $fromY = $toY = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 7 : 0;
        }
        elseif(strpos($log, 'O-O') !== false)
        {
            $fromX = 4;
            $toX   = 6;
            $fromY = $toY = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 7 : 0;
        }
        else
        {
            $delimiter = strpos($log, '-')===false ? ':' : '-';
            $log = explode($delimiter, $log);

            $posStart = substr($log[0], -2);
            $fromX = $this->convertLetterToNumber($posStart[0]);
            $fromY = $posStart[1] - 1;

            $posEnd = substr($log[1], 0, 2);
            $toX = $this->convertLetterToNumber($posEnd[0]);
            $toY = $posEnd[1] - 1;
        }

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
        $position = $this->getPosition();
        $tile = $this->getTileEntity($this->fromX, $this->fromY);

        $tile->move($this->toX, $this->toY);

        $this->log .= $tile->getMoveLog() . "\n";
        $this->setPosition($tile->getPosition());
        $this->switchPlayer();

        $possibleMoves = $this->getPossibleMoves();
        if(empty($possibleMoves))
        {
            if($this->isOwnKingAttacked())
            {
                $status = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 'black_won' : 'white_won';
            }
            else
            {
                $status = 'tie';
            }
            $this->setStatus($status);
        }
    }

    private function switchPlayer()
    {
        if($this->getCurrentPlayer() == self::PLAYER_WHITE)
        {
            $this->setCurrentPlayer(self::PLAYER_BLACK);
        }
        else
        {
            $this->setCurrentPlayer(self::PLAYER_WHITE);
        }
    }

    private function convertLetterToNumber($letter)
    {
        return strpos($this->columnLetters, $letter);
    }

}
