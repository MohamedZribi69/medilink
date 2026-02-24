<?php

namespace App\Controller\Medecin;

use App\Entity\Ordonnance;
use App\Form\Medecin\OrdonnanceType;
use App\Repository\MedicamentRepository;
use App\Repository\OrdonnanceRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/medecin')]
#[IsGranted('ROLE_MEDECIN')]
final class OrdonnanceController extends AbstractController
{
    #[Route('/ordonnances', name: 'medecin_ordonnances_index', methods: ['GET'])]
    public function index(OrdonnanceRepository $repo): Response
    {
        $ordonnances = $repo->findByMedecin($this->getUser());
        return $this->render('medecin/ordonnance/index.html.twig', [
            'ordonnances' => $ordonnances,
        ]);
    }

    #[Route('/ordonnances/new', name: 'medecin_ordonnances_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        $ordonnance = new Ordonnance();
        $ordonnance->setMedecin($this->getUser());

        $form = $this->createForm(OrdonnanceType::class, $ordonnance, [
            'patients' => $userRepo->findPatients(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($ordonnance);
            $em->flush();
            $this->addFlash('success', 'Ordonnance créée.');
            return $this->redirectToRoute('medecin_ordonnances_index');
        }

        return $this->render('medecin/ordonnance/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/ordonnances/{id}', name: 'medecin_ordonnances_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Ordonnance $ordonnance): Response
    {
        if ($ordonnance->getMedecin() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        return $this->render('medecin/ordonnance/show.html.twig', [
            'ordonnance' => $ordonnance,
        ]);
    }

    #[Route('/ordonnances/{id}/edit', name: 'medecin_ordonnances_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function edit(Ordonnance $ordonnance, Request $request, EntityManagerInterface $em, UserRepository $userRepo): Response
    {
        if ($ordonnance->getMedecin() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(OrdonnanceType::class, $ordonnance, [
            'patients' => $userRepo->findPatients(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Ordonnance modifiée.');
            return $this->redirectToRoute('medecin_ordonnances_index');
        }

        return $this->render('medecin/ordonnance/edit.html.twig', [
            'form' => $form->createView(),
            'ordonnance' => $ordonnance,
        ]);
    }

    #[Route('/ordonnances/{id}/delete', name: 'medecin_ordonnances_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Ordonnance $ordonnance, Request $request, EntityManagerInterface $em): Response
    {
        if ($ordonnance->getMedecin() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_ordonnance_' . $ordonnance->getId(), $token)) {
            throw $this->createAccessDeniedException();
        }
        $em->remove($ordonnance);
        $em->flush();
        $this->addFlash('success', 'Ordonnance supprimée.');
        return $this->redirectToRoute('medecin_ordonnances_index');
    }

    #[Route('/medicaments', name: 'medecin_medicaments_index', methods: ['GET'])]
    public function medicaments(MedicamentRepository $repo): Response
    {
        $medicaments = $repo->findAllOrderByNom();
        return $this->render('medecin/medicaments.html.twig', [
            'medicaments' => $medicaments,
        ]);
    }
}
