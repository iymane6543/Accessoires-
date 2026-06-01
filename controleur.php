<?php
declare(strict_types=1);

require_once __DIR__ . '/modele.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$page = trim((string)($_GET['page'] ?? 'accueil'));
$action = trim((string)($_POST['action'] ?? $_GET['action'] ?? ''));

$pagesAutorisees = [
    'accueil',
    'produit',
    'panier',
    'commande',
    'confirmation',
    'login',
    'register',
    'logout',
    'chatbot',
    'admin',
    'admin_produits',
    'admin_commandes',
];

if (!in_array($page, $pagesAutorisees, true)) {
    $page = 'accueil';
}

$categorie = trim((string)($_GET['categorie'] ?? ''));
if ($categorie !== '' && !categorieValide($categorie)) {
    $categorie = '';
}

$prixMin = ($_GET['prix_min'] ?? '') !== '' ? max(0, (float)str_replace(',', '.', (string)$_GET['prix_min'])) : null;
$prixMax = ($_GET['prix_max'] ?? '') !== '' ? max(0, (float)str_replace(',', '.', (string)$_GET['prix_max'])) : null;
if ($prixMin !== null && $prixMax !== null && $prixMin > $prixMax) {
    [$prixMin, $prixMax] = [$prixMax, $prixMin];
}

$recherche = trim((string)($_GET['q'] ?? ''));
$tri = trim((string)($_GET['tri'] ?? 'nom'));

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$erreur = '';
$data = [];

function setFlash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function rediriger(string $page = 'accueil', array $params = []): void
{
    header('Location: ' . urlPage($page, $params));
    exit;
}

function exigerAdminControleur(): void
{
    if (!estAdmin()) {
        setFlash('error', 'Acces reserve aux administrateurs.');
        rediriger('login');
    }
}

function imageParCategorie(string $categorie): string
{
    $map = [
        'sacs' => 'assets/images/sacs.svg',
        'bijoux' => 'assets/images/bijoux.svg',
        'montres' => 'assets/images/montres.svg',
        'lunettes' => 'assets/images/lunettes.svg',
        'ceintures' => 'assets/images/ceintures.svg',
    ];

    return $map[$categorie] ?? 'assets/images/placeholder.svg';
}

function numeroCarteValide(string $numero): bool
{
    $digits = preg_replace('/\D+/', '', $numero);
    if (!is_string($digits) || strlen($digits) < 13 || strlen($digits) > 19) {
        return false;
    }

    $somme = 0;
    $double = false;
    for ($i = strlen($digits) - 1; $i >= 0; $i--) {
        $n = (int)$digits[$i];
        if ($double) {
            $n *= 2;
            if ($n > 9) {
                $n -= 9;
            }
        }
        $somme += $n;
        $double = !$double;
    }

    return $somme % 10 === 0;
}

function expirationCarteValide(string $expiration): bool
{
    if (!preg_match('/^(0[1-9]|1[0-2])\/(\d{2}|\d{4})$/', $expiration, $match)) {
        return false;
    }

    $mois = (int)$match[1];
    $annee = (int)$match[2];
    if ($annee < 100) {
        $annee += 2000;
    }

    $dernierJour = DateTimeImmutable::createFromFormat('!Y-m-d', sprintf('%04d-%02d-01', $annee, $mois));
    if (!$dernierJour) {
        return false;
    }

    $dernierJour = $dernierJour->modify('last day of this month')->setTime(23, 59, 59);
    return $dernierJour >= new DateTimeImmutable('now');
}

function traiterPaiementCarte(): array
{
    $numero = preg_replace('/\D+/', '', (string)($_POST['numero_carte'] ?? ''));
    $expiration = trim((string)($_POST['expiration_carte'] ?? ''));
    $cvv = trim((string)($_POST['cvv_carte'] ?? ''));
    $titulaire = trim((string)($_POST['titulaire_carte'] ?? ''));

    if ($titulaire === '') {
        return ['erreur' => 'Le nom du titulaire de la carte est obligatoire.', 'data' => []];
    }

    if (!numeroCarteValide($numero)) {
        return ['erreur' => 'Numero de carte invalide.', 'data' => []];
    }

    if (!expirationCarteValide($expiration)) {
        return ['erreur' => 'Date d expiration invalide.', 'data' => []];
    }

    if (!preg_match('/^\d{3,4}$/', $cvv)) {
        return ['erreur' => 'Code CVV invalide.', 'data' => []];
    }

    return [
        'erreur' => '',
        'data' => [
            'paiement_methode' => 'carte',
            'paiement_statut' => 'paye',
            'paiement_reference' => 'PAY-' . date('YmdHis') . '-' . random_int(1000, 9999),
            'paiement_carte_last4' => substr($numero, -4),
        ],
    ];
}

function traiterFormulaireProduit(?Produit $ancien = null): array
{
    $nom = trim((string)($_POST['nom'] ?? ''));
    $prix = (float)str_replace(',', '.', (string)($_POST['prix'] ?? '0'));
    $categorie = trim((string)($_POST['categorie'] ?? ''));
    $description = trim((string)($_POST['description'] ?? ''));
    $image = $ancien ? $ancien->image : imageParCategorie($categorie);

    if ($nom === '') {
        return ['erreur' => 'Le nom du produit est obligatoire.', 'data' => []];
    }
    if ($prix <= 0) {
        return ['erreur' => 'Le prix doit etre superieur a 0.', 'data' => []];
    }
    if (!categorieValide($categorie)) {
        return ['erreur' => 'Categorie invalide.', 'data' => []];
    }
    if ($description === '') {
        return ['erreur' => 'La description est obligatoire.', 'data' => []];
    }

    if (isset($_FILES['image']) && is_array($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $nomOriginal = (string)$_FILES['image']['name'];
        $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return ['erreur' => 'Image invalide. Formats acceptes : jpg, png, webp.', 'data' => []];
        }

        if (!is_dir(UPLOAD_DIR)) {
            mkdir(UPLOAD_DIR, 0775, true);
        }

        $nomFichier = 'produit_' . bin2hex(random_bytes(8)) . '.' . $extension;
        $destination = UPLOAD_DIR . '/' . $nomFichier;

        if (!move_uploaded_file((string)$_FILES['image']['tmp_name'], $destination)) {
            return ['erreur' => 'Impossible d enregistrer l image envoyee.', 'data' => []];
        }

        $image = $nomFichier;
    }

    return [
        'erreur' => '',
        'data' => [
            'nom' => $nom,
            'prix' => $prix,
            'categorie' => $categorie,
            'description' => $description,
            'image' => $image,
        ],
    ];
}

if ($page === 'chatbot' && $method === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $question = trim((string)($_POST['question'] ?? ''));
    echo json_encode(['reponse' => chatbotReponse($question)], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($page === 'logout' || ($method === 'POST' && $action === 'logout')) {
    if ($method === 'POST') {
        verifierCsrf();
    }
    $_SESSION = [];
    session_destroy();
    rediriger('accueil');
}

if ($method === 'POST') {
    try {
        switch ($action) {
            case 'login':
                verifierCsrf();
                $login = trim((string)($_POST['login'] ?? ''));
                $mdp = (string)($_POST['mdp'] ?? '');

                if ($login === '' || $mdp === '') {
                    $erreur = 'Veuillez remplir tous les champs.';
                } else {
                    $user = authentifier($login, $mdp);
                    if ($user) {
                        session_regenerate_id(true);
                        $_SESSION['utilisateur'] = [
                            'id' => (int)$user['id'],
                            'login' => $user['login'],
                            'role' => $user['role'],
                        ];
                        setFlash('success', 'Connexion reussie.');
                        rediriger($user['role'] === 'admin' ? 'admin' : 'accueil');
                    }
                    $erreur = 'Identifiants incorrects.';
                }
                break;

            case 'register':
                verifierCsrf();
                $login = trim((string)($_POST['login'] ?? ''));
                $mdp = (string)($_POST['mdp'] ?? '');
                $mdp2 = (string)($_POST['mdp2'] ?? '');

                if ($login === '' || $mdp === '' || $mdp2 === '') {
                    $erreur = 'Tous les champs sont obligatoires.';
                } elseif (strlen($mdp) < 6) {
                    $erreur = 'Le mot de passe doit contenir au moins 6 caracteres.';
                } elseif ($mdp !== $mdp2) {
                    $erreur = 'Les mots de passe ne correspondent pas.';
                } elseif (creerUtilisateur($login, $mdp)) {
                    setFlash('success', 'Compte cree. Vous pouvez vous connecter.');
                    rediriger('login');
                } else {
                    $erreur = 'Ce login existe deja.';
                }
                break;

            case 'ajouter_panier':
                verifierCsrf();
                $id = (int)($_POST['produit_id'] ?? 0);
                $quantite = max(1, (int)($_POST['quantite'] ?? 1));
                if (ajouterAuPanier($id, $quantite)) {
                    setFlash('success', 'Produit ajoute au panier.');
                    rediriger('panier');
                }
                setFlash('error', 'Produit introuvable.');
                rediriger('accueil');
                break;

            case 'modifier_panier':
                verifierCsrf();
                modifierQuantitePanier((int)($_POST['produit_id'] ?? 0), (int)($_POST['quantite'] ?? 0));
                setFlash('success', 'Panier mis a jour.');
                rediriger('panier');
                break;

            case 'supprimer_panier':
                verifierCsrf();
                supprimerDuPanier((int)($_POST['produit_id'] ?? 0));
                setFlash('success', 'Article supprime du panier.');
                rediriger('panier');
                break;

            case 'vider_panier':
                verifierCsrf();
                viderPanier();
                setFlash('success', 'Panier vide.');
                rediriger('panier');
                break;

            case 'valider_commande':
                verifierCsrf();
                
                $modePaiement = $_POST['mode_paiement'] ?? 'cash';
                
                $client = [
                    'nom' => trim((string)($_POST['nom'] ?? '')),
                    'email' => trim((string)($_POST['email'] ?? '')),
                    'adresse' => trim((string)($_POST['adresse'] ?? '')),
                    'paiement_methode' => $modePaiement,
                ];

                if ($client['nom'] === '' || $client['email'] === '' || $client['adresse'] === '') {
                    $erreur = 'Tous les champs de livraison sont obligatoires.';
                    $page = 'commande';
                } elseif (!filter_var($client['email'], FILTER_VALIDATE_EMAIL)) {
                    $erreur = 'Adresse e-mail invalide.';
                    $page = 'commande';
                } else {
                    // Si paiement par carte, valider les infos bancaires
                    if ($modePaiement === 'carte') {
                        $paiement = traiterPaiementCarte();
                        if ($paiement['erreur'] !== '') {
                            $erreur = $paiement['erreur'];
                            $page = 'commande';
                            break;
                        }
                        $client = array_merge($client, $paiement['data']);
                        $client['paiement_statut'] = 'paye';
                    } else {
                        // Paiement cash (à la livraison)
                        $client['paiement_statut'] = 'en_attente';
                        $client['paiement_reference'] = 'CASH-' . date('YmdHis') . '-' . random_int(1000, 9999);
                        $client['paiement_methode'] = 'cash';
                    }
                    
                    $commandeId = enregistrerCommande($client, getPanier());
                    viderPanier();
                    setFlash('success', 'Commande #' . $commandeId . ' confirmée. ' . 
                             ($modePaiement === 'cash' ? 'Paiement à la livraison (espèces).' : 'Paiement par carte bancaire accepté.'));
                    rediriger('confirmation', ['id' => $commandeId]);
                }
                break;

            case 'creer_produit':
                exigerAdminControleur();
                verifierCsrf();
                $form = traiterFormulaireProduit();
                if ($form['erreur'] === '') {
                    creerProduit($form['data']);
                    setFlash('success', 'Produit cree.');
                    rediriger('admin_produits');
                }
                $erreur = $form['erreur'];
                $page = 'admin_produits';
                break;

            case 'modifier_produit':
                exigerAdminControleur();
                verifierCsrf();
                $id = (int)($_POST['id'] ?? 0);
                $ancien = getProduitById($id);
                if (!$ancien) {
                    setFlash('error', 'Produit introuvable.');
                    rediriger('admin_produits');
                }
                $form = traiterFormulaireProduit($ancien);
                if ($form['erreur'] === '') {
                    modifierProduit($id, $form['data']);
                    setFlash('success', 'Produit modifie.');
                    rediriger('admin_produits');
                }
                $erreur = $form['erreur'];
                $page = 'admin_produits';
                $_GET['edit'] = (string)$id;
                break;

            case 'supprimer_produit':
                exigerAdminControleur();
                verifierCsrf();
                supprimerProduit((int)($_POST['id'] ?? 0));
                setFlash('success', 'Produit supprime.');
                rediriger('admin_produits');
                break;

            case 'statut_commande':
                exigerAdminControleur();
                verifierCsrf();
                majStatutCommande((int)($_POST['commande_id'] ?? 0), trim((string)($_POST['statut'] ?? '')));
                setFlash('success', 'Statut mis a jour.');
                rediriger('admin_commandes');
                break;
        }
    } catch (Throwable $e) {
        $erreur = 'Erreur : ' . $e->getMessage();
    }
}

switch ($page) {
    case 'accueil':
        $data['produits'] = getProduits($categorie, $prixMin, $prixMax, $recherche, $tri);
        $data['categories'] = getCategories();
        $data['prix'] = getPrixMinMax();
        break;

    case 'produit':
        $id = (int)($_GET['id'] ?? 0);
        $data['produit'] = getProduitById($id);
        if (!$data['produit']) {
            setFlash('error', 'Produit introuvable.');
            rediriger('accueil');
        }
        break;

    case 'panier':
        $data['panier'] = getPanier();
        $data['total'] = getTotalPanier();
        break;

    case 'commande':
        $data['panier'] = getPanier();
        $data['total'] = getTotalPanier();
        if ($data['panier'] === []) {
            setFlash('error', 'Votre panier est vide.');
            rediriger('panier');
        }
        break;

    case 'confirmation':
        $commandeId = (int)($_GET['id'] ?? 0);
        $data['commande_id'] = $commandeId;
        $data['details'] = $commandeId > 0 ? getDetailsCommande($commandeId) : [];
        break;

    case 'admin':
        exigerAdminControleur();
        $data['stats'] = getStatsAdmin();
        break;

    case 'admin_produits':
        exigerAdminControleur();
        $data['produits'] = getProduits('', null, null, '', 'recent');
        if (isset($_GET['edit'])) {
            $data['produit_edit'] = getProduitById((int)$_GET['edit']);
        }
        break;

    case 'admin_commandes':
        exigerAdminControleur();
        $data['commandes'] = getCommandes();
        if (isset($_GET['detail'])) {
            $data['detail_commande_id'] = (int)$_GET['detail'];
            $data['detail_commande'] = getDetailsCommande((int)$_GET['detail']);
        }
        break;
}

require_once __DIR__ . '/template.php';