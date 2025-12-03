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
function checkCSRFToken()
{
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

require_once("../../../database/database.php");

// LISTER TOUS LES UTILISATEURS
try {
    // Correction : utiliser la table 'administrateur'
    $query = "SELECT id_admin, nom_admin, prenom_admin, login, password FROM administrateur ORDER BY nom_admin, prenom_admin";
    $statement = $connexion->prepare($query);
    $statement->execute();
    $utilisateurs = $statement->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur : " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
    $utilisateurs = [];
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
    <title>Gestion des Utilisateurs - Jeux Olympiques - Los Angeles 2028</title>
</head>

<body>
    <header>
        <nav>
            <!-- Menu vers les pages sports, events, et results -->
            <ul class="menu">
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin.php">Accueil Administration</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-sports/manage-sports.php">Gestion Sports</a>
                </li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-places/manage-places.php">Gestion Lieux</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-countries/manage-countries.php">Gestion Pays</a>
                </li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-events/manage-events.php">Gestion Calendrier</a>
                </li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-athletes/manage-athletes.php">Gestion
                        Athlètes</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-results/manage-results.php">Gestion
                        Résultats</a></li>
                <li><a href="/CHATTOU-IMAD-app-jo2028/pages/admin/admin-users/manage-users.php">Gestion Utilisateurs</a>
                </li>
                <li><a href="../../logout.php">Déconnexion</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <h1>Gestion des Utilisateurs</h1>

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

        <div class="action-buttons">
            <button onclick="openAddUserForm()">Ajouter un Utilisateur</button>
        </div>

        <!-- Tableau des utilisateurs -->
        <?php
        if (!empty($utilisateurs)) {
            echo "<table>
                    <tr>
                        <th>Nom</th>
                        <th>Prénom</th>
                        <th>Login</th>
                        <th>Modifier</th>
                        <th>Supprimer</th>
                    </tr>";

            foreach ($utilisateurs as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['nom_admin'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($user['prenom_admin'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td>" . htmlspecialchars($user['login'], ENT_QUOTES, 'UTF-8') . "</td>";
                echo "<td><button onclick='openModifyUserForm({$user['id_admin']})'>Modifier</button></td>";
                echo "<td><button onclick='deleteUserConfirmation({$user['id_admin']})'>Supprimer</button></td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>Aucun utilisateur trouvé.</p>";
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
        function openAddUserForm() {
            window.location.href = 'add-users.php';
        }

        function openModifyUserForm(id_admin) {
            window.location.href = 'modify-users.php?id_admin=' + id_admin;
        }

        function deleteUserConfirmation(id_admin) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cet utilisateur?")) {
                window.location.href = 'delete-users.php?id_admin=' + id_admin;
            }
        }
    </script>
</body>

</html>