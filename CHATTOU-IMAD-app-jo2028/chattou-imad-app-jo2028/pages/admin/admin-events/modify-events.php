<?php
session_start();
require_once("../../../database/database.php");

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

// Vérifiez si l'ID de l'événement est présent dans l'URL
if (!isset($_GET['id_epreuve'])) {
    $_SESSION['error'] = "ID de l'événement manquant.";
    header("Location: manage-events.php");
    exit();
}

$id_epreuve = filter_input(INPUT_GET, 'id_epreuve', FILTER_SANITIZE_NUMBER_INT);

// Générer un token CSRF si ce n'est pas déjà fait
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Récupérer les informations de l'événement
try {
    $query = "SELECT * FROM EPREUVE WHERE id_epreuve = :param_id_epreuve";
    $statement = $connexion->prepare($query);
    $statement->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);
    $statement->execute();
    
    if ($statement->rowCount() === 0) {
        $_SESSION['error'] = "Événement non trouvé.";
        header("Location: manage-events.php");
        exit();
    }
    
    $event = $statement->fetch(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    header("Location: manage-events.php");
    exit();
}

// Récupérer la liste des sports
try {
    $querySports = "SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport";
    $statementSports = $connexion->prepare($querySports);
    $statementSports->execute();
    $sports = $statementSports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sports = [];
}

// Récupérer la liste des lieux
try {
    $queryLieux = "SELECT id_lieu, nom_lieu, ville_lieu FROM LIEU ORDER BY nom_lieu";
    $statementLieux = $connexion->prepare($queryLieux);
    $statementLieux->execute();
    $lieux = $statementLieux->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lieux = [];
}

// Vérifiez si le formulaire est soumis
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Assurez-vous d'obtenir des données sécurisées et filtrées
    $nom_epreuve = filter_input(INPUT_POST, 'nom_epreuve', FILTER_SANITIZE_SPECIAL_CHARS);
    $id_sport = filter_input(INPUT_POST, 'id_sport', FILTER_SANITIZE_NUMBER_INT);
    $date_epreuve = $_POST['date_epreuve'];
    $heure_epreuve = $_POST['heure_epreuve'];
    $id_lieu = filter_input(INPUT_POST, 'id_lieu', FILTER_SANITIZE_NUMBER_INT);

    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error'] = "Token CSRF invalide.";
        header("Location: modify-events.php?id_epreuve=" . $id_epreuve);
        exit();
    }

    // Vérifiez si les champs obligatoires sont vides
    if (empty($nom_epreuve) || empty($id_sport) || empty($date_epreuve) || empty($heure_epreuve) || empty($id_lieu)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: modify-events.php?id_epreuve=" . $id_epreuve);
        exit();
    }

    try {
        // Vérifiez si un autre événement avec le même nom et date existe (sauf celui qu'on modifie)
        $queryCheck = "SELECT id_epreuve FROM EPREUVE 
                      WHERE nom_epreuve = :param_nom_epreuve 
                      AND date_epreuve = :param_date_epreuve 
                      AND id_epreuve != :param_id_epreuve";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_nom_epreuve", $nom_epreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_date_epreuve", $date_epreuve);
        $statementCheck->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Un autre événement avec ce nom existe déjà à cette date.";
            header("Location: modify-events.php?id_epreuve=" . $id_epreuve);
            exit();
        } else {
            // Requête pour modifier l'événement
            $query = "UPDATE EPREUVE 
                     SET nom_epreuve = :param_nom_epreuve, 
                         id_sport = :param_id_sport, 
                         date_epreuve = :param_date_epreuve, 
                         heure_epreuve = :param_heure_epreuve, 
                         id_lieu = :param_id_lieu 
                     WHERE id_epreuve = :param_id_epreuve";
            
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_nom_epreuve", $nom_epreuve, PDO::PARAM_STR);
            $statement->bindParam(":param_id_sport", $id_sport, PDO::PARAM_INT);
            $statement->bindParam(":param_date_epreuve", $date_epreuve);
            $statement->bindParam(":param_heure_epreuve", $heure_epreuve);
            $statement->bindParam(":param_id_lieu", $id_lieu, PDO::PARAM_INT);
            $statement->bindParam(":param_id_epreuve", $id_epreuve, PDO::PARAM_INT);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'événement a été modifié avec succès.";
                header("Location: manage-events.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de la modification de l'événement.";
                header("Location: modify-events.php?id_epreuve=" . $id_epreuve);
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: modify-events.php?id_epreuve=" . $id_epreuve);
        exit();
    }
}

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
    <title>Modifier un Événement - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <ul class="menu">
                <li><a href="../admin.php">Accueil Administration</a></li>
                <li><a href="../admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="../admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="../admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="manage-events.php">Gestion Calendrier</a></li>
                <li><a href="../admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="../admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Modifier un Événement</h1>
        
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
        
        <form action="modify-events.php?id_epreuve=<?php echo htmlspecialchars($id_epreuve); ?>" method="post" 
              onsubmit="return confirm('Êtes-vous sûr de vouloir modifier cet événement ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="nom_epreuve">Nom de l'événement :</label>
            <input type="text" name="nom_epreuve" id="nom_epreuve" 
                   value="<?php echo htmlspecialchars($event['nom_epreuve']); ?>" required>

            <label for="id_sport">Sport :</label>
            <select name="id_sport" id="id_sport" required>
                <option value="">Sélectionnez un sport</option>
                <?php foreach ($sports as $sport): ?>
                    <option value="<?= htmlspecialchars($sport['id_sport'], ENT_QUOTES, 'UTF-8') ?>"
                        <?php if ($sport['id_sport'] == $event['id_sport']) echo 'selected'; ?>>
                        <?= htmlspecialchars($sport['nom_sport'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_epreuve">Date :</label>
            <input type="date" name="date_epreuve" id="date_epreuve" 
                   value="<?php echo htmlspecialchars($event['date_epreuve']); ?>" required>

            <label for="heure_epreuve">Heure :</label>
            <input type="time" name="heure_epreuve" id="heure_epreuve" 
                   value="<?php echo htmlspecialchars($event['heure_epreuve']); ?>" required>

            <label for="id_lieu">Lieu :</label>
            <select name="id_lieu" id="id_lieu" required>
                <option value="">Sélectionnez un lieu</option>
                <?php foreach ($lieux as $lieu): ?>
                    <option value="<?= htmlspecialchars($lieu['id_lieu'], ENT_QUOTES, 'UTF-8') ?>"
                        <?php if ($lieu['id_lieu'] == $event['id_lieu']) echo 'selected'; ?>>
                        <?= htmlspecialchars($lieu['nom_lieu'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($lieu['ville_lieu'])): ?>
                            (<?= htmlspecialchars($lieu['ville_lieu'], ENT_QUOTES, 'UTF-8') ?>)
                        <?php endif; ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <input type="submit" value="Modifier l'Événement">
        </form>
        
        <p class="paragraph-link">
            <a class="link-home" href="manage-events.php">Retour à la gestion des événements</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

</body>

</html>