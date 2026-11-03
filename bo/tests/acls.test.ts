import { describe, expect, it } from 'vitest';
import { moduleId } from '../src/lib/modules/acls';

describe('acls module', () => {
  it('exports its identifier', () => {
    expect(moduleId).toBe('acls');
  });
});
