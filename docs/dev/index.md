# Acls

## Fixtures temporaires du crash-test d'intégration

Ce module déclare les scopes `back`, `bo`, `app` et `front` pour le crash-test global `symsvel create-project --components=all --dev`.

- Symfony : `GET /api/acls/ping` doit retourner `{"module":"acls","status":"ok"}` sans données métier ni authentification propre au controller.
- SvelteKit : chaque interface fournit `src/routes/acls/+page.svelte`, affichant `Page acls`. Le synchroniseur copie la source sous `src/lib/modules/acls/routes/` et crée le relais `src/routes/acls/+page.svelte`.
- Le code métier et ses contrôles d'accès existants restent inchangés.

Ces pages et endpoints sont des sondes d'intégration, pas des interfaces métier définitives. **Limitation provisoire :** les trois scopes Svelte sont déclarés obligatoires par le manifeste ; cette fixture vise uniquement une installation avec tous les composants, pas les installations partielles.

Validation requise : tests automatisés, CI verte, puis crash-test XT (endpoints HTTP et affichage réel des pages BO/App/Front). La présence des fichiers seule ne suffit pas.

## Expérimentation migrations Schema (10/11/2026)

La source `Migration/001_acls.php` définit désormais la structure de table avec l'API `Doctrine\\DBAL\\Schema\\Schema` (colonnes, clé primaire, index unique), sans SQL PostgreSQL pour le DDL. Il s'agit d'une **expérience contrôlée**, pas encore du contrat de production.

**Limitation connue avant CT :** le compilateur actuel de `symsvel-tooling` extrait les corps `up()/down()` et les insère dans une migration Doctrine. Il ne génère pas de SQL à partir des mutations de `Schema`. En particulier, les changements de structure risquent de ne pas être exécutés sur la base. Aucun résultat PostgreSQL/MySQL n'est encore validé.

Les opérations de création/suppression de table sont uniquement déclaratives ; elles ne produisent pas encore de SQL via le compilateur actuel.

À vérifier sur un **XT réinitialisé**, sans aucune réparation locale : compilation, exécution, tables réellement présentes, index, rôles système, état du Setup et diagnostic/rollback en cas d'échec. Toute correction devra se faire dans le framework et être documentée avec tests et CI.

## Cohérence ORM et migrations Doctrine (novembre 2026)

Les métadonnées ORM déclarent explicitement `uniq_symsvel_acl_rule` sur `(resource, action, role_code)` et `allowed DEFAULT false`, conformément à la migration Schema initiale immuable. Cette cohérence évite que `make:migration` propose de supprimer les contraintes ou valeurs par défaut des modules natifs dans les projets utilisateurs. Ne pas réécrire la migration initiale déjà publiée. Validation attendue : CI, puis `make:migration` sur une installation XT avec les modules migrés, en vérifiant l'absence de SQL parasite sur leurs tables.
