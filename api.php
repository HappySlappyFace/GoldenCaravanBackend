<?php
// Set headers for CORS
header('Access-Control-Allow-Origin: http://localhost:5173'); // Replace with your front-end's origin
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, Accept');
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Required for CORS preflight check
    header("Access-Control-Allow-Origin: http://localhost:5173"); // Adjust this to your front-end's actual origin
    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");
    header("Content-Length: 0");
    header("Content-Type: text/plain");
    http_response_code(200); // You need to send back an HTTP 200 OK status
    exit;
}


require_once 'config.php';
require_once 'authenticate.php';
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$unsplashAccessKey = $_ENV['UNSPLASH_ACCESS_KEY'];
$unsplashSecretKey = $_ENV['UNSPLASH_SECRET_KEY'];
Unsplash\HttpClient::init([
	'applicationId'	=> $unsplashAccessKey,
	'secret'	=> $unsplashSecretKey ,
	'callbackUrl'	=> 'https://your-application.com/oauth/callback',
	'utmSource' => 'Golden Caravan'
]);


// Get the path from the URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$searchText = isset($_GET['search']) ? $_GET['search'] : '';

// Assuming 'api.php' is at the root and followed by the resource type
// in other words, /Web2/Project/
$resource = $segments[3] ?? null;

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        if ($resource) {
            switch ($resource) {
                case 'unsplash':
                    //Unsplash is only called by administrator to fill in the database with images
                    if (authorize_user(['Admin', 'SuperAdmin'])) {
                        $search = 'hotel room';
                        $orientation = 'squarish';
                        $totalRooms = 100;
                        $imagesNeeded = $totalRooms;
                        $currentPage = 13;

                        $allImages = [];

                        // Fetch images until we get enough for all rooms
                        while ($imagesNeeded > 0) {
                            $perPage = min(30, $imagesNeeded); 
                            // $pageResult = Unsplash\Search::photos($search, $currentPage, $perPage, $orientation);
                            // $photos = $pageResult->getResults();

                            // foreach ($photos as $photo) {
                            //     $allImages[] = $photo['urls']['regular'];
                            // }

                            $imagesNeeded -= $perPage;
                            $currentPage++; 
                        }

                        // Update rooms with images
                        // foreach ($allImages as $index => $imageUrl) {
                        //     $roomId = sprintf("02SF%03d", $index + 1); // Generate room ID as you described
                        //     $stmt = $pdo->prepare("UPDATE Rooms SET imageUrl = :imageUrl WHERE idRoom = :idRoom");
                        //     $stmt->execute([':imageUrl' => $imageUrl, ':idRoom' => $roomId]);
                        // }

                        echo "Rooms updated successfully with new image URLs.";
                        break;
                    } else {
                        http_response_code(403); // Forbidden
                        echo json_encode(array('status' => 'fail', 'message' => 'Not authorized.'));
                        exit;
                    }
                    
                    
                case 'users':
                    // Fetch all users
                    $stmt = $pdo->query('SELECT * FROM users');
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($users);
                    break;
                case 'Room':
                    // Fetch room data
                    $room = $_GET['room'] ?? '';
                    $stmt = $pdo->prepare('SELECT * FROM Rooms r WHERE r.idRoom = :room');
                    $stmt->bindParam(':room', $room, PDO::PARAM_STR); 
                    $stmt->execute(); // You need to call execute() for prepared statements
                    $roomDetails = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($roomDetails);
                    break;
                    
                case 'Rooms':
                    $city = $_GET['city'] ?? '';
                    $checkInDate = $_GET['checkInDate'] ?? '';
                    $checkOutDate = $_GET['checkOutDate'] ?? '';
                    $guests = $_GET['guests'] ?? 0;
                    $rooms = $_GET['rooms'] ?? 0;
                    // Fetch hotel data
                    //echo $city." ".$checkInDate." ".$checkOutDate." ".$guests." ".$rooms;


                    $sql = "SELECT r.*
                        FROM Rooms r
                        JOIN Hotels h ON r.idHotel = h.idHotel
                        WHERE h.city = :city
                        AND r.numberOfBeds>= :rooms
                        AND r.idRoom NOT IN (
                            SELECT b.idRoom
                            FROM Booking b
                            WHERE b.startDate < :checkOutDate AND b.endDate > :checkInDate
                        )
                        GROUP BY r.idRoom";

                    $sql2="SELECT r.*
                    FROM Rooms r
                    JOIN Hotels h ON r.idHotel = h.idHotel
                    WHERE h.city = :city
                    ";
                    try {
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(':city', $city, PDO::PARAM_STR);
                        $stmt->bindParam(':checkInDate', $checkInDate);
                        $stmt->bindParam(':checkOutDate', $checkOutDate);
                        $stmt->bindParam(':rooms', $rooms, PDO::PARAM_INT); 
                        $stmt->execute();
                    
                        $availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        echo json_encode($availableRooms);
                    
                    } catch (PDOException $e) {
                        echo 'Connection failed: ' . $e->getMessage();
                    }
                    break;
                case 'Hotels':
                    // Fetch room data
                    $stmt = $pdo->prepare('SELECT * FROM Hotels WHERE name LIKE :searchText OR city LIKE :searchText');
                    $searchTerm = '%' . $searchText . '%';
                    $stmt->bindParam(':searchText', $searchTerm, PDO::PARAM_STR);

                    // Execute the statement and fetch the results
                    $stmt->execute();
                    $hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($hotels);
                    break;
                default:
                    http_response_code(404);
                    echo json_encode(['error' => 'Resource not found']);
                    break;
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'No resource specified']);
        }
        break;
    case 'POST':
        // Authenticate the user and create a session for a successful login
        
        if ($resource === 'login') {
            $json = file_get_contents('php://input');
            $data = json_decode($json);
            
            // Perform validation and sanitation on $data here
            
            if (isset($data->email) && isset($data->password)) {
                $user = authenticate_user($pdo, $data->email, $data->password);
                if ($user) {
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    $_SESSION['user_id'] = $user['idUser'];
                    $_SESSION['user_role'] = $user['userType'];
                    $_SESSION['logged_in'] = true;

                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }
                    
                    // Log session data to error log for debugging
                    // error_log("Session Status: " . session_status());
                    // error_log("Session ID: " . session_id());
                    // error_log("User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'Not set'));
                    // error_log("User Role: " . (isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'Not set'));
                    // error_log("Logged In: " . (isset($_SESSION['logged_in']) ? 'Yes' : 'No'));
                    
                    // Send a success response
                    echo json_encode(array('status' => 'success', 'message' => 'Logged in successfully.'));
                } else {
                    http_response_code(401); // Unauthorized
                    echo json_encode(array('status' => 'fail', 'message' => 'Invalid credentials.'));
                }
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(array('status' => 'fail', 'message' => 'Email and password are required.'));
            }
        }
        if ($resource === 'getUserRole') {
            session_start();
            if (!isset($_SESSION['user_id'])) {
                http_response_code(401); // Unauthorized
                echo json_encode(array('error' => 'User is not logged in.'));
                // Close the session
                exit;
            }
            session_write_close(); 
            $user_id = $_SESSION['user_id'];

            // Perform your database query to get user information, including userType
            // Replace this with your actual database query
            // Example: SELECT * FROM Users WHERE id = :user_id
            // Remember to use prepared statements to prevent SQL injection
            $userType=getUserRoles();
            
            // Example user information (replace with your actual database query result)
            $userInformation = array(
                'userType' => $userType // Replace 'Admin' with the actual userType fetched from the database
                // Add other user information here
            );

            // Return user information as JSON response
            echo json_encode($userInformation);


        }
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => "Method $method not supported"]);
        break;
}

?>
