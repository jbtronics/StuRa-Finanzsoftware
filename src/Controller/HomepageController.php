<?php


namespace App\Controller;


use App\Entity\PaymentOrder;
use App\Form\PaymentOrderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends AbstractController
{

    /**
     * @Route("/", name="homepage")
     * @return Response
     */
    public function homepage(Request $request): Response
    {


        return $this->render('homepage.html.twig');
    }
}