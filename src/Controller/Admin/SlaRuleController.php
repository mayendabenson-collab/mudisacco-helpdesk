<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\SlaRule;
use App\Enum\TicketPriority;
use App\Security\SystemRole;
use App\Service\Audit\AuditLogger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

#[Route('/admin/sla')]
class SlaRuleController extends AbstractController
{
    #[Route('', name: 'admin_sla_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);

        $rules = $entityManager->createQueryBuilder()
            ->select('r', 'c')
            ->from(SlaRule::class, 'r')
            ->leftJoin('r.category', 'c')
            ->orderBy('r.active', 'DESC')
            ->addOrderBy('r.priority', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('admin/sla/index.html.twig', [
            'rules' => $rules,
        ]);
    }

    #[Route('/new', name: 'admin_sla_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, AuditLogger $auditLogger): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);
        $rule = new SlaRule();
        $errors = [];

        $categories = $entityManager->getRepository(Category::class)->findBy(['active' => true], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('sla_create', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $errors = array_merge($errors, $this->applyInput($rule, $request, $entityManager));

            if ($errors === []) {
                $entityManager->persist($rule);
                $auditLogger->record($this->requireAdmin(), 'sla_rule.created', 'sla_rule', $rule->getId(), [
                    'priority' => $rule->getPriority()->value,
                    'category' => $rule->getCategory()?->getName() ?? 'GLOBAL',
                    'responseMinutes' => $rule->getResponseMinutes(),
                    'resolutionMinutes' => $rule->getResolutionMinutes(),
                ], $request->getClientIp());
                $entityManager->flush();

                $this->addFlash('success', 'SLA Rule created successfully.');

                return $this->redirectToRoute('admin_sla_index');
            }
        }

        return $this->render('admin/sla/form.html.twig', [
            'mode' => 'new',
            'rule' => $rule,
            'categories' => $categories,
            'errors' => $errors,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_sla_edit', methods: ['GET', 'POST'])]
    public function edit(string $id, Request $request, EntityManagerInterface $entityManager, AuditLogger $auditLogger): Response
    {
        $this->denyAccessUnlessGranted(SystemRole::ADMIN->value);

        try {
            $uuid = Uuid::fromString($id);
        } catch (\InvalidArgumentException) {
            throw $this->createNotFoundException('Invalid SLA Rule ID.');
        }

        $rule = $entityManager->getRepository(SlaRule::class)->find($uuid);
        if ($rule === null) {
            throw $this->createNotFoundException('SLA Rule not found.');
        }

        $categories = $entityManager->getRepository(Category::class)->findBy([], ['name' => 'ASC']);
        $errors = [];

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('sla_edit', (string) $request->request->get('_token'))) {
                $errors[] = 'Your session expired. Please submit the form again.';
            }

            $errors = array_merge($errors, $this->applyInput($rule, $request, $entityManager));

            if ($errors === []) {
                $auditLogger->record($this->requireAdmin(), 'sla_rule.updated', 'sla_rule', $rule->getId(), [
                    'priority' => $rule->getPriority()->value,
                    'category' => $rule->getCategory()?->getName() ?? 'GLOBAL',
                    'responseMinutes' => $rule->getResponseMinutes(),
                    'resolutionMinutes' => $rule->getResolutionMinutes(),
                    'active' => $rule->isActive(),
                ], $request->getClientIp());
                $entityManager->flush();

                $this->addFlash('success', 'SLA Rule updated successfully.');

                return $this->redirectToRoute('admin_sla_index');
            }
        }

        return $this->render('admin/sla/form.html.twig', [
            'mode' => 'edit',
            'rule' => $rule,
            'categories' => $categories,
            'errors' => $errors,
        ]);
    }

    /** @return list<string> */
    private function applyInput(SlaRule $rule, Request $request, EntityManagerInterface $entityManager): array
    {
        $errors = [];

        $priorityRaw = (string) $request->request->get('priority');
        $categoryId = trim((string) $request->request->get('category_id'));
        $responseMinutes = (int) $request->request->get('response_minutes');
        $resolutionMinutes = (int) $request->request->get('resolution_minutes');
        $escalationMinutes = (int) $request->request->get('escalation_minutes');
        $active = (bool) $request->request->get('active', false);

        $priority = TicketPriority::tryFrom($priorityRaw);
        if ($priority === null) {
            $errors[] = 'Please select a valid ticket priority.';
        }

        $category = null;
        if ($categoryId !== '') {
            try {
                $catUuid = Uuid::fromString($categoryId);
                $category = $entityManager->getRepository(Category::class)->find($catUuid);
                if ($category === null) {
                    $errors[] = 'The selected category does not exist.';
                }
            } catch (\InvalidArgumentException) {
                $errors[] = 'Invalid category specified.';
            }
        }

        if ($responseMinutes <= 0) {
            $errors[] = 'Response target must be greater than 0 minutes.';
        }

        if ($resolutionMinutes <= 0) {
            $errors[] = 'Resolution target must be greater than 0 minutes.';
        }

        if ($resolutionMinutes < $responseMinutes) {
            $errors[] = 'Resolution target cannot be shorter than the first response target.';
        }

        if ($escalationMinutes <= 0) {
            // Default to 80% of resolution time if not specified or zero
            $escalationMinutes = (int) round($resolutionMinutes * 0.8);
        }

        if ($errors === []) {
            $rule->setPriority($priority);
            $rule->setCategory($category);
            $rule->setTargets($responseMinutes, $resolutionMinutes, $escalationMinutes);
            $rule->setActive($active);
        }

        return $errors;
    }

    private function requireAdmin(): \App\Entity\User
    {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
