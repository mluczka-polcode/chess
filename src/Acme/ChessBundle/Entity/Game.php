<?php

namespace Acme\ChessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Acme\ChessBundle\Entity\Tiles;
use Acme\ChessBundle\Exception\ChessException;

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

    private $board;

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

    public function __construct()
    {
        $this->onLoad();
    }

    /** @PostLoad */
    public function onLoad()
    {
// $position = $this->getPosition();
// foreach($position as &$row)
// {
//     foreach($row as &$field)
//     {
//         $field = '_';
//     }
// }
// $position[0][0] = 'X';
// $position[7][7] = 'x';
// $this->setPosition($position);

        $this->board = new Chessboard();
        $this->board->setPosition($this->getPosition());
        $this->board->setLastMove($this->getLastMove());
        $this->board->setCastlings($this->getCastlings());
    }

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
    public function getPosition()
    {
        if(!empty($this->positionArray))
        {
            return $this->positionArray;
        }

        $result = array();

        $position = explode("\n", trim(str_replace("\r", '', $this->position)));
        foreach($position as $row)
        {
            $result[] = str_split($row);
        }

        $this->positionArray = $result;

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

    public function getGameState($player)
    {
        $gameState = array(
            'tableId'       => $this->tableId,
            'color'         => $player,
            'position'      => $this->getPosition(),
            'possibleMoves' => $this->getPossibleMoves($player),
            'log'           => $this->getLog(),
            'status'        => $this->getStatus(),
            'lastMove'      => $this->getLastMove(),
            'castlings'     => $this->getCastlings(),
            'tieProposal'   => $this->getTieProposal(),
            'currentPlayer' => $this->getCurrentPlayer(),
            'kingAttacked'  => $this->isKingAttacked(),
        );

        if($player == self::PLAYER_BLACK)
        {
            $gameState['position'] = $this->reversePosition($gameState['position']);
            $gameState['possibleMoves'] = $this->reverseMoves($gameState['possibleMoves']);
            if(!empty($gameState['lastMove']))
            {
                foreach($gameState['lastMove'] as &$coord)
                {
                    $coord = $this->getReverseCoord($coord);
                }
            }
        }

        return $gameState;
    }

    public function getLastMove()
    {
        if(trim($this->log) == '')
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

        return array(
            'fromX' => $fromX,
            'fromY' => $fromY,
            'toX' => $toX,
            'toY' => $toY,
        );
    }

    public function moveTile($fromX, $fromY, $toX, $toY)
    {
        if($this->getStatus() != 'in_progress')
        {
            throw new ChessException('Game already finished', ChessException::INVALID_PARAMS);
        }

        list($coordsFrom, $coordsTo) = $this->parseMoveCoords($fromX, $fromY, $toX, $toY);

        $this->board->move($coordsFrom, $coordsTo);

        $this->setPosition($this->board->getPosition());
        $this->setCastlings($this->board->getCastlings());

        $this->log .= $this->board->getMoveLog();

        $this->switchPlayer();

        $checked = $this->isKingAttacked();

        $possibleMoves = $this->getPossibleMoves();
        if(empty($possibleMoves))
        {
            if($checked)
            {
                $status = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 'black_won' : 'white_won';
                $this->log .= 'x';
            }
            else
            {
                $status = 'tie';
            }
            $this->setStatus($status);
        }
        elseif($this->board->isTie())
        {
            $this->setStatus('tie');
            return;
        }
        elseif($checked)
        {
            $this->log .= '+';
        }

        $this->log .= "\n";
    }

    private function parseMoveCoords($fromX, $fromY, $toX, $toY)
    {
        if($this->getCurrentPlayer() == self::PLAYER_BLACK)
        {
            $fromX = $this->getReverseCoord($fromX);
            $fromY = $this->getReverseCoord($fromY);
            $toX = $this->getReverseCoord($toX);
            $toY = $this->getReverseCoord($toY);
        }

        $coordsFrom = array(
            'x' => $fromX,
            'y' => $fromY,
        );

        $coordsTo = array(
            'x' => $toX,
            'y' => $toY,
        );

        return array($coordsFrom, $coordsTo);
    }

    private function reversePosition($data)
    {
        foreach($data as $y => &$row)
        {
            $row = array_reverse($row);
        }

        $data = array_reverse($data);

        return $data;
    }

    private function reverseMoves($data)
    {
        $out = array();

        foreach($data as $y => $row)
        {
            foreach($row as $x => $field)
            {
                foreach($field as $move)
                {
                    $out[$this->getReverseCoord($y)][$this->getReverseCoord($x)][] = array(
                        'x' => $this->getReverseCoord($move['x']),
                        'y' => $this->getReverseCoord($move['y']),
                    );
                }
            }
        }

        ksort($out);

        return $out;
    }

    private function getReverseCoord($coord)
    {
        if(!preg_match('/\d/', $coord) || $coord < 0 || $coord >= self::BOARD_SIZE)
        {
            throw new \Exception('Invalid coord: "'.$coord.'"');
        }

        $coord = (self::BOARD_SIZE - 1) - $coord;

        return $coord;
    }

    private function getPossibleMoves($player = null)
    {
        if(!$player)
        {
            $player = $this->getCurrentPlayer();
        }
        elseif($player != $this->getCurrentPlayer())
        {
            return array();
        }

        return $this->board->getPossibleMoves($player);
    }

    private function isKingAttacked()
    {
        return $this->board->isKingAttacked($this->getCurrentPlayer());
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
