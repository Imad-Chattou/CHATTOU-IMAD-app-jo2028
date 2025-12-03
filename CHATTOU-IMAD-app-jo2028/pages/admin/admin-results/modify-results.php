<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si les IDs sont fournis dans l'URL
if (!isset($_GET['id_athlete']) || !isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "IDs de l'athlète et de l'épreuve manquants.";
    header("Location: manage-results.php");
    exit();
}

$id_athlete = filter_input(INPUT_GET, 'id_athlete', FILTER_VALIDATE_INT);
$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_VALIDATE_INT);

// Vérifiez si les IDs sont des entiers valides
if (!$id_athlete && $id_athlete !== 0 || !$id_epreuve && $id_epreuve !== 0) {
    $_SESSION['error'] = "IDs de l'athlète ou de l'épreuve invalides.";
    header("Location: manage-results.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du résultat pour affichage dans le formulaire
try {
    $queryResult = "SELECT p.resultat, a.prenom_athlete, a.nom_athlete, e.nom_epreuve 
                    FROM PARTICIPER p
                    JOIN ATHLETE a ON p.id_athlete = a.id_athlete
                    JOIN EPREUVE e ON p.id_epreuve = e.id_epreuve
                    WHERE p.id_athlete = :param_id_athlete AND p.id_epreuve = :param_id_epreuve";
    $statementResult = $connexion->prepare($queryResult);
    $statementResult->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);
    $statementResult->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);
    $statementResult->execute();

    if ($statementResult->rowCount() > 0) {
        $resultat = $statementResult->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Résultat non trouvé.";
        header("Location: manage-results.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-results.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nouveau_resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le résultat est vide
    if (empty($nouveau_resultat)) {
        $_SESSION['error'] = "Le résultat ne peut pas être vide.";
        header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
        exit();
    }

    try {
        // Requête pour mettre à jour le résultat
        $query = "UPDATE PARTICIPER SET resultat = :param_resultat 
                  WHERE id_athlete = :param_id_athlete AND id_epreuve = :param_id_epreuve";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":param_resultat", $nouveau_resultat, PDO::PARAM_STR);
        $statement->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);
        $statement->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le résultat a été modifié avec succès.";
            header("Location: manage-results.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du résultat.";
            header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-results.php?id_athlete=$id_athlete&id_epreuve=$id_epreuve");
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
    <title>Modifier un Résultat - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="manage-sports.php">Gestion Sports</a></li>
                <li><a href="manage-places.php">Gestion Lieux</a></li>
                <li><a href="manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Résultat</h1>

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

        <form action="modify-results.php?id_athlete=<?php echo $id_athlete; ?>&id_epreuve=<?php echo $id_epreuve; ?>"
            method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce résultat?')">
            <p><strong>Athlète :</strong>
                <?php echo htmlspecialchars($resultat['prenom_athlete'] . ' ' . $resultat['nom_athlete']); ?></p>
            <p><strong>Épreuve :</strong> <?php echo htmlspecialchars($resultat['nom_epreuve']); ?></p>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat"
                value="<?php echo htmlspecialchars($resultat['resultat']); ?>" required>
            <input type="submit" value="Modifier le Résultat">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-results.php">Retour à la gestion des résultats</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>