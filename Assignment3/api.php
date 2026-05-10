<?php

ob_start();

$config_path = __DIR__ . '/COS216/PA3/config.php';

if (!file_exists($config_path)) {
    ob_end_clean();
    header("Content-Type: application/json");
    http_response_code(500);
    echo json_encode(array(
        "status"    => "error",
        "timestamp" => (string) round(microtime(true) * 1000),
        "data"      => "Server configuration error: config.php not found. Check the path in api.php."
    ));
    exit;
}

require_once $config_path;

ob_end_clean();
header("Content-Type: application/json");

class FlightAPI {
    private static $instance = null;
    private $db;

    private function __construct($dbConnection) {
        $this->db = $dbConnection;
    }

    public static function getInstance($dbConnection) {
        if (self::$instance == null) {
            self::$instance = new FlightAPI($dbConnection);
        }
        return self::$instance;
    }

    public function processRequest() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendError("Method Not Allowed. This API only accepts POST requests.", 405);
        }

        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        if (!$data || !isset($data['type'])) {
            $this->sendError("Invalid request. The 'type' parameter is required.", 400);
        }

        switch ($data['type']) {
            case 'Register':
                $this->registerUser($data);
                break;
            case 'Login':
                $this->loginUser($data);
                break;
            case 'GetAllPlanes':
                $this->validateApiKey($data);
                $this->getAllPlanes($data);
                break;
            case 'GetAllAirports':
                $this->validateApiKey($data);
                $this->getAllAirports($data);
                break;
            default:
                $this->sendError("Unknown type: '" . htmlspecialchars($data['type']) . "'. Valid types: Register, Login, GetAllPlanes, GetAllAirports.", 400);
        }
    }

    private function validateApiKey($data) {
        if (!isset($data['apikey']) || trim($data['apikey']) === '') {
            $this->sendError("Post parameters are missing", 400);
        }

        $apikey = trim($data['apikey']);

        $stmt = $this->db->prepare("SELECT id FROM Users WHERE apikey = ?");
        if (!$stmt) {
            $this->sendError("Database error during API key check: " . $this->db->error, 500);
        }
        $stmt->bind_param("s", $apikey);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            $stmt->close();
            $this->sendError("Invalid API key. Access denied.", 403);
        }
        $stmt->close();
    }

    private function getAllPlanes($data) {
        $allowed_columns = array(
            'id', 'seats', 'description', 'image_url',
            'max_range_km', 'max_cargo_kg', 'max_speed_kmh',
            'model', 'manufacturer', 'classes'
        );

        $fuzzy = isset($data['fuzzy']) ? (bool)$data['fuzzy'] : true;

        // --- return: which columns to include in response ---
        $return_cols = null;
        if (isset($data['return'])) {
            if ($data['return'] === '*') {
                $return_cols = null; // return everything
            } elseif (is_array($data['return'])) {
                foreach ($data['return'] as $col) {
                    if (!in_array($col, $allowed_columns)) {
                        $this->sendError("Invalid return column: '" . htmlspecialchars($col) . "'.", 400);
                    }
                }
                $return_cols = $data['return'];
            }
        }

        // --- limit ---
        $limit = null;
        if (isset($data['limit'])) {
            if (!is_numeric($data['limit']) || (int)$data['limit'] <= 0) {
                $this->sendError("Post parameters are missing", 400);
            }
            $limit = (int)$data['limit'];
        }

        // --- sort ---
        $sort = 'manufacturer';
        if (isset($data['sort']) && trim($data['sort']) !== '') {
            $sort_input = trim($data['sort']);
            if (!in_array($sort_input, $allowed_columns)) {
                $this->sendError("Invalid 'sort' value. Allowed columns: " . implode(', ', $allowed_columns), 400);
            }
            $sort = $sort_input;
        }

        // --- order ---
        $order = 'ASC';
        if (isset($data['order']) && trim($data['order']) !== '') {
            $order_input = strtoupper(trim($data['order']));
            if ($order_input !== 'ASC' && $order_input !== 'DESC') {
                $this->sendError("Invalid 'order' value. Must be 'ASC' or 'DESC'.", 400);
            }
            $order = $order_input;
        }

        // --- search ---
        $search = null;
        if (isset($data['search']) && is_array($data['search'])) {
            $search = $data['search'];
        }

        // --- Build SELECT clause ---
        if ($return_cols !== null) {
            $select = implode(', ', array_map(function($c) { return "`" . $c . "`"; }, $return_cols));
        } else {
            $select = '*';
        }

        $sql    = "SELECT " . $select . " FROM planes";
        $types  = '';
        $params = array();

        if ($search && count($search) > 0) {
            $conditions = array();
            foreach ($search as $col => $val) {
                if ($col === 'seats' && is_array($val)) {
                    // Range search: {"seats": {"min": 100, "max": 300}}
                    if (isset($val['min']) && is_numeric($val['min'])) {
                        $conditions[] = "`seats` >= ?";
                        $params[]     = (int)$val['min'];
                        $types       .= 'i';
                    }
                    if (isset($val['max']) && is_numeric($val['max'])) {
                        $conditions[] = "`seats` <= ?";
                        $params[]     = (int)$val['max'];
                        $types       .= 'i';
                    }
                } elseif ($col === 'max_range_km' && is_array($val)) {
                    if (isset($val['min']) && is_numeric($val['min'])) {
                        $conditions[] = "`max_range_km` >= ?";
                        $params[]     = (int)$val['min'];
                        $types       .= 'i';
                    }
                    if (isset($val['max']) && is_numeric($val['max'])) {
                        $conditions[] = "`max_range_km` <= ?";
                        $params[]     = (int)$val['max'];
                        $types       .= 'i';
                    }
                } elseif ($col === 'max_speed_kmh' && is_array($val)) {
                    if (isset($val['min']) && is_numeric($val['min'])) {
                        $conditions[] = "`max_speed_kmh` >= ?";
                        $params[]     = (int)$val['min'];
                        $types       .= 'i';
                    }
                    if (isset($val['max']) && is_numeric($val['max'])) {
                        $conditions[] = "`max_speed_kmh` <= ?";
                        $params[]     = (int)$val['max'];
                        $types       .= 'i';
                    }
                } else {
                    if (!in_array($col, $allowed_columns)) {
                        $this->sendError("Invalid search column: '" . htmlspecialchars($col) . "'.", 400);
                    }
                    if ($fuzzy) {
                        $conditions[] = "`" . $col . "` LIKE ?";
                        $params[]     = '%' . $val . '%';
                    } else {
                        $conditions[] = "`" . $col . "` = ?";
                        $params[]     = $val;
                    }
                    $types .= 's';
                }
            }
            if (!empty($conditions)) {
                $sql .= " WHERE " . implode(" AND ", $conditions);
            }
        }

        $sql .= " ORDER BY `" . $sort . "` " . $order;

        if ($limit !== null) {
            $sql .= " LIMIT " . $limit;
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->sendError("Database error (planes prepare): " . $this->db->error, 500);
        }

        if (!empty($params)) {
            $bind_args = array($types);
            foreach ($params as $key => $val) {
                $bind_args[] = &$params[$key];
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_args);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            $this->sendError("Database error (planes execute): " . $this->db->error, 500);
        }

        $planes = array();
        while ($row = $result->fetch_assoc()) {
            $planes[] = $row;
        }
        $stmt->close();

        http_response_code(200);
        echo json_encode(array(
            "status"    => "success",
            "timestamp" => (string) round(microtime(true) * 1000),
            "data"      => $planes
        ));
        exit;
    }

    private function getAllAirports($data) {
        $allowed_columns = array(
            'id', 'name', 'city', 'country', 'code', 'longitude', 'latitude'
        );

        $fuzzy = isset($data['fuzzy']) ? (bool)$data['fuzzy'] : true;

        // --- return ---
        $return_cols = null;
        if (isset($data['return'])) {
            if ($data['return'] === '*') {
                $return_cols = null;
            } elseif (is_array($data['return'])) {
                foreach ($data['return'] as $col) {
                    if (!in_array($col, $allowed_columns)) {
                        $this->sendError("Invalid return column: '" . htmlspecialchars($col) . "'.", 400);
                    }
                }
                $return_cols = $data['return'];
            }
        }

        // --- page ---
        $page_size = 30;
        $page = 1;
        if (isset($data['page'])) {
            if (!is_numeric($data['page']) || (int)$data['page'] <= 0) {
                $this->sendError("Invalid 'page' parameter. Must be a positive integer.", 400);
            }
            $page = (int)$data['page'];
        }
        $offset = ($page - 1) * $page_size;

        // --- search ---
        $search = null;
        if (isset($data['search']) && is_array($data['search'])) {
            $search = $data['search'];
        }

        // --- Build SELECT clause ---
        if ($return_cols !== null) {
            $select = implode(', ', array_map(function($c) { return "`" . $c . "`"; }, $return_cols));
        } else {
            $select = '*';
        }

        $sql    = "SELECT " . $select . " FROM airports";
        $types  = '';
        $params = array();

        if ($search && count($search) > 0) {
            $conditions = array();
            foreach ($search as $col => $val) {
                if (!in_array($col, $allowed_columns)) {
                    $this->sendError("Invalid search column: '" . htmlspecialchars($col) . "'.", 400);
                }
                if ($fuzzy) {
                    $conditions[] = "`" . $col . "` LIKE ?";
                    $params[]     = '%' . $val . '%';
                } else {
                    $conditions[] = "`" . $col . "` = ?";
                    $params[]     = $val;
                }
                $types .= 's';
            }
            $sql .= " WHERE " . implode(" AND ", $conditions);
        }

        $sql .= " ORDER BY `name` ASC";
        $sql .= " LIMIT " . $page_size . " OFFSET " . $offset;

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $this->sendError("Database error (airports prepare): " . $this->db->error, 500);
        }

        if (!empty($params)) {
            $bind_args = array($types);
            foreach ($params as $key => $val) {
                $bind_args[] = &$params[$key];
            }
            call_user_func_array(array($stmt, 'bind_param'), $bind_args);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if (!$result) {
            $this->sendError("Database error (airports execute): " . $this->db->error, 500);
        }

        $airports = array();
        while ($row = $result->fetch_assoc()) {
            $airports[] = $row;
        }
        $stmt->close();

        http_response_code(200);
        echo json_encode(array(
            "status"    => "success",
            "timestamp" => (string) round(microtime(true) * 1000),
            "data"      => $airports
        ));
        exit;
    }

    private function registerUser($data) {
        $required_fields = array('name', 'surname', 'email', 'password', 'user_type');
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || trim($data[$field]) === '') {
                $this->sendError("Validation failed. Missing or blank field: " . $field, 400);
            }
        }

        $name     = trim($data['name']);
        $surname  = trim($data['surname']);
        $email    = trim($data['email']);
        $password = $data['password'];
        $type     = trim($data['user_type']);

        if (!preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email)) {
            $this->sendError("Invalid email format. Must contain '@' and a valid domain.", 400);
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{9,}$/', $password)) {
            $this->sendError("Password too weak. Must be more than 8 characters and include: at least one uppercase letter, one lowercase letter, one digit, and one symbol.", 400);
        }

        $allowed_types = array('Passenger', 'ATC');
        if (!in_array($type, $allowed_types)) {
            $this->sendError("Invalid user type. Must be 'Passenger' or 'ATC'.", 400);
        }

        $stmt = $this->db->prepare("SELECT id FROM Users WHERE email = ?");
        if (!$stmt) {
            $this->sendError("Database preparation error: " . $this->db->error, 500);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->close();
            $this->sendError("This email address is already registered. Please log in instead.", 409);
        }
        $stmt->close();

        $salt              = bin2hex(random_bytes(16));
        $hashed_password   = hash('sha256', $salt . $password);
        $stored_credential = $salt . ':' . $hashed_password;
        $apikey            = bin2hex(random_bytes(16));

        $insert_stmt = $this->db->prepare(
            "INSERT INTO Users (name, surname, email, password, type, apikey) VALUES (?, ?, ?, ?, ?, ?)"
        );

        if (!$insert_stmt) {
            $this->sendError("Database preparation error on insert: " . $this->db->error, 500);
        }

        $insert_stmt->bind_param("ssssss", $name, $surname, $email, $stored_credential, $type, $apikey);

        if ($insert_stmt->execute()) {
            $insert_stmt->close();
            http_response_code(200);
            echo json_encode(array(
                "status"    => "success",
                "timestamp" => (string) round(microtime(true) * 1000),
                "data"      => array(
                    "apikey" => $apikey
                )
            ));
            exit;
        } else {
            $insert_stmt->close();
            $this->sendError("Failed to insert user into the database: " . $this->db->error, 500);
        }
    }

    private function loginUser($data) {
        if (!isset($data['email']) || trim($data['email']) === '' ||
            !isset($data['password']) || $data['password'] === '') {
            $this->sendError("Post parameters are missing", 400);
        }

        $email    = trim($data['email']);
        $password = $data['password'];

        $stmt = $this->db->prepare(
            "SELECT id, name, surname, email, apikey, password FROM Users WHERE email = ?"
        );
        if (!$stmt) {
            $this->sendError("Database error: " . $this->db->error, 500);
        }
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            $this->sendError("Invalid email or password.", 401);
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        // Password stored as "salt:hash"
        $parts = explode(':', $user['password']);
        if (count($parts) !== 2) {
            $this->sendError("Account error. Please re-register.", 500);
        }

        list($salt, $stored_hash) = $parts;
        $input_hash = hash('sha256', $salt . $password);

        if (!hash_equals($stored_hash, $input_hash)) {
            $this->sendError("Invalid email or password.", 401);
        }

        http_response_code(200);
        echo json_encode(array(
            "status"    => "success",
            "timestamp" => (string) round(microtime(true) * 1000),
            "data"      => array(
                array(
                    "apikey"  => $user['apikey'],
                    "name"    => $user['name'],
                    "surname" => $user['surname'],
                    "email"   => $user['email']
                )
            )
        ));
        exit;
    }

    private function sendError($message, $http_code) {
        http_response_code($http_code);
        echo json_encode(array(
            "status"    => "error",
            "timestamp" => (string) round(microtime(true) * 1000),
            "data"      => $message
        ));
        exit;
    }
}

$api = FlightAPI::getInstance($conn);
$api->processRequest();
?>