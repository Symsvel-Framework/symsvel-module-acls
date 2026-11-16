export type BoAclPermission = {
  code: string;
  resource: string;
  action: string;
  label: string;
  technical: boolean;
};
export type BoAclCatalogResponse = { data: BoAclPermission[]; can_manage: boolean };
export type RoleGrantResponse = {
  data: { role_code: string; permissions: string[]; inherited: 'all' | 'functional' | null; editable: boolean };
};
export type BoRole = { id: number; code: string; label: string; native: boolean };
export type BoRolesResponse = { data: BoRole[] };

export function filteredAclPermissions(permissions: BoAclPermission[], search: string): BoAclPermission[] {
  const query = search.trim().toLocaleLowerCase();
  return permissions.filter((item) => `${item.label} ${item.code} ${item.resource}`
    .toLocaleLowerCase().includes(query));
}

const copy = {
  fr: {
    title: 'ACL', heading: 'Permissions et accès', intro: 'Les modules déclarent les droits ; ACLs les attribue aux rôles.',
    role: 'Rôle', selectRole: 'Choisir un rôle', refresh: 'Actualiser',
    search: 'Rechercher', searchHint: 'Droit, module, code…',
    label: 'Permission', code: 'Code', resource: 'Module', type: 'Type', technical: 'Technique',
    functional: 'Fonctionnel', effective: 'État', granted: 'Autorisé', denied: 'Refusé',
    allow: 'Autoriser', revoke: 'Retirer', actions: 'Actions',
    save: 'Enregistrer les droits', saving: 'Enregistrement…', cancel: 'Annuler',
    none: 'Aucune permission déclarée.', loading: 'Chargement…',
    choose: 'Sélectionnez un rôle pour afficher ses droits.',
    noManage: 'Vous pouvez consulter les permissions, mais pas modifier les droits techniques.',
    protected: 'Les permissions natives de ce rôle ne sont pas modifiables ici.',
    allInherited: 'Le développeur possède tous les droits sans affectation explicite.',
    functionalInherited: 'Le super administrateur possède les droits fonctionnels ; les permissions techniques restent distinctes.',
    updated: 'Permissions mises à jour.',
    failed: 'Impossible de charger ou enregistrer les droits.',
    forbidden: 'Droits insuffisants.', invalid: 'Permissions invalides.',
    session: 'Session expirée. Reconnectez-vous.'
  },
  en: {
    title: 'ACLs', heading: 'Permissions and access', intro: 'Modules declare permissions; ACLs assigns them to roles.',
    role: 'Role', selectRole: 'Select a role', refresh: 'Refresh',
    search: 'Search', searchHint: 'Permission, module, code…',
    label: 'Permission', code: 'Code', resource: 'Module', type: 'Type', technical: 'Technical',
    functional: 'Functional', effective: 'Status', granted: 'Allowed', denied: 'Denied',
    allow: 'Allow', revoke: 'Revoke', actions: 'Actions',
    save: 'Save permissions', saving: 'Saving…', cancel: 'Cancel',
    none: 'No declared permissions.', loading: 'Loading…',
    choose: 'Select a role to view its permissions.',
    noManage: 'You can view permissions but cannot change technical grants.',
    protected: 'Native grants for this role cannot be edited here.',
    allInherited: 'The developer has all permissions without explicit grants.',
    functionalInherited: 'Super administrators inherit functional rights; technical permissions remain distinct.',
    updated: 'Permissions updated.',
    failed: 'Could not load or save permissions.',
    forbidden: 'Insufficient permissions.', invalid: 'Invalid permission selection.',
    session: 'Session expired. Please sign in.'
  }
};
export type AclCopy = typeof copy.fr;
export function aclCopy(language: string | null): AclCopy {
  return language?.toLowerCase().startsWith('fr') ? copy.fr : copy.en;
}
