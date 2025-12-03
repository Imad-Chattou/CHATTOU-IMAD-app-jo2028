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

// Gestion de la suppression directement dans manage-places.php
if (isset($_GET['delete_id'])) {
    require_once("../../../database/database.php");
    
    $id_lieu = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
    
    if ($id_lieu !== false) {
        try {
            // Récupérer le nom du lieu pour vérifier s'il est utilisé
            $nameQuery = "SELECT nom_lieu FROM lieu WHERE id_lieu = :id_lieu";
            $nameStatement = $connexion->prepare($nameQuery);
            $nameStatement->bindParam(':id_lieu', $id_lieu, PDO::PARAM_INT);
            $nameStatement->execute();
            $lieu = $nameStatement->fetch(PDO::FETCH_ASSOC);
            
            if (!$lieu) {
                $_SESSION['error'] = "Lieu non trouvé.";
                header('Location: manage-places.php');
                exit();
            }
            
            $nom_lieu = $lieu['nom_lieu'];

            // Vérifier d'abord si le lieu est utilisé dans la table events (via le nom du lieu)
            $checkQuery = "SELECT COUNT(*) as count FROM events WHERE venue = :nom_lieu";
            $checkStatement = $connexion->prepare($checkQuery);
            $checkStatement->bindParam(':nom_lieu', $nom_lieu, PDO::PARAM_STR);
            $checkStatement->execute();
            $result = $checkStatement->fetch(PDO::FETCH_ASSOC);

            if ($result['count'] > 0) {
                $_SESSION['error'] = "Impossible de supprimer ce lieu car il est utilisé par " . $result['count'] . " événement(s).";
            } else {
                // Supprimer le lieu
                $sql = "DELETE FROM lieu WHERE id_lieu = :id_lieu";
                $statement = $connexion->prepare($sql);
                $statement->bindParam(':id_lieu', $id_lieu, PDO::PARAM_INT);
                $statement->execute();

                $_SESSION['success'] = "Le lieu '$nom_lieu' a été supprimé avec succès.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Erreur lors de la suppression du lieu : " . $e->getMessage();
        }
    }
    header('Location: manage-places.php');
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
    <title>Gestion des Lieux - Jeux Olympiques - Los Angeles 2028</title>
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
        <h1>Gestion des Lieux</h1>
        
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
            <button onclick="openAddPlaceForm()">Ajouter un Lieu</button>
        </div>
        
        <!-- Tableau des lieux -->
        <?php
        require_once("../../../database/database.php");

        try {
            // Requête pour récupérer la liste des lieux depuis la base de données
            $query = "SELECT * FROM lieu ORDER BY nom_lieu";
            $statement = $connexion->prepare($query);
            $statement->execute();

            // Vérifier s'il y a des résultats
            if ($statement->rowCount() > 0) {
                echo "<table><tr><th>Lieu</th><th>Adresse</th><th>Ville</th><th>Modifier</th><th>Supprimer</th></tr>";

                // Afficher les données dans un tableau
                while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['nom_lieu'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['adresse_lieu'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td>" . htmlspecialchars($row['cp_lieu'] . ' ' . $row['ville_lieu'], ENT_QUOTES, 'UTF-8') . "</td>";
                    echo "<td><button onclick='openModifyPlaceForm({$row['id_lieu']})'>Modifier</button></td>";
                    echo "<td><button onclick='deletePlaceConfirmation({$row['id_lieu']})'>Supprimer</button></td>";
                    echo "</tr>";
                }

                echo "</table>";
            } else {
                echo "<p>Aucun lieu trouvé.</p>";
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
        function openAddPlaceForm() {
            window.location.href = 'add-places.php';
        }

        function openModifyPlaceForm(id_lieu) {
            window.location.href = 'modify-places.php?id_lieu=' + id_lieu;
        }

        function deletePlaceConfirmation(id_lieu) {
            if (confirm("Êtes-vous sûr de vouloir supprimer ce lieu?")) {
                window.location.href = 'manage-places.php?delete_id=' + id_lieu;
            }
        }
    </script>
</body>

</html>