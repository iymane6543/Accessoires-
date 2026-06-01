<?php
declare(strict_types=1);

require_once __DIR__ . '/config.php';

function getPDO(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }

    return $pdo;
}

function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function urlPage(string $page = 'accueil', array $params = []): string
{
    $query = array_merge(['page' => $page], $params);
    return APP_URL . '/index.php?' . http_build_query($query);
}

class Produit
{
    public int $id;
    public string $nom;
    public float $prix;
    public string $categorie;
    public string $genre;
    public string $description;
    public string $image;

    public function __construct(array $data)
    {
        $this->id = (int)$data['id'];
        $this->nom = (string)$data['nom'];
        $this->prix = (float)$data['prix'];
        $this->categorie = (string)$data['categorie'];
        $this->genre = (string)($data['genre'] ?? 'mixte');
        $this->description = (string)$data['description'];
        $this->image = (string)$data['image'];
    }

    public function prixFormate(): string
    {
        return number_format($this->prix, 2, ',', ' ') . ' EUR';
    }

    public function imageUrl(): string
    {
        $filename = basename($this->image);
        
        if ($filename !== '') {
            $fullPath = __DIR__ . '/images/produits/' . $filename;
            if (file_exists($fullPath)) {
                return APP_URL . '/images/produits/' . rawurlencode($filename);
            }
        }
        
        // Placeholder si l'image n'existe pas
        return 'https://via.placeholder.com/300x300/cccccc/333333?text=' . urlencode($this->nom);
    }
}

function categorieValide(string $categorie): bool
{
    return in_array($categorie, CATEGORIES, true);
}

function genreValide(string $genre): bool
{
    return in_array($genre, ['homme', 'femme', 'enfant', 'mixte'], true);
}

function getGenreFiltreActuel(): string
{
    $genre = trim((string)($_GET['genre'] ?? ''));
    return genreValide($genre) ? $genre : '';
}

function getProduits(
    string $categorie = '',
    ?float $prixMin = null,
    ?float $prixMax = null,
    string $recherche = '',
    string $tri = 'nom',
    ?string $genre = null
): array {
    $pdo = getPDO();
    $where = [];
    $params = [];
    $pageCourante = (string)($_GET['page'] ?? 'accueil');
    $genreFiltre = $genre ?? ($pageCourante === 'accueil' ? getGenreFiltreActuel() : '');

    if ($categorie !== '' && categorieValide($categorie)) {
        $where[] = 'categorie = :categorie';
        $params[':categorie'] = [$categorie, PDO::PARAM_STR];
    }

    if ($genreFiltre !== '' && genreValide($genreFiltre)) {
        $where[] = 'genre = :genre';
        $params[':genre'] = [$genreFiltre, PDO::PARAM_STR];
    }

    if ($prixMin !== null && $prixMin > 0) {
        $where[] = 'prix >= :prix_min';
        $params[':prix_min'] = [(string)$prixMin, PDO::PARAM_STR];
    }

    if ($prixMax !== null && $prixMax > 0) {
        $where[] = 'prix <= :prix_max';
        $params[':prix_max'] = [(string)$prixMax, PDO::PARAM_STR];
    }

    if ($recherche !== '') {
        $like = '%' . $recherche . '%';
        $where[] = '(nom LIKE :rech_nom OR categorie LIKE :rech_cat OR description LIKE :rech_desc)';
        $params[':rech_nom'] = [$like, PDO::PARAM_STR];
        $params[':rech_cat'] = [$like, PDO::PARAM_STR];
        $params[':rech_desc'] = [$like, PDO::PARAM_STR];
    }

    $orders = [
        'nom' => 'nom ASC',
        'prix_asc' => 'prix ASC',
        'prix_desc' => 'prix DESC',
        'categorie' => 'categorie ASC, nom ASC',
        'recent' => 'id DESC',
    ];
    $orderSql = $orders[$tri] ?? $orders['nom'];

    $sql = 'SELECT id, nom, prix, categorie, genre, description, image FROM produits';
    if ($where !== []) {
        $sql .= ' WHERE ' . implode(' AND ', $where);
    }
    $sql .= ' ORDER BY ' . $orderSql;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $name => [$value, $type]) {
        $stmt->bindValue($name, $value, $type);
    }
    $stmt->execute();

    $produits = [];
    foreach ($stmt->fetchAll() as $row) {
        $produits[] = new Produit($row);
    }

    return $produits;
}

function getProduitById(int $id): ?Produit
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, nom, prix, categorie, genre, description, image FROM produits WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    return $row ? new Produit($row) : null;
}

function getCategories(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT categorie, COUNT(*) AS total FROM produits GROUP BY categorie ORDER BY categorie');
    $stmt->execute();
    return $stmt->fetchAll();
}

function getPrixMinMax(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT MIN(prix) AS min_prix, MAX(prix) AS max_prix FROM produits');
    $stmt->execute();
    $row = $stmt->fetch();

    return [
        'min_prix' => (float)($row['min_prix'] ?? 0),
        'max_prix' => (float)($row['max_prix'] ?? 0),
    ];
}

function creerProduit(array $data): int
{
    $pdo = getPDO();
    $genre = isset($data['genre']) && genreValide((string)$data['genre']) ? (string)$data['genre'] : 'mixte';
    $stmt = $pdo->prepare(
        'INSERT INTO produits (nom, prix, categorie, genre, description, image)
         VALUES (:nom, :prix, :categorie, :genre, :description, :image)'
    );
    $stmt->bindValue(':nom', $data['nom'], PDO::PARAM_STR);
    $stmt->bindValue(':prix', (string)$data['prix'], PDO::PARAM_STR);
    $stmt->bindValue(':categorie', $data['categorie'], PDO::PARAM_STR);
    $stmt->bindValue(':genre', $genre, PDO::PARAM_STR);
    $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
    $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
    $stmt->execute();

    return (int)$pdo->lastInsertId();
}

function modifierProduit(int $id, array $data): bool
{
    $pdo = getPDO();
    $ancienProduit = getProduitById($id);
    $genre = isset($data['genre']) && genreValide((string)$data['genre'])
        ? (string)$data['genre']
        : ($ancienProduit ? $ancienProduit->genre : 'mixte');
    $stmt = $pdo->prepare(
        'UPDATE produits
         SET nom = :nom, prix = :prix, categorie = :categorie, genre = :genre,
             description = :description, image = :image
         WHERE id = :id'
    );
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':nom', $data['nom'], PDO::PARAM_STR);
    $stmt->bindValue(':prix', (string)$data['prix'], PDO::PARAM_STR);
    $stmt->bindValue(':categorie', $data['categorie'], PDO::PARAM_STR);
    $stmt->bindValue(':genre', $genre, PDO::PARAM_STR);
    $stmt->bindValue(':description', $data['description'], PDO::PARAM_STR);
    $stmt->bindValue(':image', $data['image'], PDO::PARAM_STR);
    $stmt->execute();

    return true;
}

function supprimerProduit(int $id): bool
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('DELETE FROM produits WHERE id = :id');
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    return true;
}

function authentifier(string $login, string $mdp): ?array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, login, mdp, role FROM utilisateurs WHERE login = :login LIMIT 1');
    $stmt->bindParam(':login', $login, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user) {
        return null;
    }

    $ok = password_verify($mdp, $user['mdp']);

    if (!$ok && hash_equals((string)$user['mdp'], hash('sha256', $mdp))) {
        $ok = true;
        $newHash = password_hash($mdp, PASSWORD_DEFAULT);
        $update = $pdo->prepare('UPDATE utilisateurs SET mdp = :mdp WHERE id = :id');
        $update->bindValue(':mdp', $newHash, PDO::PARAM_STR);
        $update->bindValue(':id', (int)$user['id'], PDO::PARAM_INT);
        $update->execute();
    }

    return $ok ? $user : null;
}

function creerUtilisateur(string $login, string $mdp): bool
{
    $pdo = getPDO();

    $check = $pdo->prepare('SELECT id FROM utilisateurs WHERE login = :login LIMIT 1');
    $check->bindParam(':login', $login, PDO::PARAM_STR);
    $check->execute();
    if ($check->fetch()) {
        return false;
    }

    $hash = password_hash($mdp, PASSWORD_DEFAULT);
    $role = 'client';
    $stmt = $pdo->prepare('INSERT INTO utilisateurs (login, mdp, role) VALUES (:login, :mdp, :role)');
    $stmt->bindParam(':login', $login, PDO::PARAM_STR);
    $stmt->bindParam(':mdp', $hash, PDO::PARAM_STR);
    $stmt->bindParam(':role', $role, PDO::PARAM_STR);
    $stmt->execute();

    return true;
}

function estConnecte(): bool
{
    return isset($_SESSION['utilisateur']);
}

function estAdmin(): bool
{
    return isset($_SESSION['utilisateur']['role']) && $_SESSION['utilisateur']['role'] === 'admin';
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifierCsrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Token CSRF invalide.');
    }
}

function getPanierBrut(): array
{
    if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    return $_SESSION['panier'];
}

function ajouterAuPanier(int $produitId, int $quantite = 1): bool
{
    $produit = getProduitById($produitId);
    if (!$produit) {
        return false;
    }

    if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    $quantite = max(1, min(99, $quantite));
    $_SESSION['panier'][$produitId] = (int)($_SESSION['panier'][$produitId] ?? 0) + $quantite;
    $_SESSION['panier'][$produitId] = min(99, $_SESSION['panier'][$produitId]);

    return true;
}

function modifierQuantitePanier(int $produitId, int $quantite): void
{
    if (!isset($_SESSION['panier']) || !is_array($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    if ($quantite <= 0) {
        unset($_SESSION['panier'][$produitId]);
        return;
    }

    $_SESSION['panier'][$produitId] = min(99, $quantite);
}

function supprimerDuPanier(int $produitId): void
{
    if (isset($_SESSION['panier'][$produitId])) {
        unset($_SESSION['panier'][$produitId]);
    }
}

function viderPanier(): void
{
    $_SESSION['panier'] = [];
}

function getPanier(): array
{
    $panier = [];

    foreach (getPanierBrut() as $id => $quantite) {
        $produit = getProduitById((int)$id);
        if ($produit) {
            $panier[(int)$id] = [
                'produit' => $produit,
                'quantite' => max(1, (int)$quantite),
            ];
        } else {
            supprimerDuPanier((int)$id);
        }
    }

    return $panier;
}

function getNbArticlesPanier(): int
{
    $total = 0;
    foreach (getPanierBrut() as $quantite) {
        $total += (int)$quantite;
    }

    return $total;
}

function getTotalPanier(): float
{
    $total = 0.0;
    foreach (getPanier() as $item) {
        $total += $item['produit']->prix * $item['quantite'];
    }

    return $total;
}

function enregistrerCommande(array $client, array $panier): int
{
    if ($panier === []) {
        throw new RuntimeException('Le panier est vide.');
    }

    $pdo = getPDO();
    $total = 0.0;
    foreach ($panier as $item) {
        $total += $item['produit']->prix * $item['quantite'];
    }

    $pdo->beginTransaction();

    try {
        $stmtCommande = $pdo->prepare(
            'INSERT INTO commandes (utilisateur_id, nom_client, email_client, adresse, total, statut, mode_paiement, paiement_statut, paiement_reference)
             VALUES (:utilisateur_id, :nom_client, :email_client, :adresse, :total, :statut, :mode_paiement, :paiement_statut, :paiement_reference)'
        );

        $utilisateurId = $_SESSION['utilisateur']['id'] ?? null;
        $statut = 'en_attente';
        $modePaiement = $client['paiement_methode'] ?? 'cash';
        $paiementStatut = $client['paiement_statut'] ?? 'en_attente';
        $paiementReference = $client['paiement_reference'] ?? null;
        
        $stmtCommande->bindValue(
            ':utilisateur_id',
            $utilisateurId === null ? null : (int)$utilisateurId,
            $utilisateurId === null ? PDO::PARAM_NULL : PDO::PARAM_INT
        );
        $stmtCommande->bindValue(':nom_client', $client['nom'], PDO::PARAM_STR);
        $stmtCommande->bindValue(':email_client', $client['email'], PDO::PARAM_STR);
        $stmtCommande->bindValue(':adresse', $client['adresse'], PDO::PARAM_STR);
        $stmtCommande->bindValue(':total', (string)$total, PDO::PARAM_STR);
        $stmtCommande->bindValue(':statut', $statut, PDO::PARAM_STR);
        $stmtCommande->bindValue(':mode_paiement', $modePaiement, PDO::PARAM_STR);
        $stmtCommande->bindValue(':paiement_statut', $paiementStatut, PDO::PARAM_STR);
        $stmtCommande->bindValue(':paiement_reference', $paiementReference, PDO::PARAM_STR);
        $stmtCommande->execute();

        $commandeId = (int)$pdo->lastInsertId();

        $stmtDetail = $pdo->prepare(
            'INSERT INTO details_commandes (commande_id, produit_id, nom_produit, prix_unitaire, quantite)
             VALUES (:commande_id, :produit_id, :nom_produit, :prix_unitaire, :quantite)'
        );

        foreach ($panier as $item) {
            $produit = $item['produit'];
            $stmtDetail->bindValue(':commande_id', $commandeId, PDO::PARAM_INT);
            $stmtDetail->bindValue(':produit_id', $produit->id, PDO::PARAM_INT);
            $stmtDetail->bindValue(':nom_produit', $produit->nom, PDO::PARAM_STR);
            $stmtDetail->bindValue(':prix_unitaire', (string)$produit->prix, PDO::PARAM_STR);
            $stmtDetail->bindValue(':quantite', (int)$item['quantite'], PDO::PARAM_INT);
            $stmtDetail->execute();
        }

        $pdo->commit();
        return $commandeId;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function getCommandes(): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT c.id, c.nom_client, c.email_client, c.adresse, c.total, c.statut, c.date_commande, u.login
         FROM commandes c
         LEFT JOIN utilisateurs u ON u.id = c.utilisateur_id
         ORDER BY c.date_commande DESC'
    );
    $stmt->execute();
    return $stmt->fetchAll();
}

function getDetailsCommande(int $commandeId): array
{
    $pdo = getPDO();
    $stmt = $pdo->prepare(
        'SELECT id, commande_id, produit_id, nom_produit, prix_unitaire, quantite
         FROM details_commandes
         WHERE commande_id = :commande_id
         ORDER BY id ASC'
    );
    $stmt->bindValue(':commande_id', $commandeId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function majStatutCommande(int $commandeId, string $statut): bool
{
    $statuts = ['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'];
    if (!in_array($statut, $statuts, true)) {
        return false;
    }

    $pdo = getPDO();
    $stmt = $pdo->prepare('UPDATE commandes SET statut = :statut WHERE id = :id');
    $stmt->bindValue(':statut', $statut, PDO::PARAM_STR);
    $stmt->bindValue(':id', $commandeId, PDO::PARAM_INT);
    $stmt->execute();

    return true;
}

function getStatsAdmin(): array
{
    $pdo = getPDO();

    $stmtProduits = $pdo->prepare('SELECT COUNT(*) AS total FROM produits');
    $stmtProduits->execute();
    $rowProduits = $stmtProduits->fetch();

    $stmtCommandes = $pdo->prepare('SELECT COUNT(*) AS total, COALESCE(SUM(total), 0) AS chiffre FROM commandes');
    $stmtCommandes->execute();
    $rowCommandes = $stmtCommandes->fetch();

    return [
        'produits' => (int)$rowProduits['total'],
        'commandes' => (int)$rowCommandes['total'],
        'chiffre' => (float)$rowCommandes['chiffre'],
    ];
}

function chatbotReponse(string $question): string
{
    $q = trim($question);
    $qMin = function_exists('mb_strtolower') ? mb_strtolower($q, 'UTF-8') : strtolower($q);

    if ($qMin === '') {
        return 'Posez-moi une question sur nos sacs, bijoux, montres, lunettes ou ceintures.';
    }

    if (preg_match('/\b(bonjour|salut|hello|bonsoir)\b/u', $qMin)) {
        return 'Bonjour ! Je peux vous aider a trouver un produit, une categorie ou une idee cadeau.';
    }

    foreach (CATEGORIES as $categorie) {
        $singulier = rtrim($categorie, 's');
        if (strpos($qMin, $categorie) !== false || strpos($qMin, $singulier) !== false) {
            $pdo = getPDO();
            $stmt = $pdo->prepare(
                'SELECT nom, prix FROM produits WHERE categorie = :categorie ORDER BY prix ASC LIMIT 3'
            );
            $stmt->bindValue(':categorie', $categorie, PDO::PARAM_STR);
            $stmt->execute();
            $produits = $stmt->fetchAll();

            if ($produits === []) {
                return 'Je ne trouve pas encore de produit dans cette categorie.';
            }

            $lignes = [];
            foreach ($produits as $produit) {
                $lignes[] = h($produit['nom']) . ' (' . number_format((float)$produit['prix'], 2, ',', ' ') . ' EUR)';
            }

            return 'Dans la categorie <strong>' . h($categorie) . '</strong>, je vous propose : ' . implode(', ', $lignes) . '.';
        }
    }

    if (preg_match('/(\d+(?:[,.]\d+)?)\s*(eur|euro|euros)?/u', $qMin, $match)
        && preg_match('/(moins|budget|prix|maximum|max|cher|abordable)/u', $qMin)
    ) {
        $budget = (float)str_replace(',', '.', $match[1]);
        $pdo = getPDO();
        $stmt = $pdo->prepare(
            'SELECT nom, prix FROM produits WHERE prix <= :budget ORDER BY prix ASC LIMIT 4'
        );
        $stmt->bindValue(':budget', (string)$budget, PDO::PARAM_STR);
        $stmt->execute();
        $produits = $stmt->fetchAll();

        if ($produits === []) {
            return 'Je ne trouve pas de produit sous ce budget pour le moment.';
        }

        $lignes = [];
        foreach ($produits as $produit) {
            $lignes[] = h($produit['nom']) . ' - ' . number_format((float)$produit['prix'], 2, ',', ' ') . ' EUR';
        }

        return 'Pour un budget de ' . number_format($budget, 2, ',', ' ') . ' EUR, regardez :<br>' . implode('<br>', $lignes);
    }

    if (preg_match('/\b(cadeau|offrir|anniversaire|idee)\b/u', $qMin)) {
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT nom, prix FROM produits ORDER BY RAND() LIMIT 3');
        $stmt->execute();
        $produits = $stmt->fetchAll();
        $lignes = [];
        foreach ($produits as $produit) {
            $lignes[] = h($produit['nom']) . ' (' . number_format((float)$produit['prix'], 2, ',', ' ') . ' EUR)';
        }

        return 'Bonne idee cadeau : ' . implode(', ', $lignes) . '.';
    }

    if (preg_match('/\b(livraison|delai|retour|remboursement|commande)\b/u', $qMin)) {
        return 'Les commandes sont enregistrees en base MySQL. Vous pouvez suivre leur statut dans l espace admin.';
    }

    $pdo = getPDO();
    $recherche = '%' . $qMin . '%';
    $stmt = $pdo->prepare(
        'SELECT nom, prix FROM produits WHERE LOWER(nom) LIKE :r1 OR LOWER(description) LIKE :r2 LIMIT 3'
    );
    $stmt->bindValue(':r1', $recherche, PDO::PARAM_STR);
    $stmt->bindValue(':r2', $recherche, PDO::PARAM_STR);
    $stmt->execute();
    $produits = $stmt->fetchAll();

    if ($produits !== []) {
        $lignes = [];
        foreach ($produits as $produit) {
            $lignes[] = h($produit['nom']) . ' (' . number_format((float)$produit['prix'], 2, ',', ' ') . ' EUR)';
        }

        return 'J ai trouve ces produits : ' . implode(', ', $lignes) . '.';
    }

    return 'Je peux repondre a des questions comme "sacs", "montres moins de 200 euros", "idee cadeau" ou "bijoux".';
}