<script lang="ts">
  import { onMount } from 'svelte';
  import { goto } from '$app/navigation';
  import { resolve } from '$app/paths';
  import Button from '../../../../components/Button.svelte';
  import Alert from '../../../../components/Alert.svelte';
  import Modal from '../../../../components/Modal.svelte';
  import DataTable from '../../../../components/DataTable.svelte';
  import type { DataAction, DataColumn, SortOrder } from '../../../../components/data-table';
  import type { PageSize } from '../../../../components/pagination';
  import { ApiError } from '../../../../api/client';
  import { boApi } from '../../../../api/session';
  import { uiLanguage } from '../../../../api/locales';
  import {
    aclCopy, filteredAclPermissions,
    type BoAclPermission, type BoAclCatalogResponse, type BoRole, type BoRolesResponse,
    type RoleGrantResponse
  } from '../../presentation';

  let catalogue = $state<BoAclPermission[]>([]);
  let roles = $state<BoRole[]>([]);
  let role = $state('');
  let chosen = $state<string[]>([]);
  let saved = $state<string[]>([]);
  let inherited = $state<'all' | 'functional' | null>(null);
  let editableRole = $state(false);
  let canManage = $state(false);
  let loading = $state(false);
  let saving = $state(false);
  let selectionLoading = $state(false);
  let labelEditing = $state<BoAclPermission | null>(null);
  let labelDraft = $state('');
  let labelError = $state('');
  let labelBusy = $state(false);
  let error = $state('');
  let notice = $state('');
  let search = $state('');
  let sortKey = $state<string | null>('resource');
  let sortOrder = $state<SortOrder>('asc');
  let page = $state(1);
  let limit = $state<PageSize>(20);

  const language = $derived($uiLanguage ?? (typeof navigator === 'undefined' ? 'en' : navigator.language));
  const t = $derived(aclCopy(language));
  const canEdit = $derived(canManage && editableRole && role !== '');
  const dirty = $derived([...chosen].sort().join(',') !== [...saved].sort().join(','));
  const filtered = $derived(filteredAclPermissions(catalogue, search).sort((a, b) => {
    const key = sortKey === 'label' || sortKey === 'code' ? sortKey : 'resource';
    return a[key].localeCompare(b[key], language, { sensitivity: 'base' }) *
      (sortOrder === 'asc' ? 1 : -1);
  }));
  const visible = $derived(filtered.slice((page - 1) * limit, page * limit));
  const columns = $derived<DataColumn<BoAclPermission>[]>([
    { key: 'label', label: t.label, sortable: true },
    { key: 'code', label: t.code, sortable: true },
    { key: 'resource', label: t.resource, sortable: true },
    { key: 'technical', label: t.type, format: (v) => v ? t.technical : t.functional },
    { key: 'chosen', label: t.effective,
      format: (_, item) => chosen.includes(item.code) || inherited === 'all'
        || (inherited === 'functional' && !item.technical) ? t.granted : t.denied }
  ]);
  const actions = $derived<DataAction<BoAclPermission>[]>([
    {
      key: 'edit-label', label: t.editLabel,
      disabled: () => !canManage || labelBusy,
      onClick: (item) => openLabel(item)
    },
    {
      key: 'toggle',
      label: (item) => chosen.includes(item.code) ? t.revoke : t.allow,
      disabled: () => !canEdit || selectionLoading || saving,
      onClick: (item) => toggle(item.code)
    }
  ]);

  function explain(cause: unknown): string {
    if (cause instanceof ApiError) {
      if (cause.status === 401) {
        void goto(resolve('/login'), { replaceState: true });
        return t.session;
      }
      if (cause.status === 403) return t.forbidden;
      if (cause.status === 400) return t.invalid;
    }
    return t.failed;
  }

  async function load(): Promise<void> {
    loading = true;
    error = '';
    try {
      const [permissions, roleList] = await Promise.all([
        boApi<BoAclCatalogResponse>('/apibo/acls'),
        boApi<BoRolesResponse>('/apibo/roles')
      ]);
      catalogue = Array.isArray(permissions.data) ? permissions.data : [];
      canManage = permissions.can_manage === true;
      roles = Array.isArray(roleList.data) ? roleList.data : [];
      const previous = role;
      const fromUrl = new URLSearchParams(window.location.search).get('role');
      role = roles.some((r) => r.code === previous) ? previous
        : roles.some((r) => r.code === fromUrl) ? (fromUrl ?? '')
        : (roles.find((r) => r.code === 'ROLE_ADMIN') ?? roles[0])?.code ?? '';
      if (role) await loadRole(role);
    } catch (cause) {
      error = explain(cause);
    } finally {
      loading = false;
    }
  }

  async function loadRole(code: string): Promise<void> {
    selectionLoading = true;
    error = '';
    try {
      const response = await boApi<RoleGrantResponse>(`/apibo/acls/roles/${encodeURIComponent(code)}`);
      if (role !== code) return;
      chosen = [...response.data.permissions];
      saved = [...response.data.permissions];
      inherited = response.data.inherited;
      editableRole = response.data.editable;
    } catch (cause) {
      error = explain(cause);
    } finally {
      selectionLoading = false;
    }
  }

  function selectRole(value: string): void {
    role = value;
    chosen = [];
    saved = [];
    inherited = null;
    editableRole = false;
    notice = '';
    void loadRole(value);
  }

  function toggle(code: string): void {
    if (!canEdit) return;
    chosen = chosen.includes(code) ? chosen.filter((item) => item !== code) : [...chosen, code];
  }

  async function save(): Promise<void> {
    if (!canEdit || saving) return;
    saving = true;
    error = '';
    notice = '';
    try {
      await boApi<unknown>(`/apibo/acls/roles/${encodeURIComponent(role)}`, {
        method: 'PUT', body: JSON.stringify({ permissions: chosen })
      });
      saved = [...chosen];
      notice = t.updated;
    } catch (cause) {
      error = explain(cause);
    } finally {
      saving = false;
    }
  }

  function openLabel(item: BoAclPermission): void {
    if (!canManage) return;
    labelEditing = item;
    labelDraft = item.label;
    labelError = '';
  }

  async function sendLabel(value: string | null): Promise<void> {
    if (!labelEditing || labelBusy || !canManage) return;
    const item = labelEditing;
    labelBusy = true;
    labelError = '';
    try {
      await boApi<unknown>(
        `/apibo/acls/labels/${encodeURIComponent(item.resource)}/${encodeURIComponent(item.action)}`,
        { method: 'PATCH', body: JSON.stringify({ label: value }) }
      );
      const label = value === null ? item.default_label : value.trim();
      catalogue = catalogue.map((entry) => entry.code === item.code ? { ...entry, label } : entry);
      labelEditing = null;
      notice = t.labelUpdated;
    } catch (cause) {
      labelError = explain(cause);
    } finally {
      labelBusy = false;
    }
  }

  onMount(() => { void load(); });
</script>

<svelte:head><title>{t.title} | SymSvel BO</title></svelte:head>
<div class="acls-page">
  <header class="page-heading">
    <div><h1>{t.heading}</h1><p>{t.intro}</p></div>
    <Button type="button" variant="secondary" onclick={load} disabled={loading}>{t.refresh}</Button>
  </header>
  {#if error}<Alert kind="error">{error}</Alert>{/if}
  {#if notice}<Alert kind="success">{notice}</Alert>{/if}
  <section class="panel">
    <div class="role-selection">
      <label for="acl-role">{t.role}</label>
      <select id="acl-role" value={role} onchange={(event) => selectRole(event.currentTarget.value)}
        disabled={loading || selectionLoading}>
        <option value="" disabled>{t.selectRole}</option>
        {#each roles as item (item.id)}
          <option value={item.code}>{item.label} ({item.code})</option>
        {/each}
      </select>
    </div>

    {#if inherited === 'all'}<Alert kind="info">{t.allInherited}</Alert>{/if}
    {#if inherited === 'functional'}<Alert kind="info">{t.functionalInherited}</Alert>{/if}
    {#if !canManage}<p class="hint">{t.noManage}</p>{/if}
    {#if canManage && role && !editableRole}<p class="hint">{t.protected}</p>{/if}
    <DataTable rows={visible} {columns} {actions}
      loading={loading || selectionLoading} emptyMessage={t.none}
      filters={[{ key: 'search', label: t.search, type: 'search', placeholder: t.searchHint }]}
      filterValues={{ search }}
      onFilterChange={(_, value) => { search = value; page = 1; }}
      {sortKey} {sortOrder}
      onSortChange={(key, order) => { sortKey = key; sortOrder = order; page = 1; }}
      pagination={{
        page, limit, total: filtered.length,
        onPageChange: (value) => page = value,
        onLimitChange: (value) => { limit = value; page = 1; }
      }}
    />

    <div class="actions">
      <Button type="button" variant="secondary" onclick={() => chosen = [...saved]}
        disabled={!canEdit || !dirty || saving}>{t.cancel}</Button>
      <Button type="button" onclick={save} disabled={!canEdit || !dirty || saving || selectionLoading}>
        {saving ? t.saving : t.save}
      </Button>
    </div>
  </section>

  <Modal open={labelEditing !== null} title={t.editLabel} onClose={() => labelEditing = null}>
    {#if labelEditing}
      <p>{t.labelHint}</p>
      <p><code>{labelEditing.code}</code></p>
      {#if labelError}<Alert kind="error">{labelError}</Alert>{/if}
      <form class="label-form" onsubmit={(event) => { event.preventDefault(); void sendLabel(labelDraft); }}>
        <label for="acl-label">{t.label}</label>
        <input id="acl-label" bind:value={labelDraft} maxlength="150" required />
        <div class="label-actions">
          <Button type="button" variant="secondary" onclick={() => void sendLabel(null)}
            disabled={labelBusy || labelEditing.label === labelEditing.default_label}>{t.resetLabel}</Button>
          <Button type="submit" disabled={labelBusy}>{labelBusy ? t.saving : t.save}</Button>
        </div>
      </form>
    {/if}
  </Modal>
</div>

<style>
  .acls-page { max-width: 1200px; margin: auto; display: grid; gap: 20px; }
  .page-heading { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
  h1 { margin: 0; } p { color: #667085; }
  .panel { padding: 24px; background: #fff; border: 1px solid #e2e7ee; border-radius: 14px; min-width: 0; }
  .role-selection { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
  .role-selection label { font-weight: 700; }
  .role-selection select { padding: 10px; border: 1px solid #ccd4df; border-radius: 8px; font: inherit; min-width: 250px; max-width: 100%; }
  .hint { font-size: .85rem; }
  .actions { display: flex; justify-content: flex-end; gap: 10px; padding-top: 20px; }
  .label-form { display: grid; gap: 10px; }
  .label-form label { font-weight: 700; }
  .label-form input { padding: 11px; font: inherit; border: 1px solid #ccd4df; border-radius: 8px; }
  .label-actions { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 10px; margin-top: 16px; }
</style>
