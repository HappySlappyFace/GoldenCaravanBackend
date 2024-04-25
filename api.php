<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);


ini_set('display_errors', 1);
error_reporting(E_ALL);


require_once 'config.php';
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
// Set the content type to JSON
// Setup header for JSON response
header('Content-Type: application/json');


// Get the path from the URL
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$segments = explode('/', trim($path, '/'));
$searchText = isset($_GET['search']) ? $_GET['search'] : '';

// Assuming 'api.php' is at the root and followed by the resource type
$resource = $segments[3] ?? null;

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case 'GET':
        if ($resource) {
            // echo $resource;
            switch ($resource) {
                case 'unsplash':

                    $search = 'hotel room';
                    $orientation = 'squarish';
                    $totalRooms = 100;
                    $imagesNeeded = $totalRooms;
                    $currentPage = 13;

                    $allImages = [];

                    // Fetch images until we get enough for all rooms
                    while ($imagesNeeded > 0) {
                        $perPage = min(30, $imagesNeeded); 
                        $pageResult = Unsplash\Search::photos($search, $currentPage, $perPage, $orientation);
                        $photos = $pageResult->getResults();

                        foreach ($photos as $photo) {
                            $allImages[] = $photo['urls']['regular'];
                        }

                        $imagesNeeded -= $perPage;
                        $currentPage++; 
                    }

                    // Update rooms with images
                    foreach ($allImages as $index => $imageUrl) {
                        $roomId = sprintf("02SF%03d", $index + 1); // Generate room ID as you described

                        // Prepare SQL statement to update room
                        $stmt = $pdo->prepare("UPDATE Rooms SET imageUrl = :imageUrl WHERE idRoom = :idRoom");
                        $stmt->execute([':imageUrl' => $imageUrl, ':idRoom' => $roomId]);
                    }

                    echo "Rooms updated successfully with new image URLs.";
                    break;
                case 'users':
                    // Fetch user data
                    $stmt = $pdo->query('SELECT * FROM users');
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode($users);
                    break;
                case 'Room':
                    // Fetch user data
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
                // Use a method to authenticate the user
                $user = authenticate_user($data->email, $data->password);
                
                if ($user) {
                    // Set session variables for the authenticated user
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['logged_in'] = true;
                    
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
        // Additional POST logic for other resources here if necessary
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => "Method $method not supported"]);
        break;
}

function authenticate_user($email, $password) {
    // Your authentication logic to check the credentials against the database goes here.
    // For simplicity, pseudocode is provided below:
    /*
    $pdo = new PDO('mysql:host=your_host;dbname=your_db', 'user', 'password');
    $statement = $pdo->prepare("SELECT * FROM users WHERE email = :email AND password = :password");
    $statement->execute(array(":email" => $email, ":password" => $password));
    $user = $statement->fetch(PDO::FETCH_ASSOC);
    return $user ? $user : false;
    */
    
    // Placeholder return for demonstration:
    return $email === 'test@example.com' && $password === 'password' ? ['id' => 1] : false;
}
?>
