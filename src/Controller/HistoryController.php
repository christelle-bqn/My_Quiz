<?php

namespace App\Controller;

use App\Entity\History;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class HistoryController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
       $this->security = $security;
    }

    /**
     * @Route("/history", name="history_index")
     */
    public function index(SessionInterface $session)
    {
        $user = $this->security->getUser();

        if ($user) {
            $histories = $this->getDoctrine()->getRepository(History::class)->findBy([
                'user' => $this->getUser()->getId()
            ]);
            
            return $this->render('history/index.html.twig', [
                'histories' => $histories
            ]);
        } else {
            $session_histories = $session->get('history', []);

            return $this->render('history/index.html.twig', [
                'session_histories' => $session_histories,
            ]);
        }
    }
}
