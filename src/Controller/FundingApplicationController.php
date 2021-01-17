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
use App\Form\FundingApplication\InternalFundingApplicationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/funding_application")
 * @package App\Controller
 */
class FundingApplicationController extends AbstractController
{
    /**
     * @Route("/internal/new")
     * @return Response
     */
    public function newInternal(Request $request, EntityManagerInterface $entityManager): Response
    {
        $funding_application = new FundingApplication();

        $form = $this->createForm(InternalFundingApplicationType::class, $funding_application);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($funding_application);
            $entityManager->flush();

            return $this->redirectToRoute('homepage');
        }

        return $this->render('FundingApplication/new_internal.html.twig', [
            'form' => $form->createView(),
            'entity' => $funding_application
        ]);
    }
}