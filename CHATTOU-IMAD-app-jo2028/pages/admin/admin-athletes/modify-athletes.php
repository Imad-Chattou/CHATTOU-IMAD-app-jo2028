<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'athlète est fourni dans l'URL
if (!isset($_GET['id_athlete'])) {
    $_SESSION['error'] = "ID de l'athlète manquant.";
    header("Location: manage-athletes.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);

// Vérifiez si l'ID de l'athlète est un entier valide
if (!$id_athlete && $id_athlete !== 0) {
    $_SESSION['error'] = "ID de l'athlète invalide.";
    header("Location: manage-athletes.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations de l'athlète pour affichage dans le formulaire
try {
    $queryAthlete = "SELECT nom_athlete, prenom_athlete, id_pays, id_genre FROM athlete WHERE id_athlete = :param_id_athlete";
    $statementAthlete = $connexion->prepare($queryAthlete);
    $statementAthlete->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);
    $statementAthlete->execute();

    if ($statementAthlete->rowCount() > 0) {
        $athlete = $statementAthlete->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Athlète non trouvé.";
        header("Location: manage-athletes.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-athletes.php");
    exit();
}

// Récupérer la liste des pays et genres
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

    // Vérifiez si les champs sont vides
    if (empty($nom_athlete) || empty($prenom_athlete) || empty($id_pays) || empty($id_genre)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: modify-athletes.php?id_athlete=$id_athlete");
        exit();
    }

    try {
        // Vérifiez si l'athlète existe déjà
        $queryCheck = "SELECT id_athlete FROM athlete WHERE nom_athlete = :param_nom_athlete AND prenom_athlete = :param_prenom_athlete AND id_athlete <> :param_id_athlete";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_nom_athlete", $nom_athlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_prenom_athlete", $prenom_athlete, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "L'athlète existe déjà.";
            header("Location: modify-athletes.php?id_athlete=$id_athlete");
            exit();
        }

        // Requête pour mettre à jour l'athlète
        $query = "UPDATE athlete SET nom_athlete = :param_nom_athlete, prenom_athlete = :param_prenom_athlete, 
                 id_pays = :param_id_pays, id_genre = :param_id_genre 
                 WHERE id_athlete = :param_id_athlete";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":param_nom_athlete", $nom_athlete, PDO::PARAM_STR);
        $statement->bindParam(":param_prenom_athlete", $prenom_athlete, PDO::PARAM_STR);
        $statement->bindParam(":param_id_pays", $id_pays, PDO::PARAM_INT);
        $statement->bindParam(":param_id_genre", $id_genre, PDO::PARAM_INT);
        $statement->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "L'athlète a été modifié avec succès.";
            header("Location: manage-athletes.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification de l'athlète.";
            header("Location: modify-athletes.php?id_athlete=$id_athlete");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-athletes.php?id_athlete=$id_athlete");
        exit();
    }
}
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
    <title>Modifier un Athlète - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Modifier un Athlète</h1>

        <!-- Affichage des messages d'erreur ou de succès -->
        <?php
        if (isset($_SESSION['error'])) {
            echo '<p style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        if (isset($_SESSION['success'])) {
            echo '<p style="color: green;">' . $_SESSION['success'] . '</p>';
            unset($_SESSION['success']);
        }
        ?>

        <form action="modify-athletes.php?id_athlete=<?php echo $id_athlete; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet athlète?')">
            <label for="nom_athlete">Nom de l'athlète :</label>
            <input type="text" name="nom_athlete" id="nom_athlete"
                value="<?php echo htmlspecialchars($athlete['nom_athlete']); ?>" required>

            <label for="prenom_athlete">Prénom de l'athlète :</label>
            <input type="text" name="prenom_athlete" id="prenom_athlete"
                value="<?php echo htmlspecialchars($athlete['prenom_athlete']); ?>" required>

            <label for="id_pays">Pays :</label>
            <select name="id_pays" id="id_pays" required>
                <option value="">Sélectionnez un pays</option>
                <?php foreach ($pays as $p): ?>
                    <option value="<?php echo $p['id_pays']; ?>" <?php echo ($athlete['id_pays'] == $p['id_pays']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($p['nom_pays']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_genre">Genre :</label>
            <select name="id_genre" id="id_genre" required>
                <option value="">Sélectionnez un genre</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?php echo $genre['id_genre']; ?>" <?php echo ($athlete['id_genre'] == $genre['id_genre']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($genre['nom_genre']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" value="Modifier l'Athlète">
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