<?php

namespace App\Controller;

use App\Entity\Orders;
use App\Form\OrdersType;
use App\Repository\OrderRepository;
use App\Service\ActivityLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/orders')]
final class OrdersController extends AbstractController
{
    private ActivityLogger $logger;

    public function __construct(ActivityLogger $logger)
    {
        $this->logger = $logger;
    }

    #[Route(name: 'app_orders_index', methods: ['GET'])]
    public function index(OrderRepository $orderRepository): Response
    {
        return $this->render('orders/index.html.twig', [
            'orders' => $orderRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_orders_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $order = new Orders();
        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $total = 0;

            // set price & link items
            foreach ($order->getItems() as $item) {
                if ($item->getProduct()) {
                    $price = $item->getProduct()->getPrice();
                    $item->setPrice($price);
                    $item->setOrder($order);
                    $total += $price * $item->getQuantity();
                }
            }

            $entityManager->persist($order);
            $entityManager->flush();

            // log creation with details
            $this->logger->log(
                'Create',
                'Order created: #'.$order->getId().' | Items: '.$order->getItems()->count().' | Total: '.$total
            );

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/new.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_orders_show', methods: ['GET'])]
    public function show(Orders $order): Response
    {
        return $this->render('orders/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_orders_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function edit(Request $request, Orders $order, EntityManagerInterface $entityManager): Response
    {
        $oldStatus = $order->getStatus();

        $form = $this->createForm(OrdersType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            // general update log
            $this->logger->log(
                'Update',
                'Order updated: #'.$order->getId()
            );

            // status change log
            if ($oldStatus !== $order->getStatus()) {
                $this->logger->log(
                    'Status Change',
                    'Order #'.$order->getId().' status changed from "'.$oldStatus.'" to "'.$order->getStatus().'"'
                );
            }

            return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('orders/edit.html.twig', [
            'order' => $order,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_orders_delete', methods: ['POST'])]
    #[IsGranted('ROLE_STAFF')]
    public function delete(Request $request, Orders $order, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$order->getId(), $request->request->get('_token'))) {

            $orderId = $order->getId();
            $itemCount = $order->getItems()->count();

            $entityManager->remove($order);
            $entityManager->flush();

            $this->logger->log(
                'Delete',
                'Order deleted: #'.$orderId.' | Items: '.$itemCount
            );
        }

        return $this->redirectToRoute('app_orders_index', [], Response::HTTP_SEE_OTHER);
    }
}
