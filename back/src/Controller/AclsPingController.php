<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class AclsPingController
{
    #[Route('/api/acls/ping', name: 'symsvel_acls_ping', methods: ['GET'])]
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(['module' => 'acls', 'status' => 'ok']);
    }
}
