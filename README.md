# Boutique accessoires - PHP pur

Projet e-commerce MVC sans framework.

## Installation

1. Copier le dossier `boutique-php` dans le dossier web de votre serveur local, par exemple `htdocs/boutique`.
2. Importer `boutique.sql` dans MySQL.
3. Verifier les identifiants de connexion dans `config.php`.
4. Ouvrir `http://localhost/boutique/index.php`.

## Comptes de test

- Admin : `admin` / `admin123`
- Client : `client` / `client123`

## Structure

- `index.php` : front controller.
- `controleur.php` : actions GET/POST et preparation des donnees.
- `modele.php` : PDO, requetes preparees, classe `Produit`, panier, commandes, chatbot.
- `template.php` : affichage HTML.
- `assets/css/style.css` : style de la boutique.
- `assets/js/app.js` : interactions panier/admin/chatbot.
- `boutique.sql` : base `boutique` complete.
