<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF sécurisé
}

// Récupérer la liste des pays et genres pour les menus déroulants
try {
    $queryPays = "SELECT id_pays, nom_pays FROM pays ORDER BY nom_pays";
    $statementPays = $connexion->prepare($queryPays);
    $statementPays->execute();
    $pays = $statementPays->fetchAll(PDO::FETCH_ASSOC);

    $queryGenres = "SELECT id_genre, nom_genre FROM genre ORDER BY nom_genre";
    $statementGenres = $connexion->prepare($queryGenres);
    $statementGenres->execute();
    $genres = $statementGenres->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $pays = [];
    $genres = [];
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_athlete = filter_input(INPUT_POST, 'nom_athlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $prenom_athlete = filter_input(INPUT_POST, 'prenom_athlete', FILTER_SANITIZE_SPECIAL_CHARS);
    $id_pays = filter_input(INPUT_POST, 'id_pays', FILTER_VALIDATE_INT);
    $id_genre = filter_input(INPUT_POST, 'id_genre', FILTER_VALIDATE_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-athletes.php");
        exit();
    }

    // Vérifiez si les champs sont vides
    if (empty($nom_athlete) || empty($prenom_athlete) || empty($id_pays) || empty($id_genre)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: add-athletes.php");
        exit();
    }

    try {
        // Vérifiez si l'athlète existe déjà
        $queryCheck = "SELECT id_athlete FROM athlete WHERE nom_athlete = :param_nom_athlete AND prenom_athlete = :param_prenom_athlete";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_nom_athlete", $nom_athlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_prenom_athlete", $prenom_athlete, PDO::PARAM_STR);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'athlète existe déjà.";
            header("Location: add-athletes.php");
            exit();
        } else {
            // Requête pour ajouter un athlète
            $query = "INSERT INTO athlete (nom_athlete, prenom_athlete, id_pays, id_genre) 
                     VALUES (:param_nom_athlete, :param_prenom_athlete, :param_id_pays, :param_id_genre)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_nom_athlete", $nom_athlete, PDO::PARAM_STR);
            $statement->bindParam(":param_prenom_athlete", $prenom_athlete, PDO::PARAM_STR);
            $statement->bindParam(":param_id_pays", $id_pays, PDO::PARAM_INT);
            $statement->bindParam(":param_id_genre", $id_genre, PDO::PARAM_INT);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'athlète a été ajouté avec succès.";
                header("Location: manage-athletes.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'athlète.";
                header("Location: add-athletes.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-athletes.php");
        exit();
    }
}

// Afficher les erreurs en PHP (fonctionne à condition d' avoir activé l'option en local)
error_reporting(E_ALL);
ini_set("display_errors", 1);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../../css/normalize.css">
    <link rel="stylesheet" href="../../../css/styles-computer.css">
    <link rel="stylesheet" href="../../../css/styles-responsive.css">
    <link rel="shortcut icon" href="../../../img/favicon.ico" type="image/x-icon">
    <title>Ajouter un Athlète - Jeux Olympiques - Los Angeles 2028</title>
    <style>
        /* Ajoutez votre style CSS ici */
    </style>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Athlète</h1>
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') . '</p>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="add-athletes.php" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet athlète ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="nom_athlete">Nom de l'athlète :</label>
            <input type="text" name="nom_athlete" id="nom_athlete" required>

            <label for="prenom_athlete">Prénom de l'athlète :</label>
            <input type="text" name="prenom_athlete" id="prenom_athlete" required>

            <label for="id_pays">Pays :</label>
            <select name="id_pays" id="id_pays" required>
                <option value="">Sélectionnez un pays</option>
                <?php foreach ($pays as $p): ?>
                    <option value="<?= $p['id_pays'] ?>">
                        <?= htmlspecialchars($p['nom_pays']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_genre">Genre :</label>
            <select name="id_genre" id="id_genre" required>
                <option value="">Sélectionnez un genre</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= $genre['id_genre'] ?>">
                        <?= htmlspecialchars($genre['nom_genre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" value="Ajouter l'Athlète">
        </form>
        <p class="paragraph-link">
            <a class="link-home" href="manage-athletes.php">Retour à la gestion des athlètes</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>