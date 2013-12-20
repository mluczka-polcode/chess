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

    const MAX_POSITION_REPEATS = 3;
    const MAX_REVERSIBLE_MOVES = 50;

    private $statusValues = array(
        'in_progress',
        'white_won',
        'black_won',
        'tie',
    );

    private $board;

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
    private $history = '';

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
     * Set history
     *
     * @param string $player
     * @param string $status
     * @return Game
     */
    public function setHistory($history)
    {
        $this->history = $history;

        return $this;
    }

    /**
     * Get history
     *
     * @return string
     */
    public function getHistory()
    {
        return $this->history;
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

        if($this->log=='')
        {
            return $result;
        }

        $log = explode("\n", str_replace("\r", '', trim($this->log)));
        for($i = 0; $i < count($log); $i += 2)
        {
            $log[$i] = $this->getFormattedMove($log[$i], 'white');
            if(!empty($log[$i + 1]))
            {
                $log[$i + 1] = $this->getFormattedMove($log[$i + 1], 'black');
            }
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
    public function setTieProposal($proposal)
    {
        $this->tieProposal = $proposal;

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
            if(trim($this->log) == '')
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

    public function moveTile($params)
    {
        if($this->getStatus() != 'in_progress')
        {
            throw new ChessException('Game already finished', ChessException::INVALID_INPUT);
        }

        list($coordsFrom, $coordsTo) = $this->parseMoveCoords($params);

        $this->board->move($coordsFrom, $coordsTo, $params['advancePawnTo']);

        $this->setPosition($this->board->getPosition());
        $this->setCastlings($this->board->getCastlings());
        $this->addToHistory($this->getPosition());
        if($this->board->wasIrreversibleMove())
        {
            $this->history .= "---\n";
        }

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
        elseif($this->isTie())
        {
            $this->setStatus('tie');
        }
        elseif($checked)
        {
            $this->log .= '+';
        }

        $this->log .= "\n";
    }

    private function addToHistory($position)
    {
        $history = $this->getHistory();

        foreach($position as $row)
        {
            foreach($row as $field)
            {
                $history .= $field;
            }
        }

        $history .= ' '.$this->castlings;

        $history .= "\n";

        $this->setHistory($history);
    }

    private function isTie()
    {
        return (
            !$this->board->sufficientTilesForCheckmate()
            || $this->positionRepeatsCount() >= self::MAX_POSITION_REPEATS
            || $this->reversibleMovesCount() >= self::MAX_REVERSIBLE_MOVES
        );
    }

    private function positionRepeatsCount()
    {
        $count = 1;

        $history = $this->getHistory();
        $history = substr($history, strrpos($history, "---\n") + strlen("---\n"));
        $history = explode("\n", trim($history));

        $lastPosition = array_pop($history);
        foreach($history as $position)
        {
            if($position == $lastPosition)
            {
                $count += 1;
            }
        }

//         echo 'positionRepeatsCount: ' . $count . "\n<br />";
        return $count;
    }

    private function reversibleMovesCount()
    {
        $history = $this->getHistory();
        $history = substr($history, strrpos($history, "---\n") + strlen("---\n"));
        $history = explode("\n", trim($history));
//         echo 'reversibleMovesCount: ' . count($history) . "\n<br />";
        return count($history);
    }

    public function updateTieProposal($player, $message)
    {
        if($this->getStatus() != 'in_progress')
        {
            return;
        }

        $proposal = $this->getTieProposal();

        if($proposal == $this->getOpponent($player) . ' proposed')
        {
            if(in_array($message, array('propose', 'accept')))
            {
                $proposal = str_replace('proposed', 'accepted', $proposal);
                $this->setStatus('tie');
            }
            else
            {
                $proposal = str_replace('proposed', 'rejected', $proposal);
            }
        }
        elseif($proposal == $player . ' proposed' && $message == 'cancel')
        {
            $proposal = '';
        }
        elseif($proposal == '' && $message == 'propose')
        {
            $proposal = $player . ' proposed';
        }

        $this->setTieProposal($proposal);
    }

    public function surrender($player)
    {
        if($this->getStatus() != 'in_progress')
        {
            return false;
        }

        $status = $this->getOpponent() . '_won';
        $this->setStatus($status);
    }

    private function parseMoveCoords($params)
    {
        if($this->getCurrentPlayer() == self::PLAYER_BLACK)
        {
            $params['fromX'] = $this->getReverseCoord($params['fromX']);
            $params['fromY'] = $this->getReverseCoord($params['fromY']);
            $params['toX'] = $this->getReverseCoord($params['toX']);
            $params['toY'] = $this->getReverseCoord($params['toY']);
        }

        $coordsFrom = array(
            'x' => $params['fromX'],
            'y' => $params['fromY'],
        );

        $coordsTo = array(
            'x' => $params['toX'],
            'y' => $params['toY'],
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
        $this->setCurrentPlayer($this->getOpponent());
    }

    private function getOpponent($player = null)
    {
        if(!$player)
        {
            $player = $this->getCurrentPlayer();
        }
        return $player == self::PLAYER_WHITE ? self::PLAYER_BLACK : self::PLAYER_WHITE;
    }

    private function getFormattedMove($move, $color)
    {
        if(in_array($move, array('O-O', 'O-O-O')))
        {
            return $move;
        }

        if(strtoupper($move[0]) != $move[0])
        {
            $pawn = $color == 'white' ? '&#9817;' : '&#9823;';
            return $pawn . $move;
        }

        $convert = array(
            'white' => array(
                'K' => '&#9816;',
                'B' => '&#9815;',
                'R' => '&#9814;',
                'Q' => '&#9813;',
                'X' => '&#9812;',
            ),
            'black' => array(
                'K' => '&#9822;',
                'B' => '&#9821;',
                'R' => '&#9820;',
                'Q' => '&#9819;',
                'X' => '&#9818;',
            ),
        );

        return str_replace(array_keys($convert[$color]), array_values($convert[$color]), $move);
    }

    private function convertLetterToNumber($letter)
    {
        $columnLetters = 'abcdefgh';
        return strpos($columnLetters, $letter);
    }

}
