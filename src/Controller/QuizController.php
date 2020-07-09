<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Entity\Reponse;
use App\Entity\Question;
use App\Entity\History;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Security\Core\Security;

class QuizController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
       $this->security = $security;
    }

    /**
     * @Route("/quiz", name="quiz_index")
     */
    public function index(): Response
    {
        $user = $this->security->getUser();

        $categories = $this->getDoctrine()->getRepository(Categorie::class)->findAll();

        $img_quiz = [
            "harry.jpg", 
            "sigles.jpg",
            "mots.jpg",
            "cuisine.jpg",   
            "simpson1.jpg",
            "simpson2.jpg", 
            "stargate.jpg",
            "ncis.jpeg",
            "jeux-de-societe.jpg",
            "programmation.jpg", 
            "sigles_info.png",
        ];

        $serializer = SerializerBuilder::create()->build();
        $json_categories = $serializer->serialize($categories, 'json');
        $category = json_decode($json_categories, JSON_OBJECT_AS_ARRAY);
        $array_quiz = array_combine($img_quiz, $category);

        if ($user) {
            $serializer = SerializerBuilder::create()->build();
            $json_user = $serializer->serialize($user, 'json');
            $array_user = json_decode($json_user, JSON_OBJECT_AS_ARRAY);

            if ($array_user['is_verified'] === false) {
                return $this->render('security/verification_email.html.twig');
            } else {
                return $this->render('quiz/index.html.twig', [
                    'array_quiz' => $array_quiz,
                ]);
            }
        }
    
        return $this->render('quiz/index.html.twig', [
            'array_quiz' => $array_quiz,
        ]);
    }

    /**
     * @Route("/quiz/{category}", name="quiz_show", requirements={"category"="\d+"})
     */
    public function show(Request $request, Categorie $category)
    {
        $categories = $this->getDoctrine()->getRepository(Categorie::class)->find($category);
        $question = $request->query->get('question_id') ?: 1;
        $question = $category->getQuestions()->get($question - 1);
        $answers = $question->getReponses()->getValues();
        shuffle($answers);

        return $this->render('quiz/show.html.twig', [
            'category' => $categories,
            'question' => $question,
            'answers' => $answers
        ]);

    }

    /**
     * @Route("/quiz/check/{question}/", name="quiz_check", requirements={"question"="\d+"}))
     */
    public function checkAnswer(Request $request, Question $question, Reponse $reponse)
    {
        $user = $this->security->getUser();

        if ($request->hasSession() && ($session = $request->getSession())) {
            $score = $session->get('score');
        }

        if (!$user && $request->hasSession() && ($session = $request->getSession())) {
            $history = $session->get('history', []);
        }

        $category = $question->getCategorie();
        $question_id = $question->getId();
        $answer = $request->request->get('answer');
        $category_id =  $category->getId();
        $category_name = $category->getName();

        $questions = $category->getQuestions()->getValues();
        $answer_expected = $reponse->getReponseExpected();
        $answer_expected_reponse = $reponse->getReponse($answer_expected);

        $entityManager = $this->getDoctrine()->getManager();

        $serializer = SerializerBuilder::create()->build();
        $json_category = $serializer->serialize($category, 'json');
        $category = json_decode($json_category, JSON_OBJECT_AS_ARRAY);

        $flashBag = $this->get('session')->getFlashBag();
        foreach ($flashBag->keys() as $type) {
            $flashBag->set($type, null);
        }

        if ($answer != $answer_expected_reponse) {
            $this->addFlash('danger', 'Mauvaise réponse ! La bonne réponse était : '.$answer_expected_reponse);
        } else {
            $this->addFlash('success', 'Bonne réponse !');
            $score++;
            $session->set('score', $score);
        }

        foreach ($questions as $key => $question) {
            if ($question->getId() === $question_id && ($key + 2) <= 10) {
                $question_id = $key + 2;
            break;
            } else if ($key + 2 == 10) {
                $this->addFlash('result', 'Score : '.$score.' / 10');

                if ($user) {
                    $user_info = $this->getDoctrine()->getRepository(User::class)->find($user->getId());
                    $category_id = $this->getDoctrine()->getRepository(Categorie::class)->find($category_id);
                    $date = new \DateTime('@'.strtotime('now'));
                    $history = new History();
                    $history->setUser($user_info);
                    $history->setCategorie($category_id);
                    $history->setScore($score);
                    $history->setCreatedAt($date);
        
                    $entityManager->persist($history);
        
                    $entityManager->flush();
                } else {
                    $date = date("Y-m-d").', '. date("H:i:s") .''; 
                    array_push($history, [
                        'category' => $category_name,
                        'score' => $score,
                        'date' => $date
                    ]);
                    $session->set('history', $history);
                }

                $session->set('score', 0);

                return $this->render('quiz/result.html.twig', ['category' => $category]);
            break;
            }
        }

        $url = parse_url($_SERVER["REQUEST_URI"], PHP_URL_HOST); 
    
        return $this->redirect($url.'/quiz/'.$category['id'].'/?question_id='.$question_id.'');
    }
}
