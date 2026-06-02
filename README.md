
                    ACCESSOIRES - BOUTIQUE EN LIGNE


DESCRIPTION
-----------
Plateforme e-commerce d'accessoires (montres, bijoux, sacs, lunettes, ceintures).
Développée en PHP MVC dans le cadre du module Programmation Web Dynamique.
Université Mohammed V - Faculté des Sciences de Rabat.

TECHNOLOGIES
------------
- PHP 8.2 (avec déclaration strict_types)
- MySQL / MariaDB (accès via PDO)
- HTML5 / CSS3 / JavaScript
- XAMPP (Apache + MariaDB)

STRUCTURE DU PROJET
-------------------
accessoires/
├── index.php          # Point d'entrée unique
├── controleur.php     # Routage des actions (464 lignes)
├── modele.php         # Accès BDD et logique métier (641 lignes)
├── template.php       # Templates HTML (541 lignes)
├── config.php         # Configuration base de données
├── assets/
│   ├── css/style.css  # Feuille de styles responsive
│   └── js/app.js      # Scripts JS (panier, chatbot)
└── images/produits/   # 25 images produits

BASE DE DONNEES (boutique)
--------------------------
4 tables :

1. utilisateurs
   - id, login, mdp (hashé bcrypt), role (admin/client)

2. produits
   - id, nom, prix, categorie, genre, description, image

3. commandes
   - id, utilisateur_id, total, statut, mode_paiement, date_commande

4. details_commandes
   - id, commande_id, produit_id, quantite, prix_unitaire

FONCTIONNALITES
---------------
VISITEURS :
- Consultation catalogue
- Recherche produits
- Accès chatbot

CLIENTS :
- Inscription / connexion sécurisée
- Filtrage produits (catégorie, genre, prix, recherche textuelle)
- Tri (nom, prix croissant/décroissant)
- Panier stocké en session PHP
- Validation commande (paiement livraison ou carte bancaire)
- Validation Luhn pour numéro de carte
- Historique des commandes
- Chatbot assistant

ADMINISTRATEUR :
- Tableau de bord (statistiques : nb produits, nb commandes, CA)
- Gestion produits (CRUD + upload images)
- Gestion commandes (mise à jour statut)

CHATBOT :
- Widget flottant accessible sur toutes les pages
- Analyse de mots-clés
- Réponses : catégorie (top 3 moins chers), budget, suggestion cadeau, livraison

SECURITE
--------
Menace                    -> Protection
--------------------------------------------------------------
Injection SQL            -> PDO + requêtes préparées
XSS                      -> htmlspecialchars() sur toutes les sorties
CSRF                     -> Token généré et vérifié
Accès non autorisé       -> Vérification rôle en session
Mots de passe faibles    -> password_hash() bcrypt
Validation données       -> Côté serveur (types, longueurs, formats)

INSTALLATION
------------
1. Démarrer XAMPP (Apache + MySQL)

2. Copier le dossier accessoires dans :
   C:\xampp\htdocs\accessoires\

3. Ouvrir phpMyAdmin : http://localhost/phpmyadmin
   - Créer une base de données nommée "boutique"
   - Importer le fichier boutique.sql

4. Configurer config.php :
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'boutique');
   define('DB_USER', 'root');
   define('DB_PASS', '');

5. Accéder à l'application :
   http://localhost/accessoires/

COMPTES DEMONSTRATION
---------------------
Rôle        | Login    | Mot de passe
------------|----------|---------------
Admin       | admin    | (hashé en BDD)
Client      | client   | (hashé en BDD)

TESTS EFFECTUES
---------------
✅ Inscription nouveau client
✅ Connexion admin / client
✅ Redirection selon rôle
✅ Ajout / modification / suppression produit (admin)
✅ Filtrage multi-critères
✅ Ajout panier et modification quantités
✅ Validation commande (cash et carte)
✅ Validation Luhn carte bancaire
✅ Mise à jour statut commande (admin)
✅ Chatbot (catégorie, budget)
✅ Tentative accès admin sans droits -> redirection login
✅ Tests injections SQL et XSS -> échoués (protection OK)

AUTEURS
-------
- Radia BOUZINE
- Iymane BOLAKHRIF
- Mouna GUALY

Encadré par : Pr. Dounia LOTFI

CONCLUSION
----------
Application web complète avec architecture MVC, gestion des utilisateurs,
catalogue, panier, commandes, administration et chatbot. Sécurité renforcée
contre les attaques courantes.

================================================================================
                    FIN DU README
================================================================================
