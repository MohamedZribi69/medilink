# Résoudre l'erreur SSL avec Composer (Avast / certificat)

L’erreur **« SSL certificate problem: unable to get local issuer certificate »** (et *curl error 60*) avec Composer vient presque toujours de l’**antivirus** (souvent **Avast**) qui intercepte les connexions HTTPS. Le message peut indiquer : *"The following exception indicates a possible issue with the Avast Firewall"*.

**Le bundle de certificats (cacert.pem) a été mis à jour dans le projet et dans XAMPP.** Si l’erreur continue, c’est qu’Avast remplace le certificat des sites par le sien : il faut donc **désactiver l’analyse HTTPS** ou **exclure PHP** dans Avast.

---

## Solution 1 : Désactiver temporairement l’analyse HTTPS (la plus simple)

### 1. Dans Avast

1. Ouvrez **Avast** → **Menu** (≡) → **Paramètres**.
2. Allez dans **Protection** → **Boucliers principaux** (Core Shields).
3. Cliquez sur **Personnaliser** à côté de **Protection Web** (Web Shield).
4. **Désactivez** l’option **« Analyser les connexions HTTPS »** / **« Enable HTTPS scanning »**.
5. Validez.

### 2. Lancer l’installation

Dans un terminal (PowerShell ou CMD) :

```batch
cd D:\xampp\htdocs\medilink
composer require symfony/mailer symfony/mime --no-interaction
```

Ou double-cliquez sur **`install-mailer.bat`** à la racine du projet.

### 3. Réactiver la protection

Une fois l’installation terminée, **réactivez** l’analyse HTTPS dans Avast.

---

## Solution 2 : Exclure PHP/Composer d’Avast (sans tout désactiver)

Au lieu de désactiver l’analyse HTTPS pour tout le trafic :

1. **Paramètres Avast** → **Protection** → **Boucliers principaux** → **Personnaliser** (à côté de **Protection Web**).
2. Cherchez une section **Exclusions** / **Exceptions** / **URLs exclues**.
3. Ajoutez les **exécutables** (selon votre version d’Avast, on peut exclure par processus ou par chemin) :
   - **Chemin PHP :** `C:\xampp\php\php.exe`
   - **Chemin Composer :** `C:\ProgramData\ComposerSetup\bin\composer.bat`  
     (ou le dossier contenant `composer.phar` si vous l’utilisez autrement)

Ainsi, seul le trafic de PHP/Composer ne sera pas inspecté par Avast, ce qui supprime l’erreur SSL avec Composer.

---

## Si l’erreur persiste

- **Redémarrez le PC** après avoir changé les paramètres Avast, puis relancez la commande `composer require ...`.
- **Testez sans Avast** : désactivez complètement Avast quelques minutes, lancez la commande Composer, puis réactivez Avast.
- **Autre réseau** : tentez avec un partage de connexion 4G/5G (souvent non inspecté par l’antivirus) puis `composer require symfony/mailer symfony/mime --no-interaction`.

---

## Vérifier que les paquets sont installés

Après une installation réussie, vous devez voir **symfony/mailer** et **symfony/mime** dans `vendor/` et dans `composer.lock`. Ensuite :

1. Renommez **`config/packages/mailer.yaml.example`** en **`mailer.yaml`**.
2. Configurez **`MAILER_DSN`** dans `.env` si besoin (ex. `MAILER_DSN=null://null` en dev).

L’envoi des reçus par e-mail depuis l’admin MediLink pourra alors fonctionner.
