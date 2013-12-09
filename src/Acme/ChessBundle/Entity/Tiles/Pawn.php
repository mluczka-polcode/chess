<?php

namespace Acme\ChessBundle\Entity\Tiles;

class Pawn extends Tile
{
    public function getMoves($x, $y)
    {
        $moves = array();

        $modifier = $this->getCurrentPlayer() == self::PLAYER_WHITE ? 1 : -1;

        // move
        if($this->isEmptyField($x, $y + $modifier))
        {
            $moves[] = array(
                'x' => $x,
                'y' => $y + $modifier,
            );

            if(($y == 1 || $y == 6) && $this->isEmptyField($x, $y + (2 * $modifier)))
            {
                $moves[] = array(
                    'x' => $x,
                    'y' => $y + (2 * $modifier),
                );
            }
        }

        // beat
        if($this->isEnemyTile($x - 1, $y + $modifier))
        {
            $moves[] = array(
                'x' => $x - 1,
                'y' => $y + $modifier,
            );
        }

        if($this->isEnemyTile($x + 1, $y + $modifier))
        {
            $moves[] = array(
                'x' => $x + 1,
                'y' => $y + $modifier,
            );
        }

        // en passant
        $lastMove = $this->getLastMove();
//         echo $x.', '.$y."\n";
//         echo '<pre>'.print_r($lastMove, true).'</pre>';
        $lastX = $lastMove['toX'];
        $lastY = $lastMove['toY'];
        if(abs($lastX - $x) == 1 && $this->isEnemyTile($lastX, $lastY) && $this->isPawn($lastX, $lastY) && abs($lastY - $lastMove['fromY']) == 2)
        {
            $moves[] = array('x' => $lastX, 'y' => $y + $modifier);
        }

        return $moves;
    }
}
