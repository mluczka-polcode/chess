<?php

namespace Acme\ChessBundle\Entity\Tiles;

class EmptyField extends Tile
{
    public function getName()
    {
        return '';
    }

    public function getMoves($mode = 'all')
    {
        return array();
    }
}
