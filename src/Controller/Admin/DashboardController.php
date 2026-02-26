<?php
// src/Controller/Admin/DashboardController.php
namespace App\Controller\Admin;

use App\Repository\DonsRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\GaugeChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(DonsRepository $donRepository): Response
    {
        $donsEnAttente = $donRepository->findBy(
            ['statut' => 'en_attente'],
            ['dateSoumission' => 'ASC']
        );

        $donsRecentsValides = $donRepository->findBy(
            ['statut' => 'valide'],
            ['dateSoumission' => 'DESC'],
            5
        );

        $stats = [
            'total' => $donRepository->count([]),
            'en_attente' => $donRepository->count(['statut' => 'en_attente']),
            'valides' => $donRepository->count(['statut' => 'valide']),
            'rejetes' => $donRepository->count(['statut' => 'rejete']),
            'urgents' => $donRepository->count(['niveauUrgence' => 'Élevé']),
        ];

        // Graphiques via CMENGoogleChartsBundle
        $chartMonthlyData = $donRepository->getMonthlyEvolution(12);
        $chartCategoryData = $donRepository->getCountByCategory();
        $validationRate = $donRepository->getValidationRate();
        $qualityScore = $validationRate;

        $lineChart = new LineChart();
        $monthlyRows = [['Mois', 'Validés', 'Rejetés', 'En attente']];
        foreach ($chartMonthlyData as $row) {
            $monthlyRows[] = [$row['label'], $row['valides'], $row['rejetes'], $row['en_attente']];
        }
        $lineChart->getData()->setArrayToDataTable($monthlyRows);
        $lineChart->getOptions()->setHeight(280);
        $lineChart->getOptions()->setCurveType('function');
        $lineChart->getOptions()->setColors(['#1cc88a', '#e74a3b', '#f6c23e']);

        $pieChart = new PieChart();
        $categoryRows = [['Catégorie', 'Dons']];
        foreach ($chartCategoryData as $row) {
            $categoryRows[] = [$row['nom'], (int) $row['total']];
        }
        if (count($categoryRows) === 1) {
            $categoryRows[] = ['Aucune donnée', 0];
        }
        $pieChart->getData()->setArrayToDataTable($categoryRows);
        $pieChart->getOptions()->setHeight(280);
        $pieChart->getOptions()->setPieHole(0.45);
        $pieChart->getOptions()->getLegend()->setPosition('labeled');

        $gaugeValidation = new GaugeChart();
        $gaugeValidation->getData()->setArrayToDataTable([['Label', 'Value'], ['Taux validation', min(100, max(0, $validationRate))]]);
        $gaugeValidation->getOptions()->setWidth(400);
        $gaugeValidation->getOptions()->setHeight(200);
        $gaugeValidation->getOptions()->setRedFrom(0);
        $gaugeValidation->getOptions()->setRedTo(40);
        $gaugeValidation->getOptions()->setYellowFrom(40);
        $gaugeValidation->getOptions()->setYellowTo(70);
        $gaugeValidation->getOptions()->setGreenFrom(70);
        $gaugeValidation->getOptions()->setGreenTo(100);
        $gaugeValidation->getOptions()->setMinorTicks(5);

        $gaugeQuality = new GaugeChart();
        $gaugeQuality->getData()->setArrayToDataTable([['Label', 'Value'], ['Score qualité', min(100, max(0, $qualityScore))]]);
        $gaugeQuality->getOptions()->setWidth(400);
        $gaugeQuality->getOptions()->setHeight(200);
        $gaugeQuality->getOptions()->setRedFrom(0);
        $gaugeQuality->getOptions()->setRedTo(40);
        $gaugeQuality->getOptions()->setYellowFrom(40);
        $gaugeQuality->getOptions()->setYellowTo(70);
        $gaugeQuality->getOptions()->setGreenFrom(70);
        $gaugeQuality->getOptions()->setGreenTo(100);
        $gaugeQuality->getOptions()->setMinorTicks(5);

        return $this->render('admin/dashboard/index.html.twig', [
            'dons_en_attente' => $donsEnAttente,
            'dons_recents_valides' => $donsRecentsValides,
            'stats' => $stats,
            'chart_monthly' => $lineChart,
            'chart_monthly_data' => $chartMonthlyData,
            'chart_by_category' => $pieChart,
            'chart_validation_rate' => $gaugeValidation,
            'chart_quality_score' => $gaugeQuality,
        ]);
    }
}