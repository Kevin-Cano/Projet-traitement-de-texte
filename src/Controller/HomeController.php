<?php

namespace App\Controller;

use App\Repository\JsonDataRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    private JsonDataRepository $repository;

    public function __construct(JsonDataRepository $repository)
    {
        $this->repository = $repository;
    }

    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        $livres = $this->repository->findAll('livre');

        // Convertir les données en objets pour la compatibilité avec les templates
        $livresData = [];
        foreach ($livres as $livre) {
            $livre['chapitres'] = $this->repository->findBy('chapitre', ['livre_id' => $livre['id']]);
            $livre['personnages'] = $this->repository->findBy('personnage', ['livre_id' => $livre['id']]);
            $livre['moodboards'] = $this->repository->findBy('moodboard', ['livre_id' => $livre['id']]);
            $livresData[] = (object) $livre;
        }

        return $this->render('home/index.html.twig', [
            'livres' => $livresData,
        ]);
    }
} 