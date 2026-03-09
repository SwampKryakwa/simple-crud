<?php
switch ($_SERVER["REQUEST_METHOD"])
{
    case "GET":
        view_tasks();
        break;
    case "POST":
        create_task();
        break;
    case "PUT":
        update_task();
        break;
    case "DELETE":
        delete_task();
        break;
}

function view_tasks() {
    if (isset($_GET["id"])) {
        view_task();
        return;
    }

    require_once "../DBHandling/connection.php";
    $tasks = DB\DBConnection::$pdo->query("SELECT * FROM tasks");
    $response = ["tasks" => []];
    while ($task = $tasks->fetch())
        array_push($response["tasks"], ["id" => $task["id"], "title" => $task["title"], "description" => $task["description"], "status" => $task["status"]]);
    header("Content-Type: application/json");
    echo json_encode($response);
}

function view_task() {
    if (filter_var($_GET["id"], FILTER_VALIDATE_INT) === false) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid id - must be int"
        ];
        echo json_encode($response);
        die();
    }
    require_once "../DBHandling/connection.php";
    $statement = DB\DBConnection::$pdo->prepare("SELECT COUNT(id) FROM tasks WHERE id = :id");
    $statement->bindParam(":id", $_GET["id"]);
    $statement->execute();
    if ($statement->fetchColumn() == 0) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid id - task doesn't exist"
        ];
        echo json_encode($response);
        die();
    }

    $statement = DB\DBConnection::$pdo->prepare("SELECT * FROM tasks WHERE id = :id");
    $statement->bindParam(":id", $_GET["id"]);
    $statement->execute();
    $task = $statement->fetch();
    $response = ["title" => $task["title"], "description" => $task["description"], "status" => $task["status"]];
    header("Content-Type: application/json");
    echo json_encode($response);
}

function create_task () {
    if (!(validate_field($_POST["title"]) && validate_field($_POST["description"]) && validate_field($_POST["status"]))) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid request body",
            "missing_fields" => []
        ];
        if (!validate_field($_POST["title"]))
            array_push($response["missing_fields"], "title");
        if (!validate_field($_POST["description"]))
            array_push($response["missing_fields"], "description");
        if (!validate_field($_POST["status"]))
            array_push($response["missing_fields"], "status");
        echo json_encode($response);
        die();
    }

    require_once "../DBHandling/connection.php";
    DB\DBConnection::$pdo->beginTransaction();
    $statement = DB\DBConnection::$pdo->prepare("INSERT INTO tasks (title, description, status) VALUES (:title, :description, :status);");
    $statement->bindParam(":title", $_POST["title"]);
    $statement->bindParam(":description", $_POST["description"]);
    $statement->bindParam(":status", $_POST["status"]);
    $statement->execute();
    $result = DB\DBConnection::$pdo->query("SELECT LAST_INSERT_ID();");
    $response = ["id" => $result->fetchColumn()];
    DB\DBConnection::$pdo->commit();
    header("Content-Type: application/json");
    echo json_encode($response);
}

function update_task () {
    if (!isset($_GET["id"])) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Missing id. Format: DELETE /tasks/{id}"
        ];
        echo json_encode($response);
        die();
    }

    if (filter_var($_GET["id"], FILTER_VALIDATE_INT) === false) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid id - must be int"
        ];
        echo json_encode($response);
        die();
    }

    require_once "../DBHandling/connection.php";
    $statement = DB\DBConnection::$pdo->prepare("SELECT COUNT(id) FROM tasks WHERE id = :id");
    $statement->bindParam(":id", $_GET["id"]);
    $statement->execute();
    if ($statement->fetchColumn() == 0) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid id - task doesn't exist"
        ];
        echo json_encode($response);
        die();
    }
    $raw = file_get_contents('php://input');
    parse_str($raw, $data);
    
    $statement = DB\DBConnection::$pdo->prepare("UPDATE tasks SET title = :title, description = :description, status = :status WHERE id = :id");
    $statement->bindParam(":id", $_GET["id"]);
    $statement->bindParam(":title", $data["title"]);
    $statement->bindParam(":description", $data["description"]);
    $statement->bindParam(":status", $data["status"]);
    $statement->execute();
    header("Content-Type: application/json");
    $response = [
        "status" => 200,
        "detail" => "Success"
    ];
    echo json_encode($response);
}

function delete_task () {
    if (!isset($_GET["id"])) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Missing id. Format: DELETE /tasks/{id}"
        ];
        echo json_encode($response);
        die();
    }

    if (filter_var($_GET["id"], FILTER_VALIDATE_INT) === false) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid id - must be int"
        ];
        echo json_encode($response);
        die();
    }

    require_once "../DBHandling/connection.php";
    $statement = DB\DBConnection::$pdo->prepare("SELECT COUNT(id) FROM tasks WHERE id = :id");
    $statement->bindParam(":id", $_GET["id"]);
    $statement->execute();
    if ($statement->fetchColumn() == 0) {
        http_response_code(400);
        header("Content-Type: application/json");
        $response = [
            "status" => 400,
            "detail" => "Invalid id - task doesn't exist"
        ];
        echo json_encode($response);
        die();
    }

    $statement = DB\DBConnection::$pdo->prepare("DELETE FROM tasks WHERE id = :id");
    $statement->bindParam(":id", $_GET["id"]);
    $statement->execute();
    header("Content-Type: application/json");
    $response = [
        "status" => 200,
        "detail" => "Success"
    ];
    echo json_encode($response);
}

function validate_field($field) {
    return isset($field) && !empty($field) && !ctype_space($field);
}
