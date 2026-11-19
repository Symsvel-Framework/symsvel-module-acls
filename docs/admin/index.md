# Acls

Documentation admin.


## Console ACL du BO (16/11/2026)

L'interface parcourt les permissions déclarées par les modules installés
et les rôles présents en base. Elle affiche un tableau de type XCore avec
recherche, tri et pagination, y compris pour les permissions techniques.
Le choix d'un rôle charge ses affectations explicites ; les boutons
`Autoriser` / `Retirer` préparent les modifications, puis
`Enregistrer les droits` remplace en une transaction leur sélection.

Les droits natifs du développeur et du super administrateur sont présentés
comme hérités, jamais comme des lignes modifiables. Les mutations exigent
l'autorisation technique `acls.manage`, et restent contrôlées par l'API.
Les libellés par défaut proviennent du module propriétaire. Comme dans
XCore, ils peuvent être personnalisés par un acteur autorisé sans changer
le code ni le caractère technique de la permission. CI BO et CT XT requis.


### Modification du libellé d'une permission

Comme dans XCore, le libellé affiché peut être personnalisé par un
administrateur ayant le droit technique `acls.manage` ; le code et le
caractère technique du droit restent déclarés par le module propriétaire.
Le libellé par défaut est rétabli en supprimant la surcharge. Les
modifications s'appliquent aux tableaux ACLs et à la sélection des droits
dans Roles. Migration Schema additive et CT multi-SGBD requis.

### Normalisation Design System BO (19/11/2026)

Le tableau ACL conserve `DataTable`, `Pagination` et l'attribution des droits par rôle, sans modification des permissions. Le sélecteur des rôles utilise désormais le `Dropdown` commun du BO HP (libellé, code, clavier et thème) et reste lié à la fonction existante `selectRole` sans reload de page. Surfaces, bordures, textes secondaires, formulaire de libellé et focus utilisent les jetons CSS `--bo-*`. Les ACL du serveur restent l'unique autorité sur les mutations ; CI et CT XT en clair/sombre requis.
