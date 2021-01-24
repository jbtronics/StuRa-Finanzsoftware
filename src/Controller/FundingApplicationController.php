<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Controller;


use App\Entity\FundingApplication;
use App\Entity\PaymentOrder;
use App\Form\FundingApplication\ExternalFundingApplicationType;
use App\Form\FundingApplication\InternalFundingApplicationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/funding_application")
 * @package App\Controller
 */
class FundingApplicationController extends AbstractController
{
    private $rateLimiter;
    private $entityManager;

    public function __construct(RateLimiterFactory $fundingApplicationSubmitLimiter, EntityManagerInterface $entityManager)
    {
        $this->rateLimiter = $fundingApplicationSubmitLimiter;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/internal/new", name="funding_application_internal_new")
     * @return Response
     */
    public function newInternal(Request $request): Response
    {
        $limiter = $this->rateLimiter->create($request->getClientIp());

        $funding_application = new FundingApplication();
        $funding_application->setExternalFunding(false);

        $form = $this->createForm(InternalFundingApplicationType::class, $funding_application);
        if (!$form instanceof Form) {
            throw new \LogicException('$form must be a Form object!');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* Limit the amount of how many payment orders can be submitted by one user in an hour
                    This prevents automatic mass creation of payment orders and also prevents that skip token can
                    guessed by brute force */
            $limiter->consume(1)->ensureAccepted();


            $this->entityManager->persist($funding_application);
            $this->entityManager->flush();

            $this->addFlash('success', 'flash.saved_successfully');

            //Redirect to homepage, if no further paymentOrders should be submitted
            //Otherwise create a new form for further ones
            if ('submit' === $form->getClickedButton()->getName()) {
                return $this->redirectToRoute('homepage');
            }

            if ('submit_new' === $form->getClickedButton()->getName()) {
                $old_application = $funding_application;
                $funding_application = new FundingApplication();
                $this->copyProperties($old_application, $funding_application);

                $form = $this->createForm(InternalFundingApplicationType::class, $funding_application);
            }
        }

        $limit = $limiter->consume(0);

        $response = $this->render('FundingApplication/new_internal.html.twig', [
            'form' => $form->createView(),
            'entity' => $funding_application
        ]);

        $response->headers->add(
            [
                'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp(),
                'X-RateLimit-Limit' => $limit->getLimit(),
            ]
        );

        return $response;
    }

    /**
     * @Route("/external/new", name="funding_application_external_new")
     * @return Response
     */
    public function newExternal(Request $request): Response
    {
        $limiter = $this->rateLimiter->create($request->getClientIp());

        $funding_application = new FundingApplication();
        $funding_application->setExternalFunding(true);

        $form = $this->createForm(ExternalFundingApplicationType::class, $funding_application);
        if (!$form instanceof Form) {
            throw new \LogicException('$form must be a Form object!');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /* Limit the amount of how many payment orders can be submitted by one user in an hour
                    This prevents automatic mass creation of payment orders and also prevents that skip token can
                    guessed by brute force */
            $limiter->consume(1)->ensureAccepted();


            $this->entityManager->persist($funding_application);
            $this->entityManager->flush();

            $this->addFlash('success', 'flash.saved_successfully');

            //Redirect to homepage, if no further paymentOrders should be submitted
            //Otherwise create a new form for further ones
            if ('submit' === $form->getClickedButton()->getName()) {
                return $this->redirectToRoute('homepage');
            }

            if ('submit_new' === $form->getClickedButton()->getName()) {
                $old_application = $funding_application;
                $funding_application = new FundingApplication();
                $this->copyProperties($old_application, $funding_application);

                $form = $this->createForm(ExternalFundingApplicationType::class, $funding_application);
            }
        }

        $limit = $limiter->consume(0);

        $response = $this->render('FundingApplication/new_external.html.twig', [
            'form' => $form->createView(),
            'entity' => $funding_application
        ]);

        $response->headers->add(
            [
                'X-RateLimit-Remaining' => $limit->getRemainingTokens(),
                'X-RateLimit-Retry-After' => $limit->getRetryAfter()->getTimestamp(),
                'X-RateLimit-Limit' => $limit->getLimit(),
            ]
        );

        return $response;
    }

    private function copyProperties(FundingApplication $source, FundingApplication $target): void
    {
        $target->setApplicantName($source->getApplicantName());
        $target->setApplicantEmail($source->getApplicantEmail());
        $target->setApplicantDepartment($source->getApplicantDepartment());
        $target->setApplicantOrganisationName($source->getApplicantOrganisationName());
        $target->setApplicantPhone($source->getApplicantPhone());
        $target->setApplicantAddress($source->getApplicantAddress());
    }
}