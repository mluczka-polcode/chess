<?php

namespace Acme\ChessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Game
 */
class Game
{
    const BOARD_SIZE = 8;

    private $statusValues = array(
        'in_progress',
        'white_won',
        'black_won',
        'tie',
    );

    private $kingAttacked = false;

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
            $position = implode('', $position);
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
     * Set status
     *
     * @param string $status
     * @return Game
     */
    public function setStatus($status)
    {
        if(!in_array($status, $this->statusValues))
        {
            throw new Exception('Invalid game status: '.$status);
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
       return(
            'RKBQXBKR'
           .'PPPPPPPP'
           .'________'
           .'________'
           .'________'
           .'________'
           .'pppppppp'
           .'rkbqxbkr'
       );
    }

    public function getPositionAsArray()
    {
        $result = array();

        $position = explode("\n", trim(chunk_split($this->position, self::BOARD_SIZE, "\n")));
        foreach($position as $row)
        {
            $result[] = str_split($row);
        }

        return $result;
    }

    public function getLogAsArray()
    {
        $result = array();
        $log = explode("\n", str_replace("\r", '', trim($this->log)));
        for($i = 0; $i < count($log); $i += 2)
        {
            $result[] = $log[$i].(!empty($log[$i+1]) ? ' '.$log[$i+1] : '');
        }
        return $result;
    }

    public function getCurrentPlayer()
    {
        if(empty($this->log))
        {
            return 'white';
        }

        $log = explode("\n", trim($this->log));
        if(count($log) % 2)
        {
            return 'black';
        }
        else
        {
            return 'white';
        }
    }

    public function moveTile($fromX, $fromY, $toX, $toY)
    {
        if(!$this->isValidMove($fromX, $fromY, $toX, $toY))
        {
            throw new Exception('Invalid move!');
        }

        $position = $this->getPositionAsArray();
        $tile = $position[$fromY][$fromX];
//         if($tile == 'P' && $toY == self::BOARD_SIZE - 1)
//         {
//             $tile = 'Q';
//         }
//         elseif($tile == 'p' && $toY == 0)
//         {
//             $tile = 'q';
//         }
        $position[$toY][$toX] = $tile;
        $position[$fromY][$fromX] = '_';
        $this->setPosition($position);

        if($this->ownKingAttacked())
        {
            throw new Exception('Invalid move!');
        }

        if($this->enemyKingAttacked())
        {
            $this->kingAttacked = true;
        }

        $fromX = $this->convertNumberToLetter($fromX);
        $toX   = $this->convertNumberToLetter($toX);
        $fromY += 1;
        $toY   += 1;

        $this->log .= $fromX.$fromY.'-'.$toX.$toY."\n";
    }

    private function isValidMove($fromX, $fromY, $toX, $toY)
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

    private function convertNumberToLetter($number)
    {
        $letters = 'abcdefgh';
        return $letters[$number];
    }

}
