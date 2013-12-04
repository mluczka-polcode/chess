<?php

namespace Acme\ChessBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Game
 */
class Game
{
    private $statusValues = array(
        'in_progress',
        'white_won',
        'black_won',
        'tie',
    );

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
            'RKBQKBKR'
           .'PPPPPPPP'
           .'        '
           .'        '
           .'        '
           .'        '
           .'pppppppp'
           .'rkbqkbkr'
       );
    }

    public function moveTile($fromX, $fromY, $toX, $toY)
    {
        if(!$this->isValidMove($fromX, $fromY, $toX, $toY))
        {
            throw new Exception('Invalid move!');
        }

        $fromX = $this->convertNumberToLetter($fromX);
        $toX   = $this->convertNumberToLetter($toX);

        $this->log .= $fromX.$fromY.'-'.$toX.$toY."\n";
    }

    private function isValidMove($fromX, $fromY, $toX, $toY)
    {
        // ...

        return true;
    }

    private function convertNumberToLetter($number)
    {
        $letters = 'abcdefgh';
        return $letters[$number-1];
    }

}
