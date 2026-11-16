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
Les libellés proviennent du module propriétaire et ne sont pas éditables
par l'administrateur : c'est une différence voulue avec le prototype XCore,
car un label déclaré par un module ne doit pas être modifié globalement
sans contrat de traduction. CI BO et CT XT requis.


### Modification du libellé d'une permission

Comme dans XCore, le libellé affiché peut être personnalisé par un
administrateur ayant le droit technique `acls.manage` ; le code et le
caractère technique du droit restent déclarés par le module propriétaire.
Le libellé par défaut est rétabli en supprimant la surcharge. Les
modifications s'appliquent aux tableaux ACLs et à la sélection des droits
dans Roles. Migration Schema additive et CT multi-SGBD requis.
