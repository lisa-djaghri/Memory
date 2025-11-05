<?php
session_start();

// --- Récupération des cartes ---
$all_cards = glob("cards/*.svg"); // Sélectionne toutes les fichiers svg dans le dossier cards
$all_cards = array_filter($all_cards, function($c) { // Parcours les fichiers de all_cards
    return basename($c) !== "back.svg"; // Si le fichier est back.svg il est supprimé de la sélection
});
$all_cards = array_values(array_map('basename', $all_cards)); // Remet en ordre les numéros des cartes (basename = prends juste le nom du fichier, pas le chemin)

// --- Choix avant partie ---
if (!isset($_SESSION['cards']) && !isset($_POST['nb_cards'])) { //Si les cartes pour la session et le nbr de cartes sont remplis, on peut continuer
    ?>
    <!doctype html>
    <html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Memory - Choix</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    </head>
    <body class="bg-light text-center p-5">
        <div class="container">
            <h1 class="mb-4">Memory - Choisis les options</h1>
            <form method="post" class="d-flex flex-column gap-4 align-items-center">

                <div>
                    <h4>Nombre de cartes :</h4>
                    <div class="d-flex flex-wrap gap-3 justify-content-center">
                        <?php foreach ([6, 12, 24, 48, 66] as $n): ?> <!-- On sépare le tableau de nbr en des nbr individuels -->
                            <input type="radio" class="btn-check" name="nb_cards" id="cards<?= $n ?>" value="<?= $n ?>" required><!-- L'id et la value sont le nbr sélectionné par le joueur -->
                            <label class="btn btn-outline-primary btn-lg" for="cards<?= $n ?>"><?= $n ?> cartes</label><!-- Le for est le nbr de cartes sélectionné par le joueur, même chose pour $n cartes -->
                        <?php endforeach; ?>
                    </div>
                </div>

                <div>
                    <h4>Mode :</h4>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="timed" value="1" id="chronoOn" checked>
                        <label class="form-check-label" for="chronoOn">Chronométré</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="timed" value="0" id="chronoOff">
                        <label class="form-check-label" for="chronoOff">Non chronométré</label>
                    </div>
                </div>

                <!-- Bouton démarrer -->
                <button type="submit" class="btn btn-success btn-lg mt-3">
                    🚀 Démarrer la partie
                </button>

            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- Initialisation ---
if (!isset($_SESSION['cards'])) { // Si les cartes sont sélectionnés pour la session en cours
    $nb_cards = (int)$_POST['nb_cards']; // On définit le nbr de cartes sélectionner par le joueur pour les convertir en nombre entiers
    $nb_pairs = intdiv($nb_cards, 2); // On divise le nbr de cartes sélectionnés par 2

    shuffle($all_cards); // Mélange les cartes
    $selected = array_slice($all_cards, 0, $nb_pairs); // Extrait une partie de toutes les cartes en paires

    $cards = [];
    foreach ($selected as $c) {
        $cards[] = $c;
        $cards[] = $c;
    } // On rends les cartes individuelles
    shuffle($cards); // Sélectionne les cartes au hasard

    $_SESSION['cards'] = $cards; // Les cartes qui ont été sélectionnées aléatoirement
    $_SESSION['revealed'] = []; // Cartes révélées
    $_SESSION['selection'] = []; // Cartes sélectionnées
    $_SESSION['moves'] = 0;       // Actions (Départ à 0)
    $_SESSION['timed'] = isset($_POST['timed']) ? (int)$_POST['timed'] : 1; // Si l'option "timed" est sélectionner 
    $_SESSION['nb_cards'] = $nb_cards; // Nbr de cartes sélectionnées pour le jeu

    if ($_SESSION['timed'] === 1) {
        $_SESSION['start_time'] = time(); 
    } // Si le joueur veut faire une partie chronométré le chronomètre est mis en place
}

// --- Gestion clic ---
if (isset($_GET['pos'])) {
    $_SESSION['moves']++;

    $pos = (int)$_GET['pos'];
    if (!in_array($pos, $_SESSION['revealed']) && !in_array($pos, $_SESSION['selection'])) {
        $_SESSION['selection'][] = $pos;
    }
    if (count($_SESSION['selection']) == 2) {
        $a = $_SESSION['selection'][0];
        $b = $_SESSION['selection'][1];
        if ($_SESSION['cards'][$a] === $_SESSION['cards'][$b]) {
            $_SESSION['revealed'][] = $a;
            $_SESSION['revealed'][] = $b;
        }
        $_SESSION['selection'] = [];
    }
}

// --- Reset ---
if (isset($_POST['reset'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// --- Calcul chrono ---
$elapsed = 0;
if ($_SESSION['timed'] === 1) {
    $elapsed = time() - ($_SESSION['start_time'] ?? time());
}
$minutes = floor($elapsed / 60);
$seconds = $elapsed % 60;

// --- Affichage partie ---
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Memory Game</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        td img { max-width: 80px; height: auto; }
        table { margin: auto; }
    </style>
</head>
<body class="bg-light text-center p-4">
<div class="container">

    <h1 class="mb-3">Memory Game</h1>

    <!-- Infos -->
    <div class="alert alert-info">
        <strong>Coups :</strong> <?= $_SESSION['moves'] ?>
        <?php if ($_SESSION['timed'] === 1): ?>
            | <strong>Temps :</strong> <?= sprintf("%02d:%02d", $minutes, $seconds) ?>
        <?php endif; ?>
        <br>
        <?php if ($_SESSION['timed'] === 1): ?>
            <span class="badge bg-danger">Chronométré - <?= $_SESSION['nb_cards'] ?> cartes</span>
        <?php else: ?>
            <span class="badge bg-success">Libre - <?= $_SESSION['nb_cards'] ?> cartes</span>
        <?php endif; ?>
    </div>

    <!-- Bouton reset avec dropdown -->
<!-- Bouton reset avec dropdown -->
<!-- Bouton reset avec dropdown -->
<div class="btn-group mb-4">
  <form method="post" action="index.php">
    <button type="submit" name="reset" value="1" class="btn btn-success">
      <i class="bi bi-arrow-repeat"></i> Nouvelle partie
    </button>
  </form>
  <button type="button" class="btn btn-success dropdown-toggle dropdown-toggle-split" 
          data-bs-toggle="dropdown" aria-expanded="false">
    <span class="visually-hidden">Choisir</span>
  </button>
  <ul class="dropdown-menu">
    <?php foreach ([6, 12, 24, 48, 66] as $n): ?>
      <li>
        <form method="post" action="index.php">
          <input type="hidden" name="nb_cards" value="<?= $n ?>">
          <input type="hidden" name="timed" value="1">
          <button type="submit" class="dropdown-item"><?= $n ?> cartes (chrono)</button>
        </form>
      </li>
      <li>
        <form method="post" action="index.php">
          <input type="hidden" name="nb_cards" value="<?= $n ?>">
          <input type="hidden" name="timed" value="0">
          <button type="submit" class="dropdown-item"><?= $n ?> cartes (libre)</button>
        </form>
      </li>
      <li><hr class="dropdown-divider"></li>
    <?php endforeach; ?>
  </ul>
</div>



    <!-- Plateau -->
    <table class="table table-borderless">
        <tr>
        <?php foreach ($_SESSION['cards'] as $i => $filename): ?>
            <td>
                <?php if (in_array($i, $_SESSION['revealed']) || in_array($i, $_SESSION['selection'])): ?>
                    <img src="cards/<?= $filename ?>" class="img-fluid">
                <?php else: ?>
                    <a href="?pos=<?= $i ?>">
                        <img src="cards/back.svg" class="img-fluid">
                    </a>
                <?php endif; ?>
            </td>
            <?php if (($i+1) % 12 == 0): ?></tr><tr><?php endif; ?>
        <?php endforeach; ?>
        </tr>
    </table>

    <?php if (count($_SESSION['revealed']) == count($_SESSION['cards'])): ?>
        <div class="alert alert-success mt-3">
            🎉 Partie terminée en <?= $_SESSION['moves'] ?> coups
            <?php if ($_SESSION['timed'] === 1): ?>
                et <?= sprintf("%02d:%02d", $minutes, $seconds) ?> !
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
