# Restauration de la base medilink

## Supprimer complètement la base et la recréer

Le fichier **`medilink_restore.sql`** :

1. **Supprime** la base `medilink` si elle existe (`DROP DATABASE IF EXISTS medilink`)
2. **Recrée** la base `medilink` vide
3. **Recrée** toutes les tables et **réinjecte** les données (utilisateurs, événements, participations, versions de migrations)

### Comment exécuter

1. Ouvrir **phpMyAdmin**.
2. Ne **pas** sélectionner la base medilink (rester sur l’accueil ou sélectionner une autre base).
3. Aller dans l’onglet **SQL**.
4. Importer le fichier **`medilink_restore.sql`** (ou copier-coller son contenu), puis exécuter.

La base `medilink` sera recréée avec le schéma et les données du 10 fév. 2026.

## Option : lier ordonnance au médecin et au patient

La table `ordonnance` n’a que `id`, `date_creation`, `instructions`. Pour que l’application (médecin crée ordonnance pour un patient) fonctionne, exécuter après la restauration :

```sql
USE medilink;

ALTER TABLE `ordonnance`
  ADD COLUMN `medecin_id` int(11) DEFAULT NULL AFTER `instructions`,
  ADD COLUMN `patient_id` int(11) DEFAULT NULL AFTER `medecin_id`,
  ADD KEY `IDX_ordonnance_medecin` (`medecin_id`),
  ADD KEY `IDX_ordonnance_patient` (`patient_id`),
  ADD CONSTRAINT `FK_ordonnance_medecin` FOREIGN KEY (`medecin_id`) REFERENCES `user` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `FK_ordonnance_patient` FOREIGN KEY (`patient_id`) REFERENCES `user` (`id`) ON DELETE CASCADE;
```
