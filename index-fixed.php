<?php
	// Connexion unique à la base de données SQLite
	$databasePath = __DIR__ . '/mydatabase.sqlite';
	$db = new SQLite3($databasePath);

	// Tâble des tâches, créées si non existante
	$query = "CREATE TABLE IF NOT EXISTS tasks (
		id INTEGER PRIMARY KEY AUTOINCREMENT,
		task TEXT NOT NULL,
		status TEXT NOT NULL CHECK(status IN ('created', 'started', 'done')) DEFAULT 'created',
		created_at DATETIME DEFAULT CURRENT_TIMESTAMP
	)";
	if (! $db->exec($query)) {
		echo "Error creating table: " . $db->lastErrorMsg();
	}

	// Traitement du formulaire de mise à jour
	if (isset($_POST['update'])) {
		$id = intval($_POST['id']);
		$task = $_POST['task'];
		$status = $_POST['status'];

		// Requête de mise à jour avec des requêtes préparées
		$stmt = $db->prepare("UPDATE tasks SET task = :task, status = :status WHERE id = :id");
		$stmt->bindValue(':task', $task, SQLITE3_TEXT);
		$stmt->bindValue(':status', $status, SQLITE3_TEXT);
		$stmt->bindValue(':id', $id, SQLITE3_INTEGER);
		$stmt->execute();

		// Redirection pour éviter la soumission multiple du formulaire
		header("Location: ".$_SERVER['PHP_SELF']);
		exit();
	}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>To-Do List</title>
</head>
<body>
    <h1>To-Do List</h1>
    <form method="POST" action="">
        <input type="text" name="task" placeholder="Nouvelle tâche" required>
        <button type="submit" name="add">Ajouter</button>
    </form>

    <form method="GET" action="">
        <input type="text" name="search" placeholder="Rechercher une tâche">
        <button type="submit">Rechercher</button>
    </form>

    <h2>Liste des tâches</h2>
    <ul>
        <?php
        // Recherche de tâches avec des requêtes préparées
        $search = $_GET['search'] ?? '';
        $stmt = $db->prepare("SELECT * FROM tasks WHERE task LIKE :search");
        $stmt->bindValue(':search', "%$search%", SQLITE3_TEXT);
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $task = htmlspecialchars($row['task'], ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8');
            echo "<li>
                  <strong>{$task}</strong> (Statut : {$status})
                  <a href='?delete={$row['id']}'>Supprimer</a>
                  <a href='?edit={$row['id']}'>Modifier</a>
                  </li>";
        }

        // Suppression d'une tâche avec des requêtes préparées
        if (isset($_GET['delete'])) {
            $id = intval($_GET['delete']);
            $stmt = $db->prepare("DELETE FROM tasks WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->execute();
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }

        // Modification d'une tâche avec des requêtes préparées et échappement des sorties
        if (isset($_GET['edit'])) {
            $id = intval($_GET['edit']);
            $stmt = $db->prepare("SELECT task, status FROM tasks WHERE id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            if ($row) {
                $task = htmlspecialchars($row['task'], ENT_QUOTES, 'UTF-8');
                $status = htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8');
                echo "<form method='POST' action=''>
                      <input type='text' name='task' value='{$task}'>
                      <select name='status'>
                          <option value='created'" . ($status == 'created' ? ' selected' : '') . ">created</option>
                          <option value='started'" . ($status == 'started' ? ' selected' : '') . ">started</option>
                          <option value='done'" . ($status == 'done' ? ' selected' : '') . ">Terminée</option>
                      </select>
                      <input type='hidden' name='id' value='{$id}'>
                      <button type='submit' name='update'>Mettre à jour</button>
                      </form>";
            }
        }

        // Ajout d'une tâche avec des requêtes préparées
        if (isset($_POST['add'])) {
            $task = $_POST['task'];
            $stmt = $db->prepare("INSERT INTO tasks (task) VALUES (:task)");
            $stmt->bindValue(':task', $task, SQLITE3_TEXT);
            $stmt->execute();
            header("Location: ".$_SERVER['PHP_SELF']);
            exit();
        }
        ?>
    </ul>
</body>
</html>