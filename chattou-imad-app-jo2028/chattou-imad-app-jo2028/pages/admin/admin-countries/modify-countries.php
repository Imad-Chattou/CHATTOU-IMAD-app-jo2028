<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID du pays est fourni dans l'URL
if (!isset($_GET['id_pays'])) {
    $_SESSION['error'] = "ID du pays manquant.";
    header("Location: manage-countries.php");
    exit();
}

$id_pays = filter_input(INPUT_GET, 'id_pays', FILTER_VALIDATE_INT);

// Vérifiez si l'ID du pays est un entier valide
if (!$id_pays && $id_pays !== 0) {
    $_SESSION['error'] = "ID du pays invalide.";
    header("Location: manage-countries.php");
    exit();
}

// Vider les messages de succès précédents
if (isset($_SESSION['success'])) {
    unset($_SESSION['success']);
}

// Récupérez les informations du pays pour affichage dans le formulaire
try {
    $queryPays = "SELECT nom_pays FROM PAYS WHERE id_pays = :param_id_pays";
    $statementPays = $connexion->prepare($queryPays);
    $statementPays->bindParam(":param_id_pays", $id_pays, PDO::PARAM_INT);
    $statementPays->execute();

    if ($statementPays->rowCount() > 0) {
        $pays = $statementPays->fetch(PDO::FETCH_ASSOC);
    } else {
        $_SESSION['error'] = "Pays non trouvé.";
        header("Location: manage-countries.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
    header("Location: manage-countries.php");
    exit();
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_pays = filter_input(INPUT_POST, 'nom_pays', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérifiez si le champ obligatoire est vide
    if (empty($nom_pays)) {
        $_SESSION['error'] = "Le champ nom du pays est obligatoire.";
        header("Location: modify-countries.php?id_pays=$id_pays");
        exit();
    }

    try {
        // Vérifiez si le nom du pays existe déjà pour un autre pays
        $queryCheckNom = "SELECT id_pays FROM PAYS WHERE nom_pays = :param_nom_pays AND id_pays <> :param_id_pays";
        $statementCheckNom = $connexion->prepare($queryCheckNom);
        $statementCheckNom->bindParam(":param_nom_pays", $nom_pays, PDO::PARAM_STR);
        $statementCheckNom->bindParam(":param_id_pays", $id_pays, PDO::PARAM_INT);
        $statementCheckNom->execute();

        if ($statementCheckNom->rowCount() > 0) {
            $_SESSION['error'] = "Le nom du pays existe déjà.";
            header("Location: modify-countries.php?id_pays=$id_pays");
            exit();
        }

        // Requête pour mettre à jour le pays
        $query = "UPDATE PAYS SET nom_pays = :param_nom_pays WHERE id_pays = :param_id_pays";
        $statement = $connexion->prepare($query);
        $statement->bindParam(":param_nom_pays", $nom_pays, PDO::PARAM_STR);
        $statement->bindParam(":param_id_pays", $id_pays, PDO::PARAM_INT);

        // Exécutez la requête
        if ($statement->execute()) {
            $_SESSION['success'] = "Le pays a été modifié avec succès.";
            header("Location: manage-countries.php");
            exit();
        } else {
            $_SESSION['error'] = "Erreur lors de la modification du pays.";
            header("Location: modify-countries.php?id_pays=$id_pays");
            exit();
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . $e->getMessage();
        header("Location: modify-countries.php?id_pays=$id_pays");
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
    <title>Modifier un Pays - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="./manage-countries.php">Gestion Pays</a></li>
                <li><a href="../admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Pays</h1>

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

        <form action="modify-countries.php?id_pays=<?php echo $id_pays; ?>" method="post"
            onsubmit="return confirm('Êtes-vous sûr de vouloir modifier ce pays?')">
            <label for="nom_pays">Nom du pays :</label>
            <input type="text" name="nom_pays" id="nom_pays"
                value="<?php echo htmlspecialchars($pays['nom_pays']); ?>" required>

            <input type="submit" value="Modifier le Pays">
        </form>

        <p class="paragraph-link">
            <a class="link-home" href="manage-countries.php">Retour à la gestion des pays</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>
</body>

</html>