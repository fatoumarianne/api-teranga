# API de réservation — Club Sportif Teranga

Backend REST de l'application de réservation de salles et d'équipements,
développé avec **Laravel 11**, **Laravel Sanctum** et **PostgreSQL**.

---

## Installation

Prérequis : PHP 8.2 ou plus, Composer, PostgreSQL 16 (Laragon fournit PHP et
Composer ; PostgreSQL s'installe à part avec pgAdmin).

```bash
# 1. Installer les dépendances
composer install

# 2. Créer le fichier de configuration
cp .env.example .env
php artisan key:generate

# 3. Créer la base dans pgAdmin : clic droit sur Databases > Create > Database
#    Nom : teranga
#    Puis renseigner DB_USERNAME et DB_PASSWORD dans .env

# 4. Créer les tables et le jeu de données de démonstration
php artisan migrate --seed

# 5. Lancer le serveur
php artisan serve
```

L'API répond alors sur `http://localhost:8000`.

### Comptes de démonstration

| Rôle | E-mail | Mot de passe |
|---|---|---|
| Administrateur | `admin@teranga.sn` | `password` |
| Membre | `fatou@teranga.sn` | `password` |

---

## Structure de la base

Quatre tables métier, reprises du diagramme de classes du mémoire :

- **utilisateur** — membres et administrateurs, distingués par la colonne `role`
- **ressource** — salles et équipements, distingués par la colonne `type`
- **reservation** — demandes, avec leur statut et le créneau réservé
- **notification** — messages envoyés aux utilisateurs

Les noms de tables et de colonnes sont en français et au singulier, afin de
correspondre exactement au modèle décrit dans le mémoire.

---

## Les routes de l'API

### Publiques

| Méthode | Route | Rôle |
|---|---|---|
| POST | `/api/inscription` | Créer un compte membre |
| POST | `/api/connexion` | Obtenir un jeton d'authentification |

### Utilisateur authentifié

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/profil` | Profil de l'utilisateur connecté |
| PUT | `/api/profil` | Modifier son profil |
| POST | `/api/deconnexion` | Révoquer le jeton courant |
| GET | `/api/ressources` | Liste des salles et équipements |
| GET | `/api/ressources/{id}` | Détail d'une ressource |
| GET | `/api/reservations` | Ses réservations (toutes, pour l'admin) |
| GET | `/api/reservations/calendrier` | Créneaux occupés, pour FullCalendar |
| POST | `/api/reservations` | Faire une demande de réservation |
| PATCH | `/api/reservations/{id}/annuler` | Annuler une réservation |
| GET | `/api/notifications` | Ses notifications |
| GET | `/api/notifications/non-lues` | Compteur pour la pastille |

### Administrateur uniquement

| Méthode | Route | Rôle |
|---|---|---|
| POST | `/api/ressources` | Créer une salle ou un équipement |
| PUT | `/api/ressources/{id}` | Modifier une ressource |
| DELETE | `/api/ressources/{id}` | Supprimer une ressource |
| PATCH | `/api/reservations/{id}/valider` | Valider une demande |
| PATCH | `/api/reservations/{id}/refuser` | Refuser une demande (motif requis) |
| GET | `/api/membres` | Liste des comptes |
| POST | `/api/membres` | Créer un compte |
| PUT | `/api/membres/{id}` | Modifier un compte |
| PATCH | `/api/membres/{id}/statut` | Activer / désactiver un compte |
| GET | `/api/statistiques` | Données du tableau de bord |

---

## Authentification

L'API est sans état : après `/api/connexion`, le serveur renvoie un jeton
qu'il faut joindre à chaque requête suivante.

```
Authorization: Bearer <jeton>
```

Deux niveaux de contrôle se complètent : l'authentification vérifie **qui**
est l'utilisateur, et le middleware `admin` vérifie **ce qu'il a le droit de
faire**. Sans ce second contrôle, un membre authentifié pourrait appeler les
routes d'administration.

---

## La règle métier centrale

Une ressource ne peut pas être réservée deux fois sur le même créneau. Deux
créneaux se chevauchent dès lors que l'un commence avant que l'autre ne
finisse, et réciproquement :

```php
->where('date_debut', '<', $fin)
->where('date_fin',   '>', $debut)
```

Seules les réservations **en attente** ou **validées** bloquent un créneau :
une réservation refusée ou annulée le libère.

La vérification et l'enregistrement se font dans une transaction avec verrou
sur la ressource (`lockForUpdate`). Sans ce verrou, deux demandes simultanées
sur le même créneau pourraient toutes deux passer le test avant que l'une ou
l'autre ne soit enregistrée, et la ressource se retrouverait réservée deux
fois.

---

## Tester avec Postman

1. `POST http://localhost:8000/api/connexion`
   avec en corps JSON : `{"email": "admin@teranga.sn", "mot_de_passe": "password"}`
2. Copier le `token` renvoyé.
3. Dans l'onglet **Authorization** des requêtes suivantes, choisir
   **Bearer Token** et coller le jeton.
