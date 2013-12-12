<?php

namespace Acme\ChessBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Acme\ChessBundle\Entity\Game;
use Acme\ChessBundle\Exception\ChessException;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('AcmeChessBundle:Default:index.html.twig');
    }

    public function createTableAction($player)
    {
        $tableId = md5(time());

        return $this->redirect($this->generateUrl('chess_table', array(
            'tableId' => $tableId,
            'player'  => $player,
        )));
    }

    public function tableAction($tableId, $player)
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

            $this->save($game);
        }

        return $this->render('AcmeChessBundle:Default:table.html.twig', array(
            'gamestate' => $game->getGameState($player),
        ));
    }

    public function moveTileAction($tableId)
    {
        $request = $this->get('request');

        $fromX = $request->get('fromX');
        $fromY = $request->get('fromY');
        $toX = $request->get('toX');
        $toY = $request->get('toY');

//         print_r($request->all());

        $game = $this->getGame($tableId);
        $player = $game->getCurrentPlayer();

        $game->moveTile($fromX, $fromY, $toX, $toY);

        $this->save($game);

        return new JsonResponse($game->getGameState($player));
    }

    public function checkGameStateAction($tableId, $player)
    {
        $game = $this->getGame($tableId);
        return new JsonResponse($game->getGameState($player));
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
