# Acls

Module SymSvel (manifest schema 1).

## CI (10/11/2026)

`.github/workflows/qa.yml` exécute Composer et les tests du module sur le runner Docker ARM64 d'organisation (`self-hosted`, `linux`, `ARM64`, `symsvel`), avec PHP 8.4 préinstallé. La CI GitHub-hosted originale est conservée et désactivée dans `.github/workflows/qa.yml.stub`. Pour revenir aux runners GitHub, restaurer ce `.stub` sous le nom `qa.yml`. Les jobs locaux nécessitent que le Mac et Docker soient disponibles ; runner réservé aux contributions de confiance. Validation finale soumise à une CI verte.

## Migrations natives (chantier DBAL)

Les sources immuables utilisent désormais des noms `Migration/VersionYYYYMMDDHHMMSS.php`. Le compilateur du Tooling conserve leur version Doctrine et convertit les opérations `Schema` en SQL selon la plateforme DBAL active. Cette branche est à valider avec le Tooling associé : tests automatisés et crash-tests PostgreSQL/MySQL avant merge. Ne pas utiliser avec l'ancien compilateur.

## Licence MIT et contributions

Ce dépôt est distribué sous [licence MIT](LICENSE). Le code peut être utilisé, modifié et redistribué, y compris dans des projets commerciaux ou propriétaires, dans les conditions de cette licence (notamment la conservation de la notice de copyright et de licence).

**Contribuer :** créer un fork, puis proposer une pull request vers `dev` lorsque cette branche existe, sinon vers la branche par défaut. Expliquer la modification et fournir les tests et la documentation concernés. Les mainteneurs conservent la maîtrise des revues, des validations (CI et crash-tests), de l'intégration et des releases officielles. Une PR ne garantit pas son acceptation.

**Versions officielles :** seules les versions diffusées par l'organisation [Symsvel-Framework](https://github.com/Symsvel-Framework) sont officielles. Les forks sont libres sous MIT, mais ne doivent pas être présentés comme officiellement approuvés par les mainteneurs. Cette distinction n'ajoute aucune restriction aux droits accordés par la licence MIT sur le code.
