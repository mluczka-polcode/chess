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
            )
        );

        if(!$game)
        {
            $game = new Game();
            $game->setTableId($tableId);
            $game->setPosition($game->getStartPosition());

            $this->save($game);
        }

        return $this->render('AcmeChessBundle:Default:table.html.twig', array(
            'tableId'       => $tableId,
            'color'         => $color,
            'position'      => $game->getPositionAsArray($color),
            'log'           => $game->getLogAsArray(),
            'status'        => $game->getStatus(),
            'lastMove'      => $game->getLastMove($color),
            'tieProposal'   => $game->getTieProposal(),
            'currentPlayer' => $game->getCurrentPlayer(),
        ));
    }

    public function moveTileAction($tableId, $fromX, $fromY, $toX, $toY)
    {
        $game = $this->getGame($tableId);

        $game->setMoveCoords($fromX, $fromY, $toX, $toY);
        $game->moveTile();

        $this->save($game);

        return new Response('ok');
    }

    public function checkGameStateAction($tableId, $player)
    {
        $game = $this->getGame($tableId);

        return new JsonResponse(array(
            'position'      => $game->getPositionAsArray($player),
            'log'           => $game->getLogAsArray(),
            'status'        => $game->getStatus(),
            'lastMove'      => $game->getLastMove($player),
            'tieProposal'   => $game->getTieProposal(),
            'currentPlayer' => $game->getCurrentPlayer(),
        ));
    }

    public function proposeTieAction($tableId, $player)
    {
        $game = $this->getGame($tableId);
        $game->setTieProposal($player);

        $this->save($game);

        return new Response('ok');
    }

    private function getGame($tableId)
    {
        $game = $this->getDoctrine()->getRepository('AcmeChessBundle:Game')->findOneBy(
            array(
                'tableId' => $tableId,
            )
        );

        if(!$game)
        {
            throw $this->createNotFoundException('No game found for tableId ' . $tableId);
        }

        return $game;
    }

    private function save($entity)
    {
        $em = $this->getDoctrine()->getManager();
        $em->persist($entity);
        $em->flush();
    }
}
