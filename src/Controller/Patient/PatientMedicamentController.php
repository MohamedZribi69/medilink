<?php

namespace App\Controller\Patient;

use App\Repository\MedicamentRepository;
use App\Repository\OrdonnanceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/patient')]
#[IsGranted('ROLE_USER')]
final class PatientMedicamentController extends AbstractController
{
    #[Route('/medicaments', name: 'patient_medicaments_index', methods: ['GET'])]
    public function index(MedicamentRepository $repo): Response
    {
        $medicaments = $repo->findAllOrderByNom();
        return $this->render('patient/medicaments_index.html.twig', [
            'medicaments' => $medicaments,
        ]);
    }

    #[Route('/medicaments/{id}', name: 'patient_medicament_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(int $id, MedicamentRepository $medRepo, OrdonnanceRepository $ordRepo): Response
    {
        $medicament = $medRepo->find($id);
        if (!$medicament) {
            throw $this->createNotFoundException('Médicament introuvable.');
        }

        $patient = $this->getUser();
        $ordonnances = $ordRepo->findByPatientAndMedicament($patient, $id);

        return $this->render('patient/medicament_show.html.twig', [
            'medicament' => $medicament,
            'ordonnances' => $ordonnances,
        ]);
    }
}
