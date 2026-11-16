<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use SymSvel\Module\Acls\AclAuthorizer;
use SymSvel\Module\Acls\Access\BoAccessGate;
use SymSvel\Module\Acls\Permission\PermissionCatalog;
use SymSvel\Module\Acls\Permission\RolePermissionManager;
use SymSvel\Module\Acls\Permission\PermissionLabelManager;

/** BO-only, ACL-gated management of module-declared permissions. */
final readonly class BoAclController
{
    public function __construct(
        private BoAccessGate $gate,
        private AclAuthorizer $authorizer,
        private PermissionCatalog $catalog,
        private RolePermissionManager $grants,
        private PermissionLabelManager $labels,
    ) {}

    #[Route('/apibo/acls', name: 'symsvel_acls_bo_catalog', methods: ['GET'], format: 'json')]
    public function catalog(Request $request): JsonResponse
    {
        if (($error = $this->readCheck($request)) !== null) return $error;
        $decision = $this->gate->check($request, 'acls', 'manage');
        $canManage = $decision->allowed()
            && $this->authorizer->allows((string) $decision->roleCode, 'acls', 'manage', true);
        $overrides = $this->labels->overrides();
        return self::respond(['can_manage' => $canManage, 'data' => array_map(static fn ($permission) => [
            'code' => $permission->code(),
            'resource' => $permission->resource,
            'action' => $permission->action,
            'label' => $overrides[$permission->code()] ?? $permission->label,
            'default_label' => $permission->label,
            'technical' => $permission->technical,
        ], $this->catalog->all())]);
    }

    #[Route('/apibo/acls/labels/{resource}/{action}', name: 'symsvel_acls_bo_label_update', methods: ['PATCH'], format: 'json')]
    public function updateLabel(Request $request, string $resource, string $action): JsonResponse
    {
        $decision = $this->gate->check($request, 'acls', 'manage');
        if (!$decision->allowed()) return self::failure($decision->status);
        if (!$this->authorizer->allows((string) $decision->roleCode, 'acls', 'manage', true)) {
            return self::failure(403);
        }
        $payload = self::payload($request);
        if ($payload instanceof JsonResponse) return $payload;
        if (array_keys($payload) !== ['label'] || ($payload['label'] !== null && !is_string($payload['label']))) {
            return self::failure(400);
        }
        try {
            $this->labels->update($resource.'.'.$action, $payload['label']);
        } catch (\InvalidArgumentException) {
            return self::failure(400);
        } catch (\DomainException) {
            return self::failure(404);
        }
        return self::respond(['status' => 'updated']);
    }

    #[Route('/apibo/acls/roles/{code}', name: 'symsvel_acls_bo_role_read', requirements: ['code' => 'ROLE_[A-Z][A-Z0-9_]*'], methods: ['GET'], format: 'json')]
    public function forRole(Request $request, string $code): JsonResponse
    {
        if (($error = $this->readCheck($request)) !== null) return $error;
        try {
            $selected = $this->grants->granted($code);
        } catch (\DomainException) {
            return self::respond(['error' => ['code' => 'not_found']], 404);
        }
        return self::respond(['data' => [
            'role_code' => $code, 'permissions' => $selected,
            'inherited' => $code === 'ROLE_DEV' ? 'all' : ($code === 'ROLE_SUPER_ADMIN' ? 'functional' : null),
            'editable' => !in_array($code, ['ROLE_DEV', 'ROLE_SUPER_ADMIN', 'ROLE_USER'], true),
        ]]);
    }

    #[Route('/apibo/acls/roles/{code}', name: 'symsvel_acls_bo_role_replace', requirements: ['code' => 'ROLE_[A-Z][A-Z0-9_]*'], methods: ['PUT'], format: 'json')]
    public function update(Request $request, string $code): JsonResponse
    {
        $decision = $this->gate->check($request, 'acls', 'manage');
        if (!$decision->allowed()) return self::failure($decision->status);
        // Except for the developer, never let an actor rewrite their own
        // explicit grants, which would allow escalating into other resources.
        if ($decision->roleCode === $code && $code !== 'ROLE_DEV') return self::failure(403);
        // The technical flag is never bypassed by SUPER_ADMIN's functional privilege.
        if (!$this->authorizer->allows((string) $decision->roleCode, 'acls', 'manage', true)) {
            return self::failure(403);
        }
        $payload = self::payload($request);
        if ($payload instanceof JsonResponse) return $payload;
        if (array_keys($payload) !== ['permissions'] || !is_array($payload['permissions'])
            || !array_is_list($payload['permissions'])) return self::failure(400);
        try {
            $this->grants->replace($code, $payload['permissions']);
        } catch (\InvalidArgumentException) {
            return self::failure(400);
        } catch (\DomainException) {
            return self::failure(409);
        }
        return self::respond(['status' => 'updated']);
    }

    private function readCheck(Request $request): ?JsonResponse
    {
        $decision = $this->gate->check($request, 'acls', 'list');
        return $decision->allowed() ? null : self::failure($decision->status);
    }

    /** @return array<string, mixed>|JsonResponse */
    private static function payload(Request $request): array|JsonResponse
    {
        if (!str_starts_with(strtolower($request->headers->get('Content-Type', '')), 'application/json')) return self::failure(415);
        $raw = $request->getContent();
        if (strlen($raw) > 16384) return self::failure(400);
        try {
            $payload = json_decode($raw, true, 8, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return self::failure(400);
        }
        return is_array($payload) && !array_is_list($payload) ? $payload : self::failure(400);
    }

    private static function failure(int $status): JsonResponse
    {
        return self::respond(['error' => ['code' => match ($status) {
            400 => 'invalid_request', 401 => 'unauthorized', 403 => 'forbidden',
            404 => 'not_found', 409 => 'conflict', 415 => 'unsupported_media_type',
            default => 'unavailable',
        }]], $status);
    }

    private static function respond(array $payload, int $status = 200): JsonResponse
    {
        $response = new JsonResponse($payload, $status);
        $response->headers->set('Cache-Control', 'no-store');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }
}
