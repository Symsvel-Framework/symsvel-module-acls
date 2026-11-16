import { describe, expect, it } from 'vitest';
import { aclCopy, filteredAclPermissions, type BoAclPermission } from './presentation';
import { boNavigation } from './navigation';

describe('BO ACLs', () => {
  it('advertises the ACL-protected catalog for global navigation', () => {
    expect(boNavigation.path).toBe('/acls');
    expect(boNavigation.probe).toBe('/apibo/acls');
  });
  it('has bilingual labels and filters only declared permissions', () => {
    expect(aclCopy('fr').title).toBe('ACL');
    expect(aclCopy('en').title).toBe('ACLs');
    const rows: BoAclPermission[] = [
      { code: 'admins.list', resource: 'admins', action: 'list', label: 'Liste administrateurs', default_label: 'Liste administrateurs', technical: false },
      { code: 'roles.assign:ROLE_ADMIN', resource: 'roles', action: 'assign:ROLE_ADMIN', label: 'Attribution', default_label: 'Attribution', technical: true }
    ];
    expect(filteredAclPermissions(rows, 'administrateurs')).toHaveLength(1);
    expect(filteredAclPermissions(rows, 'ROLE_ADMIN')).toHaveLength(1);
    expect(filteredAclPermissions(rows, '')).toHaveLength(2);
  });
});
