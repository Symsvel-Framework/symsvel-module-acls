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

### Visibilité des tests ignorés

La CI affiche les raisons des tests PHPUnit ignorés avec `--display-skipped` (directement ou via le script Composer QA). Un test ignoré ne devient pas bloquant pour autant : les motifs sont examinés pour distinguer prérequis absents et couverture manquante.

## Contrôle d'accès opérationnel par rôle (16/11/2026)

`AclAuthorizer::allows(roleCode, resource, action, technical = false)` lit les autorisations explicitement déclarées dans `symsvel_acl_rules`, sur la connexion DBAL du projet. **Absence de règle = refus**. La clé existante `(resource, action, role_code)` et les migrations initiales restent inchangées ; aucune permission n'est accordée automatiquement.

Conformément aux décisions XCore/SymSvel : `ROLE_DEV` contourne tous les ACL ; `ROLE_SUPER_ADMIN` a accès aux opérations **fonctionnelles** mais pas automatiquement aux opérations marquées `technical = true` ; les autres rôles dépendent uniquement de leurs autorisations explicites. Le type d'opération est fourni par le module appelant, sans introduire de flag persistant ni redéfinir l'architecture existante. L'auteur de la requête doit d'abord être authentifié : **ce service ne réalise pas l'authentification**.

Contrôles unitaires SQLite : privilèges natifs, exceptions techniques, refus par défaut, règles autorisées et refus explicites. CI et crash-tests du projet assemblé à confirmer.

## Contrat de contrôle d'accès BO pour les modules (16/11/2026)

Le contrat \`Access\BoAccessGate\` reçoit la requête, la ressource et l'action d'un endpoint BO, puis rend une \`BoAccessDecision\` : 200 avec l'identifiant/rôle de l'acteur authentifié, 401 si la session BO est invalide, 403 si l'ACL refuse. Les comptes, tokens et vérifications de session restent la responsabilité d'Admins/Auth. Le contrat ne dépend donc d'aucun module d'identité, évitant tout cycle Composer entre Settings, Auth, Admins et Users.

L'implémentation concrète est enregistrée par le module Admins obligatoire et utilise les mécanismes existants \`AuthenticatedTokenResolver\`, \`AdminRepository\` et \`AclAuthorizer\`. Aucun bypass : un module consommateur doit systématiquement appeler \`check()\` avant lecture ou mutation et retourner une réponse sans cache. Il n'y a pas de rôle implicite supplémentaire. Tests du contrat et des contrôles réalisés côté propriétaire Admins, CI et CT à valider.



## Catalogue des permissions et gestion BO (16/11/2026)

`PermissionProviderInterface` permet à **chaque module** de déclarer ses
opérations réelles (`resource`, `action`, libellé, caractère technique).
`PermissionCatalog` agrège les providers tagués
`symsvel.acls.permission_provider`, refuse les doublons et ne scanne pas
les routes. Le code d'affichage d'un droit est `resource.action` ;
l'autorisation runtime reste basée sur `AclAuthorizer` et sur la table
immuable `symsvel_acl_rules`. Les codes et les libellés par défaut
appartiennent aux providers ; les administrateurs autorisés peuvent
personnaliser les libellés affichés grâce à une surcharge distincte,
sans modifier la définition technique du module.

Routes BO, toutes protégées par `BoAccessGate` :

- `GET /apibo/acls` : catalogue déclaré, exige `acls:list`.
- `GET /apibo/acls/roles/{ROLE_CODE}` : sélections explicites et droits
  natifs hérités, exige `acls:list`.
- `PUT /apibo/acls/roles/{ROLE_CODE}` avec `{ "permissions": ["admins.list", …] }` :
  remplace atomiquement les permissions explicites du rôle, exige
  `acls:manage` **technique**. `ROLE_DEV`, `ROLE_SUPER_ADMIN` et
  `ROLE_USER` ne peuvent pas être réécrits via cette route.

Une permission non déclarée est rejetée. Une permission absente n'est
jamais accordée. Le développeur conserve tous les droits et le super-admin
ses droits fonctionnels, mais ne contourne pas la protection technique
des mutations ACL. On ne réécrit aucune migration déjà distribuée.
Validation prévue par PHPUnit/CI, assemblage BO puis CT XT.


### Métadonnées de l'écran ACLs (16/11/2026)

La réponse du catalogue comprend `can_manage`, calculé en backend avec
`acls:manage` **technique**. L'interface le présente comme indication UX
mais les requêtes PUT sont revérifiées indépendamment. Les actions
`assign:ROLE_*` acceptent les majuscules propres aux codes de rôle ;
une définition malformée reste rejetée à la construction du catalogue.


### Préservation des droits d'un module absent et anti-escalade (16/11/2026)

Un remplacement ne modifie que les permissions déclarées dans le catalogue
actuellement disponible. Les règles d'un module désinstallé/temporairement
absent restent en base, sans perte silencieuse. Le rôle de l'acteur ne peut
pas être réécrit par lui-même via l'API ACLs (sauf développeur), même si
`acls:manage` lui a été explicitement accordé. L'API valide ces règles
avant toute modification des droits. Aucun nouveau schéma SQL.


### QA BO assemblée (16/11/2026)

La CI possède un job indépendant `bo-integration`, qui importe les
routes Svelte et les fichiers du module dans le BO hôte `dev` selon
la structure de synchronisation Tooling. Les validations TypeScript,
Svelte, Prettier, ESLint, build statique et Vitest s'exécutent sur
l'assemblage, pas uniquement sur un fichier isolé. Le crash-test XT
reste nécessaire pour les API, les ACL et la navigation réelle.


### Libellés ACL personnalisables (16/11/2026)

Le comportement XCore de modification du seul libellé est repris sans
transformer les codes techniques déclarés par les modules en données
mutables. La migration **additive Schema** `Version20261116000201` crée
`symsvel_acl_labels(resource, action, label)`, clé primaire composite.
Le catalogue renvoie `label` (éventuelle surcharge) et
`default_label` (déclaration du module).

`PATCH /apibo/acls/labels/{resource}/{action}` avec
`{ "label": "Libellé local" }` exige `acls:manage` technique. Une valeur
`null` rétablit le libellé par défaut. Une permission inconnue est refusée ;
il n'existe aucun moyen de renommer son code. Les migrations initiales
restent immuables ; le compilateur de migrations du Tooling doit intégrer
cette nouvelle migration sur les projets existants.


### Tests et interface de personnalisation des libellés (16/11/2026)

La matrice ACLs possède une action `Modifier le libellé` qui ouvre la
modale native du BO. Le code reste en lecture seule ; l'API PATCH stocke
la surcharge et la liste se met à jour sans perdre la sélection de droits
en cours. `Libellé par défaut` supprime la surcharge. Les tests SQLite
vérifient l'aller-retour de la surcharge et le refus d'une permission
inconnue ; les modifications de droits restent soumises à la protection
technique indépendante.


Correctif de syntaxe du test de surcharge : référence de classe
`\\DomainException` corrigée vers le type PHP global attendu.


### Ownership de la table des libellés (16/11/2026)

Le manifeste `module.json` déclare maintenant aussi
`symsvel_acl_labels` comme table appartenant à ACLs, pour que les
inventaires/sauvegardes et la désinstallation du module tiennent compte
de la migration additive. L'entité `AclLabelOverride` déclare les métadonnées
ORM équivalentes à la table Schema (clé composite `resource/action`, sans
identifiant inventé) afin de limiter les diffs `make:migration` parasites.
Les mutations métier continuent à passer par le manager DBAL du module.


### Validation des payloads JSON (16/11/2026)

L'API rejette les permissions non textuelles (objets/tableaux JSON), les
doublons et les codes non déclarés **avant** d'effectuer la moindre écriture.
Le gestionnaire n'applique pas `array_unique` à des entrées hétérogènes :
il retourne une erreur de validation plutôt qu'une exception PHP inattendue.
Test d'intégration de payload non scalaire ajouté.


### Correctif de test des métadonnées ORM (16/11/2026)

La lecture des attributs de `AclLabelOverride` dans PHPUnit passe désormais
par un vrai `EntityManager` SQLite en mémoire, afin d'initialiser correctement
la réflexion de `ClassMetadata` sous Doctrine ORM 3. Aucun accès à XT.

## Fallback français des libellés d'interface (17/11/2026)

Les libellés intégrés de l'interface sont sélectionnés à partir de la langue d'interface (code ISO du navigateur ou du compte). Lorsque la locale n'a pas de dictionnaire intégré, les libellés français sont utilisés ; l'anglais reste disponible explicitement pour `en`. Le fallback ne modifie ni la langue ISO du site ni celle du compte et n'impose pas l'existence de traductions avant l'installation. Le repli doit être réalisé à la clé lors de l'introduction des dictionnaires dynamiques ; l'éditeur BO des traductions et le chargement fichier/BDD ne sont pas fournis par cette seule correction.

Vérifications : tests de sélection FR/EN/autres locales et tests de build/CI. La validation finale nécessite les crash-tests locaux sur le parcours Setup, BO et App.
