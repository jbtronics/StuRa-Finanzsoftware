<?php


namespace App\Controller;


use App\Entity\PaymentOrder;
use App\Form\PaymentOrderType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/payment_order")
 * @package App\Controller
 */
class PaymentOrderController extends AbstractController
{

    /**
     * @Route("/new", name="payment_order_new")
     * @return Response
     */
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $new_order = new PaymentOrder();

        $form = $this->createForm(PaymentOrderType::class, $new_order);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {

                $entityManager->persist($new_order);
                $entityManager->flush();

                $this->addFlash('success', 'flash.saved_successfully');

                //Redirect to homepage
                return $this->redirectToRoute('homepage');
            } else {
                $this->addFlash('error', 'flash.error.check_input');
            }
        }

        return $this->render('PaymentOrder/form.html.twig', [
            'form' => $form->createView(),
            'entity' => $new_order,
        ]);
    }
}