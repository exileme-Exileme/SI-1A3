<?php

namespace App\Controller;

use App\Entity\Facture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BillingController extends AbstractController
{
    private $entityManager;
    private $client;

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $client)
    {
        $this->entityManager = $entityManager;
        $this->client = $client;
    }

    /**
     * @Route("/create-invoice", name="create_invoice", methods={"POST"})
     */
    public function createInvoice(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $facture = new Facture();
        $facture->setAmount($data['amount']);
        $facture->setDueDate(new \DateTime($data['due_date']));
        $facture->setCustomerMail($data['customer_email']);

        $this->entityManager->persist($facture);
        $this->entityManager->flush();

        $response = $this->client->request('POST', 'http://notification-service.local/send-notification', [
            'json' => [
                'sujet' => 'Billing',
                'email_recipient' => $data['customer_email'],
                'message' => 'Your invoice has been created.'
            ],
        ]);

        return new JsonResponse(['status' => 'Invoice created and notification sent!'], 200);
    }
}
