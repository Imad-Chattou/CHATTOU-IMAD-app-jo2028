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

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $id_athlete = filter_input(INPUT_POST, 'id_athlete', FILTER_VALIDATE_INT);
    $id_epreuve = filter_input(INPUT_POST, 'id_epreuve', FILTER_VALIDATE_INT);
    $resultat = filter_input(INPUT_POST, 'resultat', FILTER_SANITIZE_SPECIAL_CHARS);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: add-results.php");
        exit();
    }

    // Vérifiez si tous les champs sont remplis
    if (empty($id_athlete) || empty($id_epreuve) || empty($resultat)) {
        $_SESSION['error'] = "Tous les champs sont obligatoires.";
        header("Location: add-results.php");
        exit();
    }

    try {
        // Vérifiez si le résultat existe déjà
        $queryCheck = "SELECT id_athlete FROM PARTICIPER WHERE id_athlete = :param_id_athlete AND id_epreuve = :param_id_epreuve";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);
        $statementCheck->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Cet athlète a déjà un résultat pour cette épreuve.";
            header("Location: add-results.php");
            exit();
        } else {
            // Requête pour ajouter un résultat
            $query = "INSERT INTO PARTICIPER (id_athlete, id_epreuve, resultat) VALUES (:param_id_athlete, :param_id_epreuve, :param_resultat)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_id_athlete", $id_athlete, PDO::PARAM_INT);
            $statement->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);
            $statement->bindParam(":param_resultat", $resultat, PDO::PARAM_STR);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "Le résultat a été ajouté avec succès.";
                header("Location: manage-results.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout du résultat.";
                header("Location: add-results.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-results.php");
        exit();
    }
}

// Récupération des données pour les menus déroulants
try {
    $athletes_list = $connexion->query("
        SELECT id_athlete, nom_athlete, prenom_athlete 
        FROM ATHLETE 
        ORDER BY nom_athlete, prenom_athlete
    ")->fetchAll(PDO::FETCH_ASSOC);

    $epreuves_list = $connexion->query("
        SELECT id_epreuve, nom_epreuve 
        FROM EPREUVE 
        ORDER BY nom_epreuve
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors du chargement des données : " . $e->getMessage();
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
    <title>Ajouter un Résultat - Jeux Olympiques - Los Angeles 2028</title>
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
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="./manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Ajouter un Résultat</h1>
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
        <form action="add-results.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter ce résultat ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="id_athlete">Athlète :</label>
            <select name="id_athlete" id="id_athlete" required>
                <option value="">-- Sélectionnez un athlète --</option>
                <?php foreach ($athletes_list as $athlete): ?>
                    <option value="<?php echo $athlete['id_athlete']; ?>">
                        <?php echo htmlspecialchars($athlete['prenom_athlete'] . ' ' . $athlete['nom_athlete'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="id_epreuve">Épreuve :</label>
            <select name="id_epreuve" id="id_epreuve" required>
                <option value="">-- Sélectionnez une épreuve --</option>
                <?php foreach ($epreuves_list as $epreuve): ?>
                    <option value="<?php echo $epreuve['id_epreuve']; ?>">
                        <?php echo htmlspecialchars($epreuve['nom_epreuve'], ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="resultat">Résultat :</label>
            <input type="text" name="resultat" id="resultat" placeholder="Ex: 9.58s, 3-0, 45.67" required>

            <input type="submit" value="Ajouter le Résultat">
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