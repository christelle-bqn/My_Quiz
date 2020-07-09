<?php

namespace App\Controller;

use App\Form\GuestType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Security\Core\Security;

class HomepageController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
       $this->security = $security;
    }

    /**
     * @Route("/", name="homepage")
     */
    public function index()
    {
        /* $session = $request->getSession();
        $form = $this->createForm(GuestType::class);
        $form->handleRequest($request);

        if ($session->has('pseudo')) {
            return $this->redirectToRoute('quiz_index');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $form_pseudo = $data['pseudo'];
            $session->set('pseudo', $form_pseudo);
            return $this->redirectToRoute('quiz_index');
        } */

        $user = $this->security->getUser();
        
        if ($user) {
            return $this->redirectToRoute('quiz_index');
        } else {
            return $this->render('homepage/index.html.twig');
        }
    }
}
