<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OrderController extends AbstractController
{
    private $entityManager;
    private $client;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->client = $client;
    }

    /**
     * @Route("/create-order", name="create_order", methods={"POST"})
     */
    public function createOrder(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $commande = new Commande();
        $commande->setProductId($data['product_id']);
        $commande->setCustomerMail($data['customer_email']);
        $commande->setQuantity($data['quantity']);
        $commande->setTotalPrice($data['total_price']);

        $this->entityManager->persist($commande);
        $this->entityManager->flush();

        
        $response = $this->client->request('POST', 'http://billing-service.local/create-invoice', [
            'json' => [
                'amount' => $data['total_price'],
                'due_date' => (new \DateTime('+30 days'))->format('Y-m-d'),
                'customer_email' => $data['customer_email']
            ],
        ]);

        return new JsonResponse(['status' => 'Order created and invoice sent!'], 200);
    }
}
