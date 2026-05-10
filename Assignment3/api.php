<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

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
            case 'AddFavourite':
                $this->addFavourite($data);
                break;
            case 'RemoveFavourite':
                $this->removeFavourite($data);
                break;
            case 'GetFavourites':
                $this->getFavourites($data);
                break;
            case 'BookFlight':
                $this->bookFlight($data);
                break;
            case 'GetBookings':
                $this->getBookings($data);
                break;
            case 'CancelBooking':
                $this->cancelBooking($data);
                break;
            default:
                $this->sendError("Unknown type: '" . htmlspecialchars($data['type']) . "'. Valid types: Register, Login, GetAllPlanes, GetAllAirports, AddFavourite, RemoveFavourite, GetFavourites, BookFlight, GetBookings, CancelBooking.", 400);
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

    // ── Haversine distance formula ─────────────────────────────────────────────
    private function calculateDistance($lat1, $lon1, $lat2, $lon2) {
        $R      = 6377;
        $phi1   = deg2rad((float)$lat1);
        $phi2   = deg2rad((float)$lat2);
        $dphi   = deg2rad((float)$lat2 - (float)$lat1);
        $dlambda= deg2rad((float)$lon2 - (float)$lon1);
        $hav    = pow(sin($dphi / 2), 2) + cos($phi1) * cos($phi2) * pow(sin($dlambda / 2), 2);
        $theta  = 2 * asin(sqrt($hav));
        return round($R * $theta, 2);
    }

    // ── Flight time formula ────────────────────────────────────────────────────
    private function calculateFlightTime($distance, $vmax, $cmax, $seats) {
        $vc = $vmax * (1 - 0.2 * ($cmax / ($cmax + 80 * $seats)));

        if ($seats > 300)      $tclimb_base = 20;
        elseif ($seats > 200)  $tclimb_base = 15;
        elseif ($seats > 100)  $tclimb_base = 10;
        elseif ($seats > 50)   $tclimb_base = 7;
        else                   $tclimb_base = 5;

        $k      = 0.001; // rate constant (not specified in spec)
        $tclimb = $tclimb_base * (1 - exp(-$k * $distance));

        // ttotal in minutes: (d/vc)*60 + tclimb + 15
        return round(($distance / $vc) * 60 + $tclimb + 15, 2);
    }

    // ── DB helpers ─────────────────────────────────────────────────────────────
    private function getPlaneById($plane_id) {
        $stmt = $this->db->prepare("SELECT * FROM planes WHERE id = ?");
        $stmt->bind_param("i", $plane_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    private function getAirportByCode($code) {
        $stmt = $this->db->prepare("SELECT * FROM airports WHERE code = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }

    private function getOrCreateFlight($plane_id, $dep_code, $arr_code, $date, $flight_time, $distance) {
        $stmt = $this->db->prepare(
            "SELECT id FROM flights
             WHERE plane_id = ? AND departure_airport_code = ? AND arrival_airport_code = ? AND departure_date = ?"
        );
        $stmt->bind_param("isss", $plane_id, $dep_code, $arr_code, $date);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $id  = (int) $row['id'];
            $stmt->close();
            return $id;
        }
        $stmt->close();

        $ins = $this->db->prepare(
            "INSERT INTO flights (plane_id, departure_airport_code, arrival_airport_code, departure_date, flight_time, distance)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $ins->bind_param("isssdd", $plane_id, $dep_code, $arr_code, $date, $flight_time, $distance);
        $ins->execute();
        $id = (int) $this->db->insert_id;
        $ins->close();
        return $id;
    }

    private function checkSeatAvailability($flight_id, $plane_seats, $requested) {
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(passengers), 0) AS total FROM bookings WHERE flight_id = ?"
        );
        $stmt->bind_param("i", $flight_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $booked = (int) $row['total'];
        $stmt->close();
        $available = $plane_seats - $booked;
        if ($requested > $available) {
            $this->sendError(
                "Not enough seats. Requested: $requested, Available: $available. " .
                "Please choose a different date or plane.", 409
            );
        }
    }

    private function createBookingRecord($flight_id, $user_id, $passengers) {
        $stmt = $this->db->prepare(
            "INSERT INTO bookings (flight_id, user_id, passengers) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iii", $flight_id, $user_id, $passengers);
        if (!$stmt->execute()) {
            $stmt->close();
            $this->sendError("Failed to create booking.", 500);
        }
        $id = (int) $this->db->insert_id;
        $stmt->close();
        return $id;
    }

    // ── BookFlight ─────────────────────────────────────────────────────────────
    private function bookFlight($data) {
        $required = array('plane_id', 'departure_airport_code', 'arrival_airport_code', 'departure_date', 'passengers');
        foreach ($required as $field) {
            if (!isset($data[$field]) || trim((string)$data[$field]) === '') {
                $this->sendError("Post parameters are missing", 400);
            }
        }

        $user_id    = $this->getUserIdFromApiKey(isset($data['apikey']) ? $data['apikey'] : '');
        $plane_id   = (int) $data['plane_id'];
        $dep_code   = strtoupper(trim($data['departure_airport_code']));
        $arr_code   = strtoupper(trim($data['arrival_airport_code']));
        $dep_date   = trim($data['departure_date']);
        $passengers = (int) $data['passengers'];
        $ret_date   = isset($data['return_date']) && trim($data['return_date']) !== ''
                    ? trim($data['return_date']) : null;

        if ($passengers < 1) {
            $this->sendError("Passengers must be at least 1.", 400);
        }
        if ($dep_code === $arr_code) {
            $this->sendError("Departure and arrival airports cannot be the same.", 400);
        }

        $plane = $this->getPlaneById($plane_id);
        if (!$plane) $this->sendError("Plane not found.", 404);

        $dep = $this->getAirportByCode($dep_code);
        $arr = $this->getAirportByCode($arr_code);
        if (!$dep) $this->sendError("Departure airport not found: $dep_code", 404);
        if (!$arr) $this->sendError("Arrival airport not found: $arr_code", 404);

        $distance    = $this->calculateDistance(
            $dep['latitude'], $dep['longitude'],
            $arr['latitude'], $arr['longitude']
        );
        $flight_time = $this->calculateFlightTime(
            $distance, $plane['max_speed_kmh'], $plane['max_cargo_kg'], $plane['seats']
        );

        // Outbound flight
        $out_flight_id  = $this->getOrCreateFlight($plane_id, $dep_code, $arr_code, $dep_date, $flight_time, $distance);
        $this->checkSeatAvailability($out_flight_id, $plane['seats'], $passengers);
        $out_booking_id = $this->createBookingRecord($out_flight_id, $user_id, $passengers);

        $result = array(
            'outbound_booking_id' => $out_booking_id,
            'outbound_flight_id'  => $out_flight_id,
            'distance_km'         => $distance,
            'flight_time_min'     => $flight_time
        );

        // Return flight (swap airports)
        if ($ret_date) {
            $ret_flight_id  = $this->getOrCreateFlight($plane_id, $arr_code, $dep_code, $ret_date, $flight_time, $distance);
            $this->checkSeatAvailability($ret_flight_id, $plane['seats'], $passengers);
            $ret_booking_id = $this->createBookingRecord($ret_flight_id, $user_id, $passengers);
            $result['return_booking_id'] = $ret_booking_id;
            $result['return_flight_id']  = $ret_flight_id;
        }

        http_response_code(200);
        echo json_encode(array(
            "status"    => "success",
            "timestamp" => (string) round(microtime(true) * 1000),
            "data"      => array($result)
        ));
        exit;
    }

    // ── GetBookings ────────────────────────────────────────────────────────────
    private function getBookings($data) {
        $user_id = $this->getUserIdFromApiKey(isset($data['apikey']) ? $data['apikey'] : '');

        $stmt = $this->db->prepare(
            "SELECT b.id AS booking_id, b.passengers,
                    f.id AS flight_id, f.departure_date, f.flight_time, f.distance,
                    f.departure_airport_code, f.arrival_airport_code,
                    p.id AS plane_id, p.manufacturer, p.model, p.image_url, p.seats,
                    da.name AS departure_airport_name, da.city AS departure_city,
                    aa.name AS arrival_airport_name, aa.city AS arrival_city
             FROM bookings b
             INNER JOIN flights f ON f.id = b.flight_id
             INNER JOIN planes  p ON p.id = f.plane_id
             LEFT JOIN  airports da ON da.code = CONVERT(f.departure_airport_code USING utf8mb4) COLLATE utf8mb4_unicode_ci
             LEFT JOIN  airports aa ON aa.code = CONVERT(f.arrival_airport_code USING utf8mb4) COLLATE utf8mb4_unicode_ci
             WHERE b.user_id = ?
             ORDER BY f.departure_date ASC"
        );
        if (!$stmt) {
            $this->sendError("Database prepare error (GetBookings): " . $this->db->error, 500);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result   = $stmt->get_result();
        $bookings = array();
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        $stmt->close();

        http_response_code(200);
        echo json_encode(array(
            "status"    => "success",
            "timestamp" => (string) round(microtime(true) * 1000),
            "data"      => $bookings
        ));
        exit;
    }

    // ── CancelBooking ──────────────────────────────────────────────────────────
    private function cancelBooking($data) {
        if (!isset($data['booking_id']) || !is_numeric($data['booking_id'])) {
            $this->sendError("Post parameters are missing", 400);
        }
        $user_id    = $this->getUserIdFromApiKey(isset($data['apikey']) ? $data['apikey'] : '');
        $booking_id = (int) $data['booking_id'];

        $check = $this->db->prepare("SELECT id FROM bookings WHERE id = ? AND user_id = ?");
        $check->bind_param("ii", $booking_id, $user_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows === 0) {
            $check->close();
            $this->sendError("Booking not found or access denied.", 404);
        }
        $check->close();

        $stmt = $this->db->prepare("DELETE FROM bookings WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $booking_id, $user_id);
        if ($stmt->execute()) {
            $stmt->close();
            http_response_code(200);
            echo json_encode(array(
                "status"    => "success",
                "timestamp" => (string) round(microtime(true) * 1000),
                "data"      => array(array("message" => "Booking cancelled successfully."))
            ));
            exit;
        }
        $stmt->close();
        $this->sendError("Failed to cancel booking.", 500);
    }

    private function getUserIdFromApiKey($apikey) {
        if (!isset($apikey) || trim($apikey) === '') {
            $this->sendError("Post parameters are missing", 400);
        }
        $apikey = trim($apikey);
        $stmt   = $this->db->prepare("SELECT id FROM Users WHERE apikey = ?");
        if (!$stmt) {
            $this->sendError("Database error: " . $this->db->error, 500);
        }
        $stmt->bind_param("s", $apikey);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            $stmt->close();
            $this->sendError("Invalid API key. Access denied.", 403);
        }
        $row = $result->fetch_assoc();
        $stmt->close();
        return (int) $row['id'];
    }

    private function addFavourite($data) {
        if (!isset($data['plane_id']) || !is_numeric($data['plane_id'])) {
            $this->sendError("Post parameters are missing", 400);
        }
        $user_id  = $this->getUserIdFromApiKey(isset($data['apikey']) ? $data['apikey'] : '');
        $plane_id = (int) $data['plane_id'];

        $check = $this->db->prepare("SELECT id FROM favourites WHERE user_id = ? AND plane_id = ?");
        $check->bind_param("ii", $user_id, $plane_id);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $check->close();
            $this->sendError("Plane is already in favourites.", 409);
        }
        $check->close();

        $stmt = $this->db->prepare("INSERT INTO favourites (user_id, plane_id) VALUES (?, ?)");
        if (!$stmt) {
            $this->sendError("Database error: " . $this->db->error, 500);
        }
        $stmt->bind_param("ii", $user_id, $plane_id);
        if ($stmt->execute()) {
            $stmt->close();
            http_response_code(200);
            echo json_encode(array(
                "status"    => "success",
                "timestamp" => (string) round(microtime(true) * 1000),
                "data"      => array(array("message" => "Plane added to favourites."))
            ));
            exit;
        }
        $stmt->close();
        $this->sendError("Failed to add to favourites.", 500);
    }

    private function removeFavourite($data) {
        if (!isset($data['plane_id']) || !is_numeric($data['plane_id'])) {
            $this->sendError("Post parameters are missing", 400);
        }
        $user_id  = $this->getUserIdFromApiKey(isset($data['apikey']) ? $data['apikey'] : '');
        $plane_id = (int) $data['plane_id'];

        $stmt = $this->db->prepare("DELETE FROM favourites WHERE user_id = ? AND plane_id = ?");
        if (!$stmt) {
            $this->sendError("Database error: " . $this->db->error, 500);
        }
        $stmt->bind_param("ii", $user_id, $plane_id);
        if ($stmt->execute()) {
            $stmt->close();
            http_response_code(200);
            echo json_encode(array(
                "status"    => "success",
                "timestamp" => (string) round(microtime(true) * 1000),
                "data"      => array(array("message" => "Plane removed from favourites."))
            ));
            exit;
        }
        $stmt->close();
        $this->sendError("Failed to remove from favourites.", 500);
    }

    private function getFavourites($data) {
        $user_id = $this->getUserIdFromApiKey(isset($data['apikey']) ? $data['apikey'] : '');

        $stmt = $this->db->prepare(
            "SELECT p.* FROM planes p
            INNER JOIN favourites f ON f.plane_id = p.id
            WHERE f.user_id = ?
            ORDER BY p.manufacturer ASC"
        );
        if (!$stmt) {
            $this->sendError("Database error: " . $this->db->error, 500);
        }
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

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