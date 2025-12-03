<?php
session_start();

// Vérifiez si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../../../index.php');
    exit();
}

$login = $_SESSION['login'];
$nom_utilisateur = $_SESSION['prenom_admin'];
$prenom_utilisateur = $_SESSION['nom_admin'];

// Fonction pour vérifier le token CSRF
function checkCSRFToken() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
            die('Token CSRF invalide.');
        }
    }
}

// Générer un token CSRF si ce n'est pas déjà fait
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Génère un token CSRF
}

// Gestion de la suppression directement dans manage-athletes.php
if (isset($_GET['delete_id'])) {
    require_once("../../../database/database.php");

    $id_athlete = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);

    if ($id_athlete !== false) {
        try {
            // Vérifier d'abord si l'athlète est utilisé dans d'autres tables
            $checkQuery = "SELECT COUNT(*) as count FROM participer WHERE id_athlete = :id_athlete";
            $checkStatement = $connexion->prepare($checkQuery);
            $checkStatement->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
            $checkStatement->execute();
            $result = $checkStatement->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Impossible de supprimer cet athlète car il participe à " . $result['count'] . " épreuve(s).";
            } else {
                // Récupérer le nom de l'athlète pour le message de succès
                $nameQuery = "SELECT nom_athlete, prenom_athlete FROM athlete WHERE id_athlete = :id_athlete";
                $nameStatement = $connexion->prepare($nameQuery);
                $nameStatement->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
                $nameStatement->execute();
                $athlete = $nameStatement->fetch(PDO::FETCH_ASSOC);
                $nom_complet = $athlete['prenom_athlete'] . ' ' . $athlete['nom_athlete'] ?? "l'athlète";

                // Supprimer l'athlète
                $sql = "DELETE FROM athlete WHERE id_athlete = :id_athlete";
                $statement = $connexion->prepare($sql);
                $statement->bindParam(':id_athlete', $id_athlete, PDO::PARAM_INT);
                $statement->execute();

                $_SESSION['success'] = "L'athlète '$nom_complet' a été supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression de l'athlète : " . $e->getMessage();
        }
    }
    header('Location: manage-athletes.php');
    exit();
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
    <title>Gestion des Athlètes - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin.php">Accueil Administration</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-sports/manage-sports.php">Gestion Sports</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-countries/manage-countries.php">Gestion Pays</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-events/manage-events.php">Gestion Calendrier</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-athletes/manage-athletes.php">Gestion Athlètes</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-results/manage-results.php">Gestion Résultats</a></li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Gestion des Athlètes</h1>
        
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

        <div class="action-buttons">
            <button onclick="openAddAthleteForm()">Ajouter un Athlète</button>
        </div>
        
        <!-- Tableau des athlètes -->
        <?php
        require_once("../../../database/database.php");

        try {
            // Requête pour récupérer la liste des athlètes avec les informations des pays et genres depuis la base de données
            $query = "SELECT a.*, p.nom_pays, g.nom_genre 
                     FROM athlete a 
                     LEFT JOIN pays p ON a.id_pays = p.id_pays 
                     LEFT JOIN genre g ON a.id_genre = g.id_genre 
                     ORDER BY a.nom_athlete, a.prenom_athlete";
            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table><tr><th>Nom</th><th>Prénom</th><th>Pays</th><th>Genre</th><th>Modifier</th><th>Supprimer</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_athlete'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['prenom_athlete'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_pays'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['nom_genre'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td><button onclick='openModifyAthleteForm({$row['id_athlete']})'>Modifier</button></td>";
                    echo "<td><button onclick='deleteAthleteConfirmation({$row['id_athlete']})'>Supprimer</button></td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun athlète trouvé.</p>";
            }
        } catch (PDOException $e) {
            echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
        ?>
        
        <p class="paragraph-link">
            <a class="link-home" href="../admin.php">Accueil administration</a>
        </p>
    </main>

    <footer>
        <figure>
            <img src="../../../img/logo-jo.png" alt="logo Jeux Olympiques - Los Angeles 2028">
        </figure>
    </footer>

    <script>
        function openAddAthleteForm() {
            window.location.href = 'add-athletes.php';
        }

        function openModifyAthleteForm(id_athlete) {
            window.location.href = 'modify-athletes.php?id_athlete=' + id_athlete;
        }

        function deleteAthleteConfirmation(id_athlete) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cet athlète?")) {
                window.location.href = 'manage-athletes.php?delete_id=' + id_athlete;
            }
        }
    </script>
</body>

</html>