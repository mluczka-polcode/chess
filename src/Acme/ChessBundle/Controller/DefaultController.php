<?php

namespace Acme\ChessBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Acme\ChessBundle\Entity\Game;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AcmeChessBundle:Default:index.html.twig');
    }

    public function createTableAction($color)
    {
        $tableId = md5(time());
        
        return $this->redirect($this->generateUrl('chess_table', array(
            'tableId' => $tableId,
            'color'   => $color,
        )));
    }

    public function tableAction($tableId, $color)
    {
        $game = $this->getDoctrine()->getRepository('AcmeChessBundle:Game')->findOneBy(
            array(
                'tableId' => $tableId,
                'status'  => 'in_progress',
            )
        );

        if(!$game)
        {
            $game = new Game();
            $game->setTableId($tableId);
            $game->setPosition($game->getStartPosition());
        }

        return $this->render('AcmeChessBundle:Default:table.html.twig', array(
            'tableId'  => $tableId,
            'color'    => $color,
            'position' => $game->getPositionAsArray($color),
            'log'      => $game->getLogAsArray(),
            'status'   => $game->getStatus(),
            'lastMove' => $game->getLastMove($color),
            'currentPlayer' => $game->getCurrentPlayer(),
        ));
    }

    public function startGameAction($tableId)
    {
        $game = new Game();
        $game->setTableId($tableId);
        $game->setPosition($game->getStartPosition());

        $em = $this->getDoctrine()->getManager();
        $em->persist($game);
        $em->flush();

        return $this->redirect($this->generateUrl('chess_table', array(
            'tableId' => $tableId,
            'color'   => 'white',
        )));
    }

    public function moveTileAction($tableId, $fromX, $fromY, $toX, $toY)
    {
        $game = $this->getGame($tableId);

        $game->setMoveCoords($fromX, $fromY, $toX, $toY);
        $game->moveTile();

        $em = $this->getDoctrine()->getManager();
        $em->persist($game);
        $em->flush();

        return new Response('ok');
    }

    public function checkGameStateAction($tableId, $color)
    {
        $game = $this->getGame($tableId);

        return new JsonResponse(array(
            'position' => $game->getPositionAsArray($color),
            'log'      => $game->getLogAsArray(),
            'status'   => $game->getStatus(),
            'lastMove' => $game->getLastMove($color),
            'currentPlayer' => $game->getCurrentPlayer(),
        ));
    }

    public function proposeTieAction($tableId, $color)
    {
        $game = $this->getGame($tableId);

//         $game->
    }

    private function getGame($tableId)
    {
        $game = $this->getDoctrine()->getRepository('AcmeChessBundle:Game')->findOneBy(
            array(
                'tableId' => $tableId,
                'status'  => 'in_progress',
            )
        );

        if(!$game)
        {
            throw $this->createNotFoundException('No game found for tableId ' . $tableId);
        }

        return $game;
    }
}
