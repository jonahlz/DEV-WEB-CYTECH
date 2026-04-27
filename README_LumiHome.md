# LumiHome — Guide d'installation et de lancement

LumiHome est une application web PHP/MySQL de gestion de lumières connectées. Le projet permet de consulter un tableau de bord, gérer des lumières, modifier son profil utilisateur, et utiliser des comptes de démonstration.

## Prérequis

- XAMPP **ou** WAMP installé sur Windows
- Apache démarré
- MySQL / MariaDB démarré
- phpMyAdmin disponible
- Un navigateur web

## Installation du projet

### Avec WAMP

1. Copier le dossier `Lumihome` dans :
   ```
   C:\wamp64\www\
   ```
2. Démarrer **WAMP**.
3. Vérifier que les services **Apache** et **MySQL** sont actifs.

### Avec XAMPP

1. Copier le dossier `Lumihome` dans :
   ```
   C:\xampp\htdocs\
   ```
2. Démarrer **XAMPP Control Panel**.
3. Lancer **Apache** et **MySQL**.

## Import de la base de données

1. Ouvrir **phpMyAdmin**.
2. Créer une base nommée :
   ```sql
   lumihome
   ```
3. Importer le fichier `database.sql` fourni avec le projet.
4. Vérifier que les tables ont bien été créées :
   - `utilisateurs`
   - `pieces`
   - `lumieres`
   - `historique`
   - `connexions`

## Lancement du site

### Avec WAMP

Ouvrir dans le navigateur :

```text
http://localhost/Lumihome/index.php
```

### Avec XAMPP

Ouvrir dans le navigateur :

```text
http://localhost/Lumihome/index.php
```

## Comptes de démonstration

Les comptes de démonstration sont insérés via `database.sql`.[file:446]

| Login | Rôle | Mot de passe indiqué dans la base |
|------|------|------------------------------------|
| `admin` | admin | voir commentaire SQL |
| `sophie` | membre | voir commentaire SQL |
| `lucas` | membre | voir commentaire SQL |

**Important :** dans le fichier `database.sql`, les commentaires sur les mots de passe peuvent être incohérents avec le hash réellement inséré. Il faut donc utiliser les identifiants effectivement testés dans l'environnement local, ou créer un nouveau compte via l'inscription si besoin.[file:446]

## Fonctionnalités principales

- Accueil public avec aperçu du système
- Inscription et connexion utilisateur
- Tableau de bord personnel
- Ajout, modification et suppression de lumières
- Consultation et modification du profil
- Déconnexion

## Conseils pour la démonstration

Ordre conseillé pour présenter le projet :

1. Ouvrir l'accueil
2. Se connecter avec un compte de démonstration
3. Accéder au tableau de bord
4. Ajouter une lumière
5. Modifier sa luminosité / couleur
6. Supprimer une lumière
7. Aller sur le profil
8. Modifier les informations utilisateur
9. Se déconnecter

## En cas de problème

- Vérifier que **Apache** et **MySQL** sont bien démarrés.
- Vérifier que le dossier du projet est dans `htdocs` (XAMPP) ou `www` (WAMP).
- Vérifier que la base `lumihome` a bien été importée.
- Vérifier les paramètres de connexion dans `config.php`.
- En cas d'erreur PHP, consulter le message affiché par le navigateur ou les logs Apache/PHP.

## Structure minimale attendue

- `index.php`
- `config.php`
- `database.sql`
- dossier `pages/`
- dossier `api/`

## Remarque

Le projet a été testé en environnement local sous serveur Apache avec PHP et MySQL/MariaDB. L'utilisation d'un serveur local est obligatoire pour exécuter correctement les fichiers PHP.
