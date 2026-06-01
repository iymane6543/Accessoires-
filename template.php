<?php
declare(strict_types=1);

function isSelected(string $actual, string $expected): string
{
    return $actual === $expected ? 'selected' : '';
}

function isChecked(string $actual, string $expected): string
{
    return $actual === $expected ? 'checked' : '';
}

function csrfInput(): string
{
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function renderFormProduit(?Produit $produit = null): void
{
    $isEdit = $produit !== null;
    $action = $isEdit ? 'modifier_produit' : 'creer_produit';
    ?>
    <form class="form-panel" method="POST" action="<?= h(urlPage('admin_produits')) ?>" enctype="multipart/form-data">
        <?= csrfInput() ?>
        <input type="hidden" name="action" value="<?= h($action) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$produit->id ?>">
        <?php endif; ?>

        <div class="form-grid">
            <label>
                Nom
                <input type="text" name="nom" required value="<?= h($isEdit ? $produit->nom : '') ?>">
            </label>
            <label>
                Prix
                <input type="number" name="prix" step="0.01" min="0" required value="<?= h($isEdit ? $produit->prix : '') ?>">
            </label>
            <label>
                Categorie
                <select name="categorie" required>
                    <?php foreach (CATEGORIES as $cat): ?>
                        <option value="<?= h($cat) ?>" <?= $isEdit ? isSelected($produit->categorie, $cat) : '' ?>>
                            <?= h(ucfirst($cat)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>
                Image
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                <?php if ($isEdit): ?>
                    <small>Actuelle : <?= h($produit->image) ?></small>
                <?php endif; ?>
            </label>
            <label class="full">
                Description
                <textarea name="description" rows="4" required><?= h($isEdit ? $produit->description : '') ?></textarea>
            </label>
        </div>
        <div class="form-actions">
            <button class="btn" type="submit"><?= $isEdit ? 'Enregistrer' : 'Creer le produit' ?></button>
            <?php if ($isEdit): ?>
                <a class="btn btn-light" href="<?= h(urlPage('admin_produits')) ?>">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
    <?php
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(APP_NAME) ?> - <?= h(ucfirst($page)) ?></title>
    <link rel="stylesheet" href="<?= h(APP_URL) ?>/assets/css/style.css">
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= h(urlPage('accueil')) ?>">
        <span>Boutique</span>
        <strong>Accessoires</strong>
    </a>

    <nav class="main-nav" aria-label="Categories">
        <?php foreach (CATEGORIES as $cat): ?>
            <a href="<?= h(urlPage('accueil', ['categorie' => $cat])) ?>"><?= h(ucfirst($cat)) ?></a>
        <?php endforeach; ?>
    </nav>

    <div class="header-actions">
        <button class="icon-button" type="button" id="openChat">IA</button>
        <a class="cart-link" href="<?= h(urlPage('panier')) ?>">Panier <span><?= getNbArticlesPanier() ?></span></a>
        <?php if (estConnecte()): ?>
            <?php if (estAdmin()): ?>
                <a class="btn btn-small" href="<?= h(urlPage('admin')) ?>">Admin</a>
            <?php endif; ?>
            <form method="POST" action="<?= h(urlPage('logout')) ?>" class="inline-form">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="logout">
                <button class="btn btn-light btn-small" type="submit">Sortir</button>
            </form>
        <?php else: ?>
            <a class="btn btn-small" href="<?= h(urlPage('login')) ?>">Connexion</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash flash-<?= h($flash['type']) ?>"><?= h($flash['message']) ?></div>
<?php endif; ?>
<?php if ($erreur !== ''): ?>
    <div class="flash flash-error"><?= h($erreur) ?></div>
<?php endif; ?>

<main class="main">
<?php switch ($page):
case 'accueil': ?>
    <section class="hero">
        <div>
            <p class="eyebrow">Sacs, bijoux, montres, lunettes, ceintures</p>
            <h1>Des accessoires choisis pour le quotidien.</h1>
        </div>
        <form class="ai-search" id="aiSearchForm">
            <input id="aiQuestion" type="search" placeholder="Ex : montres moins de 200 euros">
            <button class="btn" type="submit">Demander</button>
            <output id="aiAnswer"></output>
        </form>
    </section>

    <section class="shop-layout">
        <aside class="filters">
            <form method="GET" action="<?= h(APP_URL) ?>/index.php">
                <input type="hidden" name="page" value="accueil">

                <label>
                    Recherche
                    <input type="search" name="q" value="<?= h($recherche) ?>" placeholder="Nom, categorie...">
                </label>

                <label>
                    Categorie
                    <select name="categorie">
                        <option value="">Toutes</option>
                        <?php foreach (CATEGORIES as $cat): ?>
                            <option value="<?= h($cat) ?>" <?= isSelected($categorie, $cat) ?>><?= h(ucfirst($cat)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <div class="price-grid">
                    <label>
                        Prix min
                        <input type="number" name="prix_min" min="0" step="1" value="<?= h($prixMin ?? '') ?>">
                    </label>
                    <label>
                        Prix max
                        <input type="number" name="prix_max" min="0" step="1" value="<?= h($prixMax ?? '') ?>">
                    </label>
                </div>

                <label>
                    Tri
                    <select name="tri">
                        <option value="nom" <?= isSelected($tri, 'nom') ?>>Nom</option>
                        <option value="recent" <?= isSelected($tri, 'recent') ?>>Recents</option>
                        <option value="prix_asc" <?= isSelected($tri, 'prix_asc') ?>>Prix croissant</option>
                        <option value="prix_desc" <?= isSelected($tri, 'prix_desc') ?>>Prix decroissant</option>
                        <option value="categorie" <?= isSelected($tri, 'categorie') ?>>Categorie</option>
                    </select>
                </label>

                <div class="form-actions vertical">
                    <button class="btn" type="submit">Filtrer</button>
                    <a class="btn btn-light" href="<?= h(urlPage('accueil')) ?>">Reinitialiser</a>
                </div>
            </form>
        </aside>

        <section class="catalog">
            <div class="catalog-head">
                <h2><?= count($data['produits']) ?> produit(s)</h2>
                <?php if ($recherche !== ''): ?>
                    <p>Resultat pour "<?= h($recherche) ?>"</p>
                <?php endif; ?>
            </div>

            <?php if ($data['produits'] === []): ?>
                <div class="empty">Aucun produit ne correspond aux filtres.</div>
            <?php else: ?>
                <div class="product-grid">
                    <?php foreach ($data['produits'] as $produit): ?>
                        <article class="product-card">
                            <a href="<?= h(urlPage('produit', ['id' => $produit->id])) ?>">
                                <img src="<?= h($produit->imageUrl()) ?>" alt="<?= h($produit->nom) ?>">
                                <span><?= h(ucfirst($produit->categorie)) ?></span>
                                <h3><?= h($produit->nom) ?></h3>
                                <strong><?= h($produit->prixFormate()) ?></strong>
                            </a>
                            <form method="POST" action="<?= h(urlPage('panier')) ?>">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="ajouter_panier">
                                <input type="hidden" name="produit_id" value="<?= (int)$produit->id ?>">
                                <input type="hidden" name="quantite" value="1">
                                <button class="btn btn-full" type="submit">Ajouter</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
    </section>
    <?php break; ?>

<?php case 'produit':
    /** @var Produit $produit */
    $produit = $data['produit'];
    ?>
    <section class="product-detail">
        <img src="<?= h($produit->imageUrl()) ?>" alt="<?= h($produit->nom) ?>">
        <div>
            <a class="back-link" href="<?= h(urlPage('accueil', ['categorie' => $produit->categorie])) ?>"><?= h(ucfirst($produit->categorie)) ?></a>
            <h1><?= h($produit->nom) ?></h1>
            <p class="price"><?= h($produit->prixFormate()) ?></p>
            <p><?= nl2br(h($produit->description)) ?></p>
            <form method="POST" action="<?= h(urlPage('panier')) ?>" class="buy-box">
                <?= csrfInput() ?>
                <input type="hidden" name="action" value="ajouter_panier">
                <input type="hidden" name="produit_id" value="<?= (int)$produit->id ?>">
                <label>
                    Quantite
                    <input type="number" name="quantite" min="1" max="99" value="1">
                </label>
                <button class="btn" type="submit">Ajouter au panier</button>
            </form>
        </div>
    </section>
    <?php break; ?>

<?php case 'panier': ?>
    <section>
        <h1>Panier</h1>
        <?php if ($data['panier'] === []): ?>
            <div class="empty">Votre panier est vide. <a href="<?= h(urlPage('accueil')) ?>">Voir les produits</a></div>
        <?php else: ?>
            <div class="cart-layout">
                <div class="cart-list">
                    <?php foreach ($data['panier'] as $item):
                        /** @var Produit $produit */
                        $produit = $item['produit'];
                        ?>
                        <article class="cart-item">
                            <img src="<?= h($produit->imageUrl()) ?>" alt="<?= h($produit->nom) ?>">
                            <div>
                                <h3><?= h($produit->nom) ?></h3>
                                <p><?= h($produit->prixFormate()) ?></p>
                            </div>
                            <form method="POST" action="<?= h(urlPage('panier')) ?>" class="quantity-form">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="modifier_panier">
                                <input type="hidden" name="produit_id" value="<?= (int)$produit->id ?>">
                                <input type="number" name="quantite" min="0" max="99" value="<?= (int)$item['quantite'] ?>">
                                <button class="btn btn-small" type="submit">OK</button>
                            </form>
                            <strong><?= h(number_format($produit->prix * $item['quantite'], 2, ',', ' ')) ?> EUR</strong>
                            <form method="POST" action="<?= h(urlPage('panier')) ?>">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="supprimer_panier">
                                <input type="hidden" name="produit_id" value="<?= (int)$produit->id ?>">
                                <button class="link-danger" type="submit">Supprimer</button>
                            </form>
                        </article>
                    <?php endforeach; ?>
                </div>
                <aside class="summary">
                    <h2>Total</h2>
                    <p class="price"><?= h(number_format($data['total'], 2, ',', ' ')) ?> DH</p>
                    <a class="btn btn-full" href="<?= h(urlPage('commande')) ?>">Valider la commande</a>
                    <form method="POST" action="<?= h(urlPage('panier')) ?>">
                        <?= csrfInput() ?>
                        <input type="hidden" name="action" value="vider_panier">
                        <button class="btn btn-light btn-full" type="submit">Vider le panier</button>
                    </form>
                </aside>
            </div>
        <?php endif; ?>
    </section>
    <?php break; ?>

<?php case 'commande': ?>
    <section class="checkout-layout">
        <form method="POST" action="<?= h(urlPage('commande')) ?>" class="form-panel" id="commandeForm">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="valider_commande">
            <h1>Validation de commande</h1>
            
            <label>Nom complet <input type="text" name="nom" required></label>
            <label>Email <input type="email" name="email" required></label>
            <label>Adresse de livraison <textarea name="adresse" rows="5" required></textarea></label>
            
            <!-- Choix du mode de paiement -->
            <div class="payment-methods">
                <label class="payment-option">
                    <input type="radio" name="mode_paiement" value="cash" checked required>
                     Paiement à la livraison (Espèces / Cash)
                </label>
                <label class="payment-option">
                    <input type="radio" name="mode_paiement" value="carte">
                     Carte bancaire (CB, Visa, Mastercard)
                </label>
            </div>
            
            <!-- Formulaire carte bancaire (caché par défaut) -->
            <div id="carte-info" style="display:none; margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 10px;">
                <h3>Informations bancaires</h3>
                <label>Titulaire de la carte <input type="text" name="titulaire_carte" placeholder="NOM PRENOM"></label>
                <label>Numéro de carte <input type="text" name="numero_carte" placeholder="1234 5678 9012 3456"></label>
                <label>Date d'expiration <input type="text" name="expiration_carte" placeholder="MM/AA"></label>
                <label>CVV <input type="text" name="cvv_carte" placeholder="123"></label>
                <small style="color: #666;"> Paiement sécurisé (test : numéro 4111111111111111)</small>
            </div>
            
            <button class="btn" type="submit">Confirmer la commande</button>
        </form>
        
        <aside class="summary">
            <h2>Récapitulatif</h2>
            <?php foreach ($data['panier'] as $item): ?>
                <p><?= h($item['produit']->nom) ?> x <?= (int)$item['quantite'] ?></p>
            <?php endforeach; ?>
            <p class="price">Total : <?= h(number_format($data['total'], 2, ',', ' ')) ?> EUR</p>
            <div class="cash-info">💰 Paiement sécurisé</div>
        </aside>
    </section>
    
    <script>
        const radioCash = document.querySelector('input[value="cash"]');
        const radioCarte = document.querySelector('input[value="carte"]');
        const carteInfo = document.getElementById('carte-info');
        
        function toggleCarteInfo() {
            carteInfo.style.display = radioCarte.checked ? 'block' : 'none';
        }
        
        if (radioCash && radioCarte) {
            radioCash.addEventListener('change', toggleCarteInfo);
            radioCarte.addEventListener('change', toggleCarteInfo);
            toggleCarteInfo();
        }
    </script>
    <?php break; ?>

<?php case 'confirmation': ?>
    <section class="center-card">
        <h1>Commande confirmee</h1>
        <p>Numero de commande : <strong>#<?= (int)$data['commande_id'] ?></strong></p>
        <?php foreach ($data['details'] as $detail): ?>
            <p><?= h($detail['nom_produit']) ?> x <?= (int)$detail['quantite'] ?></p>
        <?php endforeach; ?>
        <a class="btn" href="<?= h(urlPage('accueil')) ?>">Retour boutique</a>
    </section>
    <?php break; ?>

<?php case 'login': ?>
    <section class="center-card">
        <h1>Connexion</h1>
        <form method="POST" action="<?= h(urlPage('login')) ?>" class="stack-form">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="login">
            <label>Login <input type="text" name="login" required autofocus></label>
            <label>Mot de passe <input type="password" name="mdp" required></label>
            <button class="btn btn-full" type="submit">Se connecter</button>
        </form>
        <p class="muted">Admin : admin / admin123</p>
        <p><a href="<?= h(urlPage('register')) ?>">Creer un compte client</a></p>
    </section>
    <?php break; ?>

<?php case 'register': ?>
    <section class="center-card">
        <h1>Inscription</h1>
        <form method="POST" action="<?= h(urlPage('register')) ?>" class="stack-form">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="register">
            <label>Login <input type="text" name="login" required autofocus></label>
            <label>Mot de passe <input type="password" name="mdp" minlength="6" required></label>
            <label>Confirmation <input type="password" name="mdp2" minlength="6" required></label>
            <button class="btn btn-full" type="submit">Creer le compte</button>
        </form>
    </section>
    <?php break; ?>

<?php case 'admin': ?>
    <section>
        <h1>Administration</h1>
        <div class="admin-stats">
            <a href="<?= h(urlPage('admin_produits')) ?>"><strong><?= (int)$data['stats']['produits'] ?></strong><span>Produits</span></a>
            <a href="<?= h(urlPage('admin_commandes')) ?>"><strong><?= (int)$data['stats']['commandes'] ?></strong><span>Commandes</span></a>
            <div><strong><?= h(number_format($data['stats']['chiffre'], 2, ',', ' ')) ?> EUR</strong><span>Chiffre d affaires</span></div>
        </div>
    </section>
    <?php break; ?>

<?php case 'admin_produits': ?>
    <section>
        <div class="page-head">
            <h1>Produits</h1>
            <a class="btn btn-light" href="<?= h(urlPage('admin')) ?>">Dashboard</a>
        </div>

        <details class="admin-form" <?= isset($data['produit_edit']) ? 'open' : '' ?>>
            <summary><?= isset($data['produit_edit']) && $data['produit_edit'] ? 'Modifier le produit' : 'Nouveau produit' ?></summary>
            <?php renderFormProduit($data['produit_edit'] ?? null); ?>
        </details>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Nom</th>
                        <th>Categorie</th>
                        <th>Prix</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['produits'] as $produit): ?>
                    <tr>
                        <td><?= (int)$produit->id ?></td>
                        <td><img class="thumb" src="<?= h($produit->imageUrl()) ?>" alt=""></td>
                        <td><?= h($produit->nom) ?></td>
                        <td><?= h(ucfirst($produit->categorie)) ?></td>
                        <td><?= h($produit->prixFormate()) ?></td>
                        <td class="row-actions">
                            <a class="btn btn-small" href="<?= h(urlPage('admin_produits', ['edit' => $produit->id])) ?>">Modifier</a>
                            <form method="POST" action="<?= h(urlPage('admin_produits')) ?>" data-confirm="Supprimer ce produit ?">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="supprimer_produit">
                                <input type="hidden" name="id" value="<?= (int)$produit->id ?>">
                                <button class="btn btn-danger btn-small" type="submit">Supprimer</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php break; ?>

<?php case 'admin_commandes': ?>
    <section>
        <div class="page-head">
            <h1>Commandes</h1>
            <a class="btn btn-light" href="<?= h(urlPage('admin')) ?>">Dashboard</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Client</th>
                        <th>Email</th>
                        <th>Total</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($data['commandes'] as $commande): ?>
                    <tr>
                        <td>#<?= (int)$commande['id'] ?></td>
                        <td><?= h($commande['nom_client']) ?></td>
                        <td><?= h($commande['email_client']) ?></td>
                        <td><?= h(number_format((float)$commande['total'], 2, ',', ' ')) ?> EUR</td>
                        <td><span class="status"><?= h(str_replace('_', ' ', $commande['statut'])) ?></span></td>
                        <td><?= h(date('d/m/Y H:i', strtotime($commande['date_commande']))) ?></td>
                        <td class="row-actions">
                            <form method="POST" action="<?= h(urlPage('admin_commandes')) ?>">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="statut_commande">
                                <input type="hidden" name="commande_id" value="<?= (int)$commande['id'] ?>">
                                <select name="statut">
                                    <?php foreach (['en_attente', 'confirmee', 'expediee', 'livree', 'annulee'] as $statut): ?>
                                        <option value="<?= h($statut) ?>" <?= isSelected($commande['statut'], $statut) ?>><?= h(str_replace('_', ' ', $statut)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-small" type="submit">OK</button>
                            </form>
                            <a class="btn btn-light btn-small" href="<?= h(urlPage('admin_commandes', ['detail' => (int)$commande['id']])) ?>">Detail</a>
                        </td>
                    </tr>
                    <?php if (($data['detail_commande_id'] ?? 0) === (int)$commande['id']): ?>
                        <tr>
                            <td colspan="7" class="detail-cell">
                                <strong>Adresse :</strong> <?= nl2br(h($commande['adresse'])) ?>
                                <ul>
                                    <?php foreach ($data['detail_commande'] as $detail): ?>
                                        <li><?= h($detail['nom_produit']) ?> x <?= (int)$detail['quantite'] ?> - <?= h(number_format((float)$detail['prix_unitaire'], 2, ',', ' ')) ?> EUR</li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php break; ?>

<?php default: ?>
    <section class="center-card"><h1>Page introuvable</h1></section>
<?php endswitch; ?>
</main>

<div class="chatbot" id="chatbot" hidden>
    <div class="chatbot-head">
        <strong>Assistant IA</strong>
        <button type="button" id="closeChat">Fermer</button>
    </div>
    <div class="chatbot-messages" id="chatMessages">
        <div class="bot-message">Bonjour ! Essayez : "bijoux", "montres moins de 200 euros" ou "idee cadeau".</div>
    </div>
    <form class="chatbot-form" id="chatForm">
        <input id="chatInput" type="text" autocomplete="off" placeholder="Votre question">
        <button class="btn" type="submit">OK</button>
    </form>
</div>

<footer class="site-footer">
    <p><?= h(APP_NAME) ?> - PHP pur, PDO, MVC, POO, sessions et requetes preparees.</p>
</footer>

<script src="<?= h(APP_URL) ?>/assets/js/app.js"></script>
</body>
</html>