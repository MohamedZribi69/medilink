# Intégration branche gmedicament (repo medilink)

**Dépôt :** [MohamedZribi69/medilink](https://github.com/MohamedZribi69/medilink) (branche `gmedicament`).

## Fait

- Remote `medilink` ajouté, branche `gmedicament` récupérée et fusionnée (`--allow-unrelated-histories`).
- Code gmedicament (médicaments, ordonnances, rendez-vous, utilisateurs, événements, sécurité) copié à la racine :
  - **src/** : Entity (User, Medicament, Ordonnance, RendezVous, etc.), Controller (Admin, Medecin, Patient, Security, Calendar, Event, Registration), Form, Repository, Security, Service, Validator, Command.
  - **templates/** : admin (disponibilite, event, medicament, ordonnance, rendezvous, user), calendar, event, medecin, patient, registration, security.
  - **config/** : security.yaml (User, authenticator, rôles), csrf.yaml.
  - **migrations/** : versions gmedicament ajoutées.
  - **public/uploads/** : fichiers événements copiés.

Ton module **Dons** (Front + Admin) reste inchangé à la racine. Les deux fonctionnalités coexistent.

## À faire de ton côté

1. **Migrations** (créer les tables User, Medicament, Ordonnance, etc.) :
   ```bash
   php bin/console doctrine:migrations:migrate
   ```
2. **Utilisateurs de test** (optionnel) :
   ```bash
   php bin/console app:create-test-medecin
   php bin/console app:create-test-patient
   php bin/console app:create-test-creneaux
   ```
3. **Vérifier** : ouvrir `/login`, `/register`, `/admin` (dons + users, médicaments, etc.), `/medecin`, `/patient`.

---

## Référence : étapes manuelles (déjà fait)

### 1. Ajouter le remote medilink

Remplace `REMOTE_URL` par l’URL réelle du repo medilink :

```powershell
cd c:\Users\client\dev
git remote add medilink REMOTE_URL
```

Exemple :

```powershell
git remote add medilink https://github.com/ton-user/medilink.git
```

### 2. Récupérer la branche gmedicament

```powershell
git fetch medilink gmedicament
```

### 3. Intégrer gmedicament dans ton code (merge)

Option A – merge direct dans `master` :

```powershell
git merge medilink/gmedicament -m "Merge branche gmedicament (medilink)"
```

En cas de conflits, Git te les listera. Ouvre les fichiers concernés, résous les conflits, puis :

```powershell
git add .
git commit -m "Résolution conflits gmedicament"
```

Option B – merger dans une branche dédiée (recommandé si beaucoup de changements) :

```powershell
git checkout -b integration-gmedicament
git merge medilink/gmedicament -m "Merge gmedicament"
# résoudre conflits si besoin, puis :
git checkout master
git merge integration-gmedicament
```

### 4. Vérifier le résultat

```powershell
git log --oneline -5
git status
```

Ensuite relance ton app (ex. `symfony server:start` ou ton serveur web) et vérifie que dons + partie gmedicament fonctionnent ensemble.

## Si le dépôt medilink est déjà un remote

Si tu as déjà cloné medilink ou ajouté le remote ailleurs, tu peux réutiliser la même URL. Pour lister les remotes :

```powershell
git remote -v
```

Pour changer l’URL du remote `medilink` :

```powershell
git remote set-url medilink NOUVELLE_URL
```
