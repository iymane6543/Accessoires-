CREATE DATABASE IF NOT EXISTS boutique
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE boutique;

DROP TABLE IF EXISTS details_commandes;
DROP TABLE IF EXISTS commandes;
DROP TABLE IF EXISTS produits;
DROP TABLE IF EXISTS utilisateurs;

CREATE TABLE produits (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(150) NOT NULL,
  prix DECIMAL(10,2) NOT NULL,
  categorie ENUM('sacs','bijoux','montres','lunettes','ceintures') NOT NULL,
  description TEXT NOT NULL,
  image VARCHAR(255) NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_categorie (categorie),
  INDEX idx_prix (prix)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE utilisateurs (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  login VARCHAR(80) NOT NULL,
  mdp VARCHAR(255) NOT NULL,
  role ENUM('client','admin') NOT NULL DEFAULT 'client',
  PRIMARY KEY (id),
  UNIQUE KEY uniq_login (login)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commandes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  utilisateur_id INT UNSIGNED NULL,
  nom_client VARCHAR(150) NOT NULL,
  email_client VARCHAR(180) NOT NULL,
  adresse TEXT NOT NULL,
  total DECIMAL(10,2) NOT NULL,
  statut ENUM('en_attente','confirmee','expediee','livree','annulee') NOT NULL DEFAULT 'en_attente',
  date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_utilisateur (utilisateur_id),
  CONSTRAINT fk_commandes_utilisateurs
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE details_commandes (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  commande_id INT UNSIGNED NOT NULL,
  produit_id INT UNSIGNED NULL,
  nom_produit VARCHAR(150) NOT NULL,
  prix_unitaire DECIMAL(10,2) NOT NULL,
  quantite INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  INDEX idx_commande (commande_id),
  INDEX idx_produit (produit_id),
  CONSTRAINT fk_details_commandes
    FOREIGN KEY (commande_id) REFERENCES commandes(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_details_produits
    FOREIGN KEY (produit_id) REFERENCES produits(id)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO utilisateurs (login, mdp, role) VALUES
('admin', SHA2('admin123', 256), 'admin'),
('client', SHA2('client123', 256), 'client');

INSERT INTO produits (nom, prix, categorie, description, image) VALUES
('Sac Cuir Milano', 189.00, 'sacs', 'Sac structure en cuir souple avec bandouliere reglable, doublure coton et fermeture aimantee.', 'assets/images/sacs.svg'),
('Tote Bag Canvas', 59.00, 'sacs', 'Grand cabas en toile epaisse avec anses solides et poche interieure zippee.', 'assets/images/sacs.svg'),
('Pochette Soiree Satin', 79.00, 'sacs', 'Pochette elegante en satin noir avec chaine amovible et fermoir dore.', 'assets/images/sacs.svg'),

('Collier Perles Nacrees', 65.00, 'bijoux', 'Collier de perles nacrees avec fermoir discret, parfait pour une tenue habillee.', 'assets/images/bijoux.svg'),
('Bracelet Jonc Or', 45.00, 'bijoux', 'Bracelet jonc en acier dore, finition polie et ligne minimaliste.', 'assets/images/bijoux.svg'),
('Boucles Creoles XL', 35.00, 'bijoux', 'Creoles legeres en acier inoxydable dore, faciles a porter au quotidien.', 'assets/images/bijoux.svg'),

('Montre Minimaliste', 245.00, 'montres', 'Montre quartz avec boitier acier, bracelet cuir brun et cadran epure.', 'assets/images/montres.svg'),
('Montre Sport Noire', 189.00, 'montres', 'Chronographe urbain avec bracelet silicone et boitier noir resistant.', 'assets/images/montres.svg'),
('Montre Doree Vintage', 320.00, 'montres', 'Montre automatique doree avec bracelet milanais et details retro.', 'assets/images/montres.svg'),

('Lunettes Cat Eye', 89.00, 'lunettes', 'Monture ecaille style cat eye avec verres UV400 et silhouette expressive.', 'assets/images/lunettes.svg'),
('Lunettes Aviateur', 75.00, 'lunettes', 'Monture metal dore avec verres teintes et nez ajustable.', 'assets/images/lunettes.svg'),
('Lunettes Rondes Acier', 65.00, 'lunettes', 'Monture ronde fine en acier et verres legerement fumes.', 'assets/images/lunettes.svg'),

('Ceinture Cuir Noir', 55.00, 'ceintures', 'Ceinture en cuir pleine fleur avec boucle en metal brosse.', 'assets/images/ceintures.svg'),
('Ceinture Tressee Camel', 65.00, 'ceintures', 'Ceinture tressee en cuir camel, souple et ajustable.', 'assets/images/ceintures.svg'),
('Ceinture Serpent', 88.00, 'ceintures', 'Ceinture effet serpent avec boucle doree, piece forte pour une silhouette sobre.', 'assets/images/ceintures.svg');
