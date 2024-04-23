<?php
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

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
                    ini_set('display_errors', 1);
                    error_reporting(E_ALL);
                    // $search = 'hotel room';
                    // $page = 1;
                    // $per_page = 30;
                    // $orientation = 'squarish';

                    // $pageResult =Unsplash\Search::photos($search, $page, $per_page, $orientation);
                    // $photos = $pageResult->getResults();

                    // if (!empty($photos)) {
                    //     // Iterate through each photo in the results
                    //     foreach ($photos as $photo) {
                    //         $imageUrl = $photo['urls']['regular'];
                    //         echo 'Image URL: ' . $imageUrl . "<br>";
                    //     }
                    // } else {
                    //     echo 'No photos found';
                    // }
                    $search = 'hotel room';
                    $orientation = 'squarish';
                    $totalRooms = 100;
                    $imagesNeeded = $totalRooms;
                    $currentPage = 13;

                    $allImages = [];

                    // Fetch images until we get enough for all rooms
                    while ($imagesNeeded > 0) {
                        $perPage = min(30, $imagesNeeded); // Fetch up to 30 images per call, but not more than we need
                        $pageResult = Unsplash\Search::photos($search, $currentPage, $perPage, $orientation);
                        $photos = $pageResult->getResults();

                        foreach ($photos as $photo) {
                            $allImages[] = $photo['urls']['regular']; // Collect image URLs
                        }

                        $imagesNeeded -= $perPage; // Decrement the counter by the number of images fetched
                        $currentPage++; // Increment to fetch next set of images
                    }

                    // Database connection setup

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
    default:
        http_response_code(405);
        echo json_encode(['error' => "Method $method not supported"]);
        break;
}
?>
