<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Participation;
use App\Form\ParticipationType;
use App\Repository\EvenementRepository;
use App\Repository\ParticipationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/events')]
final class EventController extends AbstractController
{
    #[Route('', name: 'app_events_index')]
    public function index(EvenementRepository $repo): Response
    {
        $evenements = $repo->findAllOrderByDate();

        return $this->render('event/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/{id}', name: 'app_events_show', requirements: ['id' => '\d+'])]
    public function show(
        Evenement $evenement,
        Request $request,
        EntityManagerInterface $em,
        ParticipationRepository $participationRepo
    ): Response {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $participation = $participationRepo->findOneByUserAndEvenement($user, $evenement);
        $form = null;

        if (!$participation) {
            $participation = new Participation();
            $participation->setEvenement($evenement);
            $participation->setUser($user);
            $participation->setStatut(Participation::STATUT_EN_ATTENTE);

            $form = $this->createForm(ParticipationType::class, $participation);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em->persist($participation);
                $em->flush();
                $this->addFlash('success', 'Votre participation a été enregistrée.');
                return $this->redirectToRoute('app_events_show', ['id' => $evenement->getId()]);
            }
        }

        return $this->render('event/show.html.twig', [
            'evenement' => $evenement,
            'participation' => $participation,
            'form' => $form?->createView(),
        ]);
    }
}
