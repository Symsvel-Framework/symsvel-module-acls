import { describe, expect, it } from 'vitest';
import { moduleId } from '../src/lib/modules/symsvel-module-acls';

describe('symsvel-module-acls module', () => {
  it('exports its identifier', () => {
    expect(moduleId).toBe('symsvel-module-acls');
  });
});
