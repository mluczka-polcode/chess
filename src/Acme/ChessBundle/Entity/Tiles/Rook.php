<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Rook extends Tile
{
    public function getMoves()
    {
        return $this->getLongMoves($this->straightMoves);
    }

    public function move($toX, $toY)
    {
        if($this->x == 0 && $this->y == $this->getFirstLine())
        {
            $player = $this->getCurrentPlayer();

            $castlings = $this->getCastlings();
            $castlings[$player] = in_array($castlings[$player], array('both', 'short')) ? 'short' : 'none';
            $this->game->setCastlings($castlings);
        }
        elseif($this->x == self::BOARD_SIZE - 1 && $this->y == $this->getFirstLine())
        {
            $player = $this->getCurrentPlayer();

            $castlings = $this->getCastlings();
            $castlings[$player] = in_array($castlings[$player], array('both', 'long')) ? 'long' : 'none';
            $this->game->setCastlings($castlings);
        }

        parent::move($toX, $toY);
    }

    private function getFirstLine()
    {
        return ( $this->getCurrentPlayer() == self::PLAYER_WHITE ? 0 : self::BOARD_SIZE - 1 );
    }

}
