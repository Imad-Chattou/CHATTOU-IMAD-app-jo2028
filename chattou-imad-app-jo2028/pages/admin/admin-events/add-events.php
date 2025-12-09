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
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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
        header("Location: add-events.php");
        exit();
    }

    // Vérifiez si les champs obligatoires sont vides
    if (empty($nom_epreuve) || empty($id_sport) || empty($date_epreuve) || empty($heure_epreuve) || empty($id_lieu)) {
        $_SESSION['error'] = "Tous les champs obligatoires doivent être remplis.";
        header("Location: add-events.php");
        exit();
    }

    try {
        // Vérifiez si l'événement existe déjà
        $queryCheck = "SELECT id_epreuve FROM EPREUVE WHERE nom_epreuve = :param_nom_epreuve AND date_epreuve = :param_date_epreuve";
        $statementCheck = $connexion->prepare($queryCheck);
        $statementCheck->bindParam(":param_nom_epreuve", $nom_epreuve, PDO::PARAM_STR);
        $statementCheck->bindParam(":param_date_epreuve", $date_epreuve);
        $statementCheck->execute();

        if ($statementCheck->rowCount() > 0) {
            $_SESSION['error'] = "Un événement avec ce nom existe déjà à cette date.";
            header("Location: add-events.php");
            exit();
        } else {
            // Requête pour ajouter un événement
            $query = "INSERT INTO EPREUVE (nom_epreuve, id_sport, date_epreuve, heure_epreuve, id_lieu) 
                     VALUES (:param_nom_epreuve, :param_id_sport, :param_date_epreuve, :param_heure_epreuve, :param_id_lieu)";
            $statement = $connexion->prepare($query);
            $statement->bindParam(":param_nom_epreuve", $nom_epreuve, PDO::PARAM_STR);
            $statement->bindParam(":param_id_sport", $id_sport, PDO::PARAM_INT);
            $statement->bindParam(":param_date_epreuve", $date_epreuve);
            $statement->bindParam(":param_heure_epreuve", $heure_epreuve);
            $statement->bindParam(":param_id_lieu", $id_lieu, PDO::PARAM_INT);

            // Exécutez la requête
            if ($statement->execute()) {
                $_SESSION['success'] = "L'événement a été ajouté avec succès.";
                header("Location: manage-events.php");
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de l'événement.";
                header("Location: add-events.php");
                exit();
            }
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Erreur de base de données : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        header("Location: add-events.php");
        exit();
    }
}

// Récupérer la liste des sports pour le select
try {
    $querySports = "SELECT id_sport, nom_sport FROM SPORT ORDER BY nom_sport";
    $statementSports = $connexion->prepare($querySports);
    $statementSports->execute();
    $sports = $statementSports->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $sports = [];
}

// Récupérer la liste des lieux pour le select
try {
    $queryLieux = "SELECT id_lieu, nom_lieu, ville_lieu FROM LIEU ORDER BY nom_lieu";
    $statementLieux = $connexion->prepare($queryLieux);
    $statementLieux->execute();
    $lieux = $statementLieux->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lieux = [];
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
    <title>Ajouter un Événement - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Ajouter un Événement</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div style="color: red; background: #ffeeee; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div style="color: green; background: #eeffee; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?= htmlspecialchars($_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
                <?php unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>
        
        <form action="add-events.php" method="post" onsubmit="return confirm('Êtes-vous sûr de vouloir ajouter cet événement ?')">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="nom_epreuve">Nom de l'événement : *</label>
            <input type="text" name="nom_epreuve" id="nom_epreuve" required placeholder="Ex: 100m hommes finale">

            <label for="id_sport">Sport : *</label>
            <select name="id_sport" id="id_sport" required>
                <option value="">-- Sélectionnez un sport --</option>
                <?php if (!empty($sports)): ?>
                    <?php foreach ($sports as $sport): ?>
                        <option value="<?= htmlspecialchars($sport['id_sport'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($sport['nom_sport'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">Aucun sport disponible</option>
                <?php endif; ?>
            </select>

            <label for="date_epreuve">Date : *</label>
            <input type="date" name="date_epreuve" id="date_epreuve" required>

            <label for="heure_epreuve">Heure : *</label>
            <input type="time" name="heure_epreuve" id="heure_epreuve" required>

            <label for="id_lieu">Lieu : *</label>
            <select name="id_lieu" id="id_lieu" required>
                <option value="">-- Sélectionnez un lieu --</option>
                <?php if (!empty($lieux)): ?>
                    <?php foreach ($lieux as $lieu): ?>
                        <option value="<?= htmlspecialchars($lieu['id_lieu'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($lieu['nom_lieu'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($lieu['ville_lieu'])): ?>
                                (<?= htmlspecialchars($lieu['ville_lieu'], ENT_QUOTES, 'UTF-8') ?>)
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">Aucun lieu disponible</option>
                <?php endif; ?>
            </select>

            <input type="submit" value="Ajouter l'Événement">
        </form>
        
        <p class="paragraph-link" style="margin-top: 20px;">
            <a class="link-home" href="manage-events.php">← Retour à la gestion des événements</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

    <script>
        // Définir la date minimum à aujourd'hui
        document.addEventListener('DOMContentLoaded', function() {
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('date_epreuve').min = today;
            
            // Pré-remplir avec demain par défaut
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            const tomorrowStr = tomorrow.toISOString().split('T')[0];
            document.getElementById('date_epreuve').value = tomorrowStr;
            
            // Heure par défaut : 14:00
            document.getElementById('heure_epreuve').value = '14:00';
        });
    </script>
</body>

</html>