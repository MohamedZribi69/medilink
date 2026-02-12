# Comment tester MediLink

## 1. Prérequis

- PHP 8.1+
- MySQL (base `medilink` — voir `DATABASE_URL` dans `.env`)
- Composer installé

## 2. Base de données

```powershell
cd c:\Users\client\dev

# Créer la base si besoin (MySQL)
# mysql -u root -e "CREATE DATABASE IF NOT EXISTS medilink;"

# Exécuter les migrations
php bin/console doctrine:migrations:migrate --no-interaction
```

## 3. Utilisateurs de test

Créer admin, médecin et patient de test :

```powershell
php bin/console app:create-test-admin
php bin/console app:create-test-medecin
php bin/console app:create-test-patient
```

**Comptes :**

| Rôle    | Email                  | Mot de passe |
|--------|------------------------|--------------|
| Admin  | admin@medilink.test    | admin123     |
| Médecin| medecin@medilink.test  | medecin123   |
| Patient| patient@medilink.test  | patient123   |

**Promouvoir un utilisateur existant en admin :**

```powershell
php bin/console app:create-test-admin --promote=mon@email.com
```

## 4. Démarrer le serveur

```powershell
php bin/console symfony:serve
# ou
php -S localhost:8000 -t public
```

Ouvrir : **http://localhost:8000**

## 5. Scénarios de test

### Site public (sans connexion)

- **/** — Accueil
- **/don/liste** — Dons disponibles
- **/events** — Liste des événements
- **/login** — Connexion
- **/register** — Inscription

### Espace donateur (anonyme, pas de mode=public)

- **/don/nouveau** — Déposer un don
- **/don/mes-dons** — Mes dons (liste vide si non connecté ou pas de dons)

### Connecté en Admin

- Se connecter avec **admin@medilink.test** / **admin123**
- **/admin/** — Tableau de bord
- Sidebar : Dons (en attente, validés, rejetés), Médicaments, Ordonnances, Rendez-vous, Disponibilités, Utilisateurs, Événements

### Connecté en Médecin

- **medecin@medilink.test** / **medecin123**
- **/medecin** — Espace médecin (ordonnances, rendez-vous, créneaux, médicaments)

### Connecté en Patient

- **patient@medilink.test** / **patient123**
- **/patient/rendez-vous** — Prendre rendez-vous
- **/patient/medicaments** — Voir médicaments

## 6. Vérifier les routes

```powershell
php bin/console debug:router
```

## 7. En cas d’erreur

- **Cache :** `php bin/console cache:clear`
- **Schéma DB :** `php bin/console doctrine:schema:validate`
- **Logs :** `var/log/dev.log`
