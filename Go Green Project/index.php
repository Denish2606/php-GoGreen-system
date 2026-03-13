<?php
 

if (session_status() === PHP_SESSION_NONE) session_start();

 
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "apu_sustainable_transport";

 
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $mysqli->set_charset("utf8mb4");
    
     
    $mysqli->query("CREATE TABLE IF NOT EXISTS content_posts (
        post_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $mysqli->query("CREATE TABLE IF NOT EXISTS challenges (
        challenge_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $mysqli->query("CREATE TABLE IF NOT EXISTS reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        ride_id INT NOT NULL,
        reviewer_id INT NOT NULL,
        driver_partner_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

     
    $mysqli->query("CREATE TABLE IF NOT EXISTS transactions (
        transaction_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        type ENUM('Credit', 'Debit') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

     
    try { $mysqli->query("ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) AFTER email"); } catch (Exception $e) { } 
    try { $mysqli->query("ALTER TABLE community_partners ADD COLUMN vehicle_model VARCHAR(100) AFTER is_verified"); } catch (Exception $e) { }
    try { $mysqli->query("ALTER TABLE community_partners ADD COLUMN license_plate VARCHAR(20) AFTER vehicle_model"); } catch (Exception $e) { }
    try { $mysqli->query("ALTER TABLE community_partners ADD COLUMN capacity INT DEFAULT 4 AFTER license_plate"); } catch (Exception $e) { }
    
     
    try { $mysqli->query("ALTER TABLE users ADD COLUMN wallet_balance DECIMAL(10,2) DEFAULT 0.00 AFTER role"); } catch (Exception $e) { }
    try { $mysqli->query("ALTER TABLE rides ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00 AFTER status"); } catch (Exception $e) { }

} catch (mysqli_sql_exception $e) {
    die("Database connection failed: " . $e->getMessage() . ". Please create the DB: " . $DB_NAME);
}

 
function is_logged_in(){ return isset($_SESSION['user_id']); }
function require_login(){ if(!is_logged_in()){ header("Location: index.php"); exit; } }
function current_user_id(){ return $_SESSION['user_id'] ?? null; }
function current_user_role(){ return $_SESSION['role'] ?? null; }
function current_user_name(){ return $_SESSION['full_name'] ?? null; }

function flash($k, $v=null){
    if($v===null){ $v = $_SESSION['flash'][$k] ?? null; unset($_SESSION['flash'][$k]); return $v; }
    $_SESSION['flash'][$k] = $v;
}

 
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if($action === 'seed') {
    $pw = password_hash('password123', PASSWORD_DEFAULT);
    
     
    
    $r = $mysqli->query("SELECT user_id FROM users LIMIT 1");
    if($r->fetch_assoc()){
        flash('error','Database already has users; seed skipped.');
    } else {
         
        $stmt = $mysqli->prepare("INSERT INTO users (full_name,email,phone_number,password_hash,role, wallet_balance) VALUES (?,?,?,?,?, 50.00)");
        $n='Ramesh Kumar'; $e='ramesh@apu.edu.my'; $ph='012-3456789'; $rhash=$pw; $role='Student';
        $stmt->bind_param("sssss",$n,$e,$ph,$rhash,$role); $stmt->execute(); $uid1 = $stmt->insert_id; $stmt->close();
        $mysqli->query("INSERT INTO customers (user_id, apu_id, total_points) VALUES ($uid1,'TP083466',1250)");
        
         
        $stmt = $mysqli->prepare("INSERT INTO users (full_name,email,phone_number,password_hash,role, wallet_balance) VALUES (?,?,?,?,?, 10.00)");
        $n='Aliya Binti Ahmad'; $e='aliya@apu.edu.my'; $ph='019-8765432'; $role='Driver';
        $stmt->bind_param("sssss",$n,$e,$ph,$rhash,$role); $stmt->execute(); $uid2 = $stmt->insert_id; $stmt->close();
        $stmt = $mysqli->prepare("INSERT INTO community_partners (user_id, full_name, is_verified, vehicle_model, license_plate, capacity) VALUES (?,?,1, 'Perodua Myvi', 'VAA 1234', 4)");
        $stmt->bind_param("is",$uid2,$n); $stmt->execute(); $stmt->close();
        
         
        $stmt = $mysqli->prepare("INSERT INTO users (full_name,email,phone_number,password_hash,role) VALUES (?,?,?,?,?)");
        $n='Admin User'; $e='admin@apu.edu.my'; $ph='010-0000000'; $role='Admin';
        $stmt->bind_param("sssss",$n,$e,$ph,$rhash,$role); $stmt->execute(); $uid3 = $stmt->insert_id; $stmt->close();
        $mysqli->query("INSERT INTO administrators (user_id, department) VALUES ($uid3,'IT')");
        
         
        $mysqli->query("INSERT INTO rewards (title, points_cost, stock) VALUES ('Cafe Voucher RM5',200,10),('Free Bus Pass',500,5)");
        flash('success','Seed completed: Admin created (admin@apu.edu.my / password123)');
    }
    header("Location: index.php");
    exit;
}

if($action === 'register'){
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'Student';

     
    if ($role === 'Admin') {
        $role = 'Student';
    }

    if(!$full_name || !$email || !$password){
        flash('error','Please fill all required fields.');
    } else {
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s",$email); $stmt->execute(); $stmt->store_result();
        if($stmt->num_rows>0){
            flash('error','Email already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("INSERT INTO users (full_name,email,password_hash,role, wallet_balance) VALUES (?,?,?,?, 0.00)");
            $stmt->bind_param("ssss",$full_name,$email,$hash,$role);
            if($stmt->execute()){
                $uid = $stmt->insert_id;
                if(strtolower($role)==='student'){
                    $stmt2 = $mysqli->prepare("INSERT INTO customers (user_id, apu_id, total_points) VALUES (?, ?, 0)");
                    $apu_id = null; $stmt2->bind_param("is",$uid,$apu_id); $stmt2->execute(); $stmt2->close();
                } elseif(strtolower($role)==='driver'){
                    $stmt2 = $mysqli->prepare("INSERT INTO community_partners (user_id, full_name, is_verified) VALUES (?, ?, 0)");
                    $stmt2->bind_param("is",$uid,$full_name); $stmt2->execute(); $stmt2->close();
                } 
                flash('success','Registration successful. You may now log in.');
            } else {
                flash('error','Registration failed.');
            }
            $stmt->close();
        }
    }
    header("Location: index.php?view=login");
    exit;
}

if($action === 'login'){
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if(!$email || !$password){
        flash('error','Enter email and password.');
        header("Location: index.php?view=login"); exit;
    }
    $stmt = $mysqli->prepare("SELECT user_id, full_name, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s",$email); $stmt->execute(); $stmt->store_result();
    if($stmt->num_rows!==1){
        flash('error','Invalid credentials.');
        $stmt->close();
        header("Location: index.php?view=login"); exit;
    }
    $stmt->bind_result($uid,$full_name,$hash,$role); $stmt->fetch(); $stmt->close();
    if(password_verify($password,$hash)){
        $_SESSION['user_id'] = $uid;
        $_SESSION['role'] = $role;
        $_SESSION['full_name'] = $full_name;
        flash('success','Welcome back, '.$full_name.'!');
        header("Location: index.php");
        exit;
    } else {
        flash('error','Invalid credentials.');
        header("Location: index.php?view=login"); exit;
    }
}

if(isset($_GET['logout'])){
    session_unset(); session_destroy();
    header("Location: index.php");
    exit;
}

if($action === 'update_profile'){
    require_login();
    $uid = current_user_id();
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if(!$full_name || !$email){ flash('error','Name and Email required.'); header("Location: index.php"); exit; }

    $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
    $stmt->bind_param("si", $email, $uid); $stmt->execute();
    if($stmt->get_result()->num_rows > 0){ flash('error','Email taken.'); header("Location: index.php"); exit; }
    $stmt->close();

    $query = "UPDATE users SET full_name = ?, email = ?, phone_number = ?";
    $params = [$full_name, $email, $phone];
    $types = "sss";

    if(!empty($password)){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query .= ", password_hash = ?";
        $params[] = $hash;
        $types .= "s";
    }
    $query .= " WHERE user_id = ?";
    $params[] = $uid; $types .= "i";

    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    if($stmt->execute()){
        $_SESSION['full_name'] = $full_name;
        if(current_user_role() === 'Driver'){
            $stmt2 = $mysqli->prepare("UPDATE community_partners SET full_name = ? WHERE user_id = ?");
            $stmt2->bind_param("si", $full_name, $uid); $stmt2->execute(); $stmt2->close();
        }
        flash('success', 'Profile updated.');
    } else {
        flash('error', 'Update failed.');
    }
    $stmt->close();
    header("Location: index.php");
    exit;
}

if($action === 'log_trip'){
    if(!is_logged_in()){ flash('error','Login required'); header("Location: index.php"); exit; }
    $uid = current_user_id();
    $transport = $_POST['transport_type'] ?? '';
    $distance = floatval($_POST['distance_km'] ?? 0);
    $log_date = $_POST['log_date'] ?? date('Y-m-d');

    if(!$transport || $distance <= 0){ flash('error','Invalid trip data.'); header("Location: index.php"); exit; }

    $co2_saved = round($distance * 0.2,2);
    $cost_saved = 0;
    if($transport === 'Public Bus' || $transport === 'Carpool') $cost_saved = round($distance * 0.2,2);

    $stmt = $mysqli->prepare("INSERT INTO trip_logs (user_id, transport_type, distance_km, co2_saved_kg, cost_saved_rm, log_date) VALUES (?,?,?,?,?,?)");
    $stmt->bind_param("isddds", $uid, $transport, $distance, $co2_saved, $cost_saved, $log_date);
    if($stmt->execute()){
        $points = max(1,intval(round($distance * 2)));
        $stmt2 = $mysqli->prepare("UPDATE customers SET total_points = total_points + ? WHERE user_id = ?");
        $stmt2->bind_param("ii",$points,$uid); $stmt2->execute(); $stmt2->close();
        $stmt3 = $mysqli->prepare("INSERT INTO points_history (user_id, change_value, reason) VALUES (?, ?, ?)");
        $reason = "Trip: $transport $distance km";
        $stmt3->bind_param("iis",$uid,$points,$reason); $stmt3->execute(); $stmt3->close();
        flash('success','Trip logged. Points: +'.$points);
    } else { flash('error','Failed to log trip.'); }
    $stmt->close();
    header("Location: index.php");
    exit;
}

if($action === 'create_ride'){
    if(!is_logged_in() || current_user_role()!=='Driver'){ flash('error','Driver only'); header("Location: index.php"); exit; }
    $uid = current_user_id();
    $origin = trim($_POST['origin'] ?? '');
    $destination = trim($_POST['destination'] ?? '');
    $departure = $_POST['departure_time'] ?? null;
    $price = floatval($_POST['price'] ?? 0);

    if(!$origin || !$destination || !$departure){ flash('error','Fill ride details'); header("Location: index.php"); exit; }

    $stmt = $mysqli->prepare("SELECT partner_id FROM community_partners WHERE user_id = ?");
    $stmt->bind_param("i",$uid); $stmt->execute(); $res = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $pid = $res['partner_id'] ?? null;
    if(!$pid){ flash('error','Driver profile not found'); header("Location: index.php"); exit; }

    $stmt = $mysqli->prepare("INSERT INTO rides (driver_partner_id, origin, destination, departure_time, is_recurring, status, price) VALUES (?, ?, ?, ?, 0, 'Scheduled', ?)");
    $stmt->bind_param("isssd",$pid,$origin,$destination,$departure,$price);
    if($stmt->execute()){ flash('success','Ride created.'); } else { flash('error','Create ride failed'); }
    $stmt->close();
    header("Location: index.php");
    exit;
}

if($action === 'book_ride'){
    if(!is_logged_in()){ flash('error','Login required'); header("Location: index.php"); exit; }
    $uid = current_user_id();
    $ride_id = intval($_POST['ride_id'] ?? 0);
    if(!$ride_id){ flash('error','Invalid ride'); header("Location: index.php"); exit; }
    
    $stmt = $mysqli->prepare("
        SELECT r.price, u.wallet_balance, cp.capacity,
        (SELECT COUNT(*) FROM ride_bookings rb WHERE rb.ride_id = r.ride_id AND rb.status != 'Cancelled') as current_bookings
        FROM rides r 
        JOIN users u ON u.user_id = ? 
        JOIN community_partners cp ON cp.partner_id = r.driver_partner_id
        WHERE r.ride_id = ?
    ");
    $stmt->bind_param("ii", $uid, $ride_id); $stmt->execute(); $res = $stmt->get_result()->fetch_assoc();
    
    if(!$res) { 
        flash('error', 'Ride details not found.'); 
    } elseif ($res['current_bookings'] >= $res['capacity']) {
        flash('error', 'Booking failed: This vehicle is at maximum capacity.');
    } elseif ($res['wallet_balance'] < $res['price']) {
        flash('error', 'Insufficient funds. Please top up your wallet.');
    } else {
        $stmt = $mysqli->prepare("INSERT INTO ride_bookings (ride_id, passenger_user_id, status) VALUES (?, ?, 'Pending')");
        $stmt->bind_param("ii",$ride_id,$uid);
        if($stmt->execute()){ flash('success','Booking confirmed! Seat reserved.'); } else { flash('error','Booking failed.'); }
        $stmt->close();
    }
    header("Location: index.php");
    exit;
}

if($action === 'claim_reward'){
    if(!is_logged_in()){ flash('error','Login required'); header("Location: index.php"); exit; }
    $uid = current_user_id();
    $reward_id = intval($_POST['reward_id'] ?? 0);
    if(!$reward_id){ flash('error','Invalid reward'); header("Location: index.php"); exit; }

    $stmt = $mysqli->prepare("SELECT points_cost, stock FROM rewards WHERE reward_id = ?");
    $stmt->bind_param("i",$reward_id); $stmt->execute(); $rw = $stmt->get_result()->fetch_assoc(); $stmt->close();
    if(!$rw){ flash('error','Reward not found'); header("Location: index.php"); exit; }

    $points_needed = (int)$rw['points_cost'];
    $stock = (int)$rw['stock'];
    $stmt = $mysqli->prepare("SELECT total_points FROM customers WHERE user_id = ?");
    $stmt->bind_param("i",$uid); $stmt->execute(); $cus = $stmt->get_result()->fetch_assoc(); $stmt->close();
    $user_points = $cus['total_points'] ?? 0;

    if($user_points < $points_needed){ flash('error','Not enough points'); header("Location: index.php"); exit; }
    if($stock <= 0){ flash('error','Out of stock'); header("Location: index.php"); exit; }

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("UPDATE customers SET total_points = total_points - ? WHERE user_id = ?");
        $stmt->bind_param("ii",$points_needed,$uid); $stmt->execute(); $stmt->close();
        $stmt = $mysqli->prepare("INSERT INTO reward_claims (user_id, reward_id) VALUES (?, ?)");
        $stmt->bind_param("ii",$uid,$reward_id); $stmt->execute(); $stmt->close();
        $stmt = $mysqli->prepare("UPDATE rewards SET stock = stock - 1 WHERE reward_id = ?");
        $stmt->bind_param("i",$reward_id); $stmt->execute(); $stmt->close();
        $mysqli->commit();
        flash('success','Reward claimed!');
    } catch(Exception $e){
        $mysqli->rollback();
        flash('error','Claim failed.');
    }
    header("Location: index.php");
    exit;
}

if($action === 'send_message'){
    if(!is_logged_in()){ flash('error','Login required'); header("Location: index.php"); exit; }
    $sender = current_user_id();
    $receiver = intval($_POST['to_user'] ?? 0);
    $text = trim($_POST['text'] ?? '');
    if(!$receiver || !$text){ flash('error','Invalid message'); header("Location: index.php"); exit; }
    $stmt = $mysqli->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis",$sender,$receiver,$text);
    if($stmt->execute()) flash('success','Message sent'); else flash('error','Send failed');
    $stmt->close();
    header("Location: index.php?action=messages");
    exit;
}

if($action === 'approve_driver'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $pid = intval($_POST['approve_id'] ?? 0);
    if(!$pid){ flash('error','Invalid driver'); header("Location: index.php"); exit; }
    $stmt = $mysqli->prepare("UPDATE community_partners SET is_verified = 1 WHERE partner_id = ?");
    $stmt->bind_param("i",$pid); $stmt->execute(); $stmt->close();
    flash('success','Driver approved.');
    header("Location: index.php?action=admin_verify");
    exit;
}

 
if($action === 'create_post'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $title = trim($_POST['title'] ?? '');
    $body = trim($_POST['body'] ?? '');
    $uid = current_user_id();
    if(!$title || !$body) { flash('error', 'Title and body required.'); } else {
        $stmt = $mysqli->prepare("INSERT INTO content_posts (user_id, title, body) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $uid, $title, $body);
        if($stmt->execute()) flash('success', 'Content posted successfully.'); else flash('error', 'Failed to post content.');
        $stmt->close();
    }
    header("Location: index.php"); exit;
}
if($action === 'delete_post'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $pid = intval($_POST['post_id'] ?? 0);
    if($pid) { $mysqli->query("DELETE FROM content_posts WHERE post_id = $pid"); flash('success', 'Post deleted.'); }
    header("Location: index.php"); exit;
}

 
if($action === 'create_challenge'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $start = $_POST['start_date'] ?? '';
    $end = $_POST['end_date'] ?? '';

    if(!$title || !$start || !$end) { flash('error', 'Title and dates required.'); } else {
        $stmt = $mysqli->prepare("INSERT INTO challenges (title, description, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $title, $desc, $start, $end);
        if($stmt->execute()) flash('success', 'Challenge created.'); else flash('error', 'Failed to create challenge.');
        $stmt->close();
    }
    header("Location: index.php"); exit;
}
if($action === 'delete_challenge'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $cid = intval($_POST['challenge_id'] ?? 0);
    if($cid) { $mysqli->query("DELETE FROM challenges WHERE challenge_id = $cid"); flash('success', 'Challenge deleted.'); }
    header("Location: index.php"); exit;
}

 
if($action === 'add_reward'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $title = trim($_POST['title'] ?? '');
    $cost = intval($_POST['points_cost'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    
    if(!$title || $cost <= 0) { flash('error', 'Valid title and cost required.'); } else {
        $stmt = $mysqli->prepare("INSERT INTO rewards (title, points_cost, stock) VALUES (?, ?, ?)");
        $stmt->bind_param("sii", $title, $cost, $stock);
        if($stmt->execute()) flash('success', 'Reward added.'); else flash('error', 'Failed to add reward.');
        $stmt->close();
    }
    header("Location: index.php"); exit;
}
if($action === 'delete_reward'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $rid = intval($_POST['reward_id'] ?? 0);
    if($rid) { $mysqli->query("DELETE FROM rewards WHERE reward_id = $rid"); flash('success', 'Reward deleted.'); }
    header("Location: index.php"); exit;
}
if($action === 'give_points'){
    if(!is_logged_in() || current_user_role()!=='Admin'){ flash('error','Admin only'); header("Location: index.php"); exit; }
    $email = trim($_POST['user_email'] ?? '');
    $points = intval($_POST['points'] ?? 0);
    
    if(!$email || $points <= 0){ flash('error', 'Invalid email or points.'); } else {
        $stmt = $mysqli->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email); $stmt->execute();
        $res = $stmt->get_result();
        if($user = $res->fetch_assoc()){
            $uid = $user['user_id'];
            $stmt->close();
             
            $c_check = $mysqli->query("SELECT user_id FROM customers WHERE user_id = $uid");
            if($c_check->num_rows > 0){
                $mysqli->query("UPDATE customers SET total_points = total_points + $points WHERE user_id = $uid");
                $mysqli->query("INSERT INTO points_history (user_id, change_value, reason) VALUES ($uid, $points, 'Admin Award')");
                flash('success', "Assigned $points points to $email.");
            } else {
                flash('error', 'User is not a student/customer.');
            }
        } else {
            flash('error', 'User email not found.');
        }
    }
    header("Location: index.php"); exit;
}

 
if($action === 'update_vehicle'){
    if(!is_logged_in() || current_user_role()!=='Driver'){ flash('error','Driver only'); header("Location: index.php"); exit; }
    $uid = current_user_id();
    $model = trim($_POST['model'] ?? '');
    $plate = trim($_POST['plate'] ?? '');
    $capacity = intval($_POST['capacity'] ?? 4);

    if(!$model || !$plate || $capacity < 1) { flash('error', 'Valid vehicle details required.'); } else {
        $stmt = $mysqli->prepare("UPDATE community_partners SET vehicle_model = ?, license_plate = ?, capacity = ? WHERE user_id = ?");
        $stmt->bind_param("ssii", $model, $plate, $capacity, $uid);
        if($stmt->execute()) flash('success', 'Vehicle details updated.'); else flash('error', 'Update failed.');
        $stmt->close();
    }
    header("Location: index.php"); exit;
}
if($action === 'delete_ride'){
    if(!is_logged_in() || current_user_role()!=='Driver'){ flash('error','Driver only'); header("Location: index.php"); exit; }
    $rid = intval($_POST['ride_id'] ?? 0);
     
    $uid = current_user_id();
    $stmt = $mysqli->prepare("SELECT r.ride_id FROM rides r JOIN community_partners cp ON cp.partner_id = r.driver_partner_id WHERE r.ride_id = ? AND cp.user_id = ?");
    $stmt->bind_param("ii", $rid, $uid); $stmt->execute();
    if($stmt->get_result()->num_rows > 0){
        $mysqli->query("DELETE FROM rides WHERE ride_id = $rid");
        flash('success', 'Route deleted.');
    } else {
        flash('error', 'Invalid ride.');
    }
    $stmt->close();
    header("Location: index.php"); exit;
}
if($action === 'complete_ride'){
    if(!is_logged_in() || current_user_role()!=='Driver'){ flash('error','Driver only'); header("Location: index.php"); exit; }
    $rid = intval($_POST['ride_id'] ?? 0);
    $driver_uid = current_user_id();
    
     
    $stmt = $mysqli->prepare("SELECT r.ride_id, r.price, r.origin, r.destination FROM rides r JOIN community_partners cp ON cp.partner_id = r.driver_partner_id WHERE r.ride_id = ? AND cp.user_id = ?");
    $stmt->bind_param("ii", $rid, $driver_uid); $stmt->execute(); $ride_res = $stmt->get_result();
    
    if($ride = $ride_res->fetch_assoc()){
         
        $mysqli->begin_transaction();
        try {
             
            $mysqli->query("UPDATE rides SET status = 'Completed' WHERE ride_id = $rid");
            $mysqli->query("UPDATE ride_bookings SET status = 'Completed' WHERE ride_id = $rid");
            
            
            $price = $ride['price'];
            if($price > 0){
                 
                $p_stmt = $mysqli->prepare("SELECT passenger_user_id FROM ride_bookings WHERE ride_id = ?");
                $p_stmt->bind_param("i", $rid); $p_stmt->execute(); $passengers = $p_stmt->get_result();
                
                $total_earnings = 0;
                while($p = $passengers->fetch_assoc()){
                    $pid = $p['passenger_user_id'];
                    
                     
                    $mysqli->query("UPDATE users SET wallet_balance = wallet_balance - $price WHERE user_id = $pid");
                    $mysqli->query("INSERT INTO transactions (user_id, type, amount, description) VALUES ($pid, 'Debit', $price, 'Ride to {$ride['destination']}')");
                    
                    $total_earnings += $price;
                }
                
                 
                if($total_earnings > 0){
                    $mysqli->query("UPDATE users SET wallet_balance = wallet_balance + $total_earnings WHERE user_id = $driver_uid");
                    $mysqli->query("INSERT INTO transactions (user_id, type, amount, description) VALUES ($driver_uid, 'Credit', $total_earnings, 'Earnings: Ride to {$ride['destination']}')");
                }
            }
            
            $mysqli->commit();
            flash('success', 'Ride completed. Payments processed.');
        } catch (Exception $e) {
            $mysqli->rollback();
            flash('error', 'Error completing ride: '.$e->getMessage());
        }
    } else {
        flash('error', 'Invalid ride.');
    }
    header("Location: index.php"); exit;
}

 
if($action === 'submit_review'){
    if(!is_logged_in()){ flash('error','Login required'); header("Location: index.php"); exit; }
    $rid = intval($_POST['ride_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 5);
    $comment = trim($_POST['comment'] ?? '');
    $uid = current_user_id();

     
    $stmt = $mysqli->prepare("SELECT r.driver_partner_id FROM rides r JOIN ride_bookings rb ON rb.ride_id = r.ride_id WHERE r.ride_id = ? AND rb.passenger_user_id = ? AND r.status = 'Completed'");
    $stmt->bind_param("ii", $rid, $uid); $stmt->execute(); $res = $stmt->get_result();
    
    if($row = $res->fetch_assoc()){
        $driver_id = $row['driver_partner_id'];
        $stmt2 = $mysqli->prepare("INSERT INTO reviews (ride_id, reviewer_id, driver_partner_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt2->bind_param("iiiis", $rid, $uid, $driver_id, $rating, $comment);
        if($stmt2->execute()) flash('success', 'Thank you for your feedback!');
        else flash('error', 'Review submission failed.');
        $stmt2->close();
    } else {
        flash('error', 'Invalid or incomplete ride.');
    }
    header("Location: index.php"); exit;
}

 
if($action === 'broadcast_ride_alert'){
    if(!is_logged_in() || current_user_role()!=='Driver'){ flash('error','Driver only'); header("Location: index.php"); exit; }
    $rid = intval($_POST['ride_id'] ?? 0);
    $msg = trim($_POST['alert_message'] ?? '');
    $uid = current_user_id();

     
    $stmt = $mysqli->prepare("SELECT r.origin, r.destination FROM rides r JOIN community_partners cp ON cp.partner_id = r.driver_partner_id WHERE r.ride_id = ? AND cp.user_id = ?");
    $stmt->bind_param("ii", $rid, $uid); $stmt->execute(); $res = $stmt->get_result();
    
    if($row = $res->fetch_assoc()){
        if(empty($msg)){ flash('error','Message cannot be empty.'); }
        else {
             
            $stmt2 = $mysqli->prepare("SELECT passenger_user_id FROM ride_bookings WHERE ride_id = ?");
            $stmt2->bind_param("i", $rid); $stmt2->execute(); $res2 = $stmt2->get_result();
            
            $count = 0;
            $full_msg = "[ALERT: Ride to " . $row['destination'] . "] " . $msg;
            
            while($p = $res2->fetch_assoc()){
                $pid = $p['passenger_user_id'];
                $stmt3 = $mysqli->prepare("INSERT INTO messages (sender_id, receiver_id, message_text) VALUES (?, ?, ?)");
                $stmt3->bind_param("iis", $uid, $pid, $full_msg);
                $stmt3->execute();
                $count++;
            }
            flash('success', "Broadcast sent to $count passenger(s).");
        }
    } else {
        flash('error', 'Invalid ride.');
    }
    header("Location: index.php");  
    exit;
}

 
if($action === 'top_up_wallet'){
    if(!is_logged_in()){ flash('error','Login required'); header("Location: index.php"); exit; }
    
    $amount = floatval($_POST['amount'] ?? 0);
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = trim($_POST['card_number'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $uid = current_user_id();
    
     
    if($amount <= 0) {
        flash('error', 'Invalid amount.');
    } elseif (empty($card_name) || strlen($card_number) < 16 || strlen($cvv) < 3) {
        flash('error', 'Invalid card details. Please check your card info.');
    } else {
         
        $mysqli->query("UPDATE users SET wallet_balance = wallet_balance + $amount WHERE user_id = $uid");
        
         
        $masked_card = "Card ****" . substr(str_replace(' ', '', $card_number), -4);
        $desc = "Wallet Top Up via $masked_card";
        
        $stmt = $mysqli->prepare("INSERT INTO transactions (user_id, type, amount, description) VALUES (?, 'Credit', ?, ?)");
        $stmt->bind_param("ids", $uid, $amount, $desc);
        $stmt->execute();
        $stmt->close();
        
        flash('success', "Payment Successful! RM " . number_format($amount, 2) . " added to wallet.");
    }
    header("Location: index.php"); exit;
}


function get_leaders($mysqli){
    $out = [];
    $res = $mysqli->query("SELECT u.full_name, COALESCE(SUM(t.co2_saved_kg),0) AS co2 FROM customers c JOIN users u ON u.user_id = c.user_id LEFT JOIN trip_logs t ON t.user_id = u.user_id GROUP BY c.customer_id ORDER BY co2 DESC LIMIT 10");
    while($r = $res->fetch_assoc()) $out[] = $r;
    return $out;
}
function get_open_rides($mysqli){
    $out = [];
    $res = $mysqli->query("
        SELECT r.*, cp.full_name as driver_name, cp.vehicle_model, cp.license_plate, cp.capacity,
        (SELECT COUNT(*) FROM ride_bookings rb WHERE rb.ride_id = r.ride_id AND rb.status != 'Cancelled') as bookings_count
        FROM rides r 
        LEFT JOIN community_partners cp ON cp.partner_id = r.driver_partner_id 
        WHERE r.status = 'Scheduled' 
        ORDER BY r.departure_time ASC
    ");
    while($r = $res->fetch_assoc()) $out[] = $r;
    return $out;
}
function get_rewards($mysqli){
    $arr = [];
    $res = $mysqli->query("SELECT * FROM rewards ORDER BY points_cost ASC");
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}
function get_messages_for($mysqli, $uid){
    $arr = [];
    $stmt = $mysqli->prepare("SELECT m.*, su.full_name AS sender_name, ru.full_name AS receiver_name FROM messages m JOIN users su ON su.user_id=m.sender_id JOIN users ru ON ru.user_id=m.receiver_id WHERE m.sender_id = ? OR m.receiver_id = ? ORDER BY m.sent_at DESC LIMIT 100");
    $stmt->bind_param("ii",$uid,$uid); $stmt->execute(); $res = $stmt->get_result();
    while($r = $res->fetch_assoc()) $arr[] = $r;
    $stmt->close();
    return $arr;
}
function get_current_user_profile($mysqli, $uid){
    $stmt = $mysqli->prepare("SELECT full_name, email, phone_number, wallet_balance FROM users WHERE user_id = ?");
    $stmt->bind_param("i",$uid); $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $res;
}
 
function get_latest_content($mysqli) {
    $res = $mysqli->query("SELECT * FROM content_posts ORDER BY created_at DESC LIMIT 1");
    return $res->fetch_assoc();
}
 
function get_all_content($mysqli) {
    $res = $mysqli->query("SELECT * FROM content_posts ORDER BY created_at DESC");
    $arr = [];
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}
 
function get_active_challenges($mysqli) {
    $today = date('Y-m-d');
    $res = $mysqli->query("SELECT * FROM challenges WHERE end_date >= '$today' ORDER BY end_date ASC");
    $arr = [];
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}
 
function get_all_challenges($mysqli) {
    $res = $mysqli->query("SELECT * FROM challenges ORDER BY created_at DESC");
    $arr = [];
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}

function get_system_analytics($mysqli) {
    $data = [];
    
    $r1 = $mysqli->query("SELECT COUNT(*) as total_users FROM users");
    $data['users'] = $r1->fetch_assoc()['total_users'];
    
    $r2 = $mysqli->query("SELECT COUNT(*) as total_trips, SUM(distance_km) as total_dist, SUM(co2_saved_kg) as total_co2 FROM trip_logs");
    $stats = $r2->fetch_assoc();
    $data['trips'] = $stats['total_trips'] ?? 0;
    $data['dist'] = $stats['total_dist'] ?? 0;
    $data['co2'] = $stats['total_co2'] ?? 0;

     
    $r3 = $mysqli->query("SELECT transport_type, COUNT(*) as usage_count FROM trip_logs GROUP BY transport_type ORDER BY usage_count DESC");
    $modes = [];
    while($row = $r3->fetch_assoc()) $modes[] = $row;
    $data['modes'] = $modes;

     
    $r4 = $mysqli->query("SELECT origin, destination, COUNT(*) as frequency FROM rides GROUP BY origin, destination ORDER BY frequency DESC LIMIT 5");
    $routes = [];
    while($row = $r4->fetch_assoc()) $routes[] = $row;
    $data['routes'] = $routes;

    return $data;
}
 
function get_driver_details($mysqli, $uid){
    $stmt = $mysqli->prepare("SELECT * FROM community_partners WHERE user_id = ?");
    $stmt->bind_param("i", $uid); $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}
function get_driver_rides($mysqli, $uid){
    $stmt = $mysqli->prepare("SELECT r.* FROM rides r JOIN community_partners cp ON cp.partner_id = r.driver_partner_id WHERE cp.user_id = ? ORDER BY r.departure_time DESC");
    $stmt->bind_param("i", $uid); $stmt->execute();
    $res = $stmt->get_result();
    $arr = [];
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}
 
function get_pending_reviews($mysqli, $uid){
    $stmt = $mysqli->prepare("SELECT r.ride_id, r.origin, r.destination FROM ride_bookings rb JOIN rides r ON r.ride_id = rb.ride_id WHERE rb.passenger_user_id = ? AND r.status = 'Completed' AND r.ride_id NOT IN (SELECT ride_id FROM reviews WHERE reviewer_id = ?)");
    $stmt->bind_param("ii", $uid, $uid); $stmt->execute();
    $res = $stmt->get_result();
    $arr = [];
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}
 
function get_user_transactions($mysqli, $uid){
    $stmt = $mysqli->prepare("SELECT * FROM transactions WHERE user_id = ? ORDER BY created_at DESC LIMIT 20");
    $stmt->bind_param("i", $uid); $stmt->execute();
    $res = $stmt->get_result();
    $arr = [];
    while($r = $res->fetch_assoc()) $arr[] = $r;
    return $arr;
}
?>
<!doctype html>

<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>APU Go Green</title>
     
    <script src="https://cdn.tailwindcss.com"></script>
     
    <script src="https://unpkg.com/lucide@latest"></script>
     
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; }
    .font-display { font-family: 'Outfit', sans-serif; }
    
     
    .glass { background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(16px); border-bottom: 1px solid 
            rgba(255, 255, 255, 0.9); box-shadow: 0 4px 30px rgba(0, 0, 0, 0.03); }
    .glass-dark { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); }
    
    .animate-fade-in { animation: fadeIn 0.6s ease-out forwards; }
    .animate-slide-up { animation: slideUp 0.5s ease-out forwards; }
    
    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
    @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

     
    
    
    .text-gradient-animate {
        background: linear-gradient(to right, #059669, #34d399, #0d9488, #059669);
        background-size: 200% auto;
        color: transparent;
        -webkit-background-clip: text;
        background-clip: text;
        animation: gradientMove 3s linear infinite;
    }
    @keyframes gradientMove { to { background-position: 200% center; } }

     
    .wave-hand {
        display: inline-block;
        animation: wave 2.5s infinite;
        transform-origin: 70% 70%;
    }
    @keyframes wave {
        0% { transform: rotate(0deg); }
        10% { transform: rotate(14deg); }
        20% { transform: rotate(-8deg); }
        30% { transform: rotate(14deg); }
        40% { transform: rotate(-4deg); }
        50% { transform: rotate(10deg); }
        60% { transform: rotate(0deg); }
        100% { transform: rotate(0deg); }
    }

     
    #global-loader { backdrop-filter: blur(5px); }
    .spinner-leaf { animation: spin-leaf 1.5s ease-in-out infinite; }
    @keyframes spin-leaf { 0% { transform: rotate(0deg) scale(1); } 50% { transform: rotate(180deg) scale(1.2); } 100% { transform: rotate(360deg) scale(1); } }
    
     
    .quote-enter { animation: quoteEnter 0.8s cubic-bezier(0.2, 0.8, 0.2, 1) forwards; }
    @keyframes quoteEnter { 
        0% { opacity: 0; transform: translateY(20px) scale(0.95); filter: blur(5px); } 
        100% { opacity: 1; transform: translateY(0) scale(1); filter: blur(0); } 
    }
</style>
</head>
<body class="bg-slate-50 text-slate-800">
 
<div id="global-loader" class="fixed inset-0 z-[100] flex flex-col items-center justify-center bg-white/80 hidden transition-opacity duration-300">
    <div class="relative">
        <i data-lucide="leaf" class="w-16 h-16 text-emerald-600 spinner-leaf"></i>
        <div class="absolute inset-0 animate-ping rounded-full border-2 border-emerald-400 opacity-20"></div>
    </div>
    <p class="mt-4 text-emerald-800 font-semibold text-lg tracking-wide animate-pulse">Processing...</p>
</div>
 
<div class="fixed top-6 right-6 z-50 flex flex-col gap-3">
    <?php if($m=flash('success')): ?>
      <div class="glass bg-white border-l-4 border-emerald-500 text-slate-700 px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 animate-slide-up">
          <div class="p-2 bg-emerald-100 rounded-full"><i data-lucide="check" class="text-emerald-600 w-5 h-5"></i></div>
          <div><h4 class="font-bold text-sm text-emerald-800">Success</h4><p class="text-sm text-slate-600"><?php echo htmlspecialchars($m); ?></p></div>
      </div>
    <?php endif; ?>
    <?php if($m=flash('error')): ?>
      <div class="glass bg-white border-l-4 border-red-500 text-slate-700 px-6 py-4 rounded-xl shadow-2xl flex items-center gap-4 animate-slide-up">
          <div class="p-2 bg-red-100 rounded-full"><i data-lucide="alert-triangle" class="text-red-600 w-5 h-5"></i></div>
          <div><h4 class="font-bold text-sm text-red-800">Error</h4><p class="text-sm text-slate-600"><?php echo htmlspecialchars($m); ?></p></div>
      </div>
    <?php endif; ?>
</div>
 
<?php if(!is_logged_in()): ?>
<div class="min-h-screen flex items-center justify-center p-4 bg-[url('https://images.unsplash.com/photo-1518173946687-a4c8892bbd9f?q=80&w=2500&auto=format&fit=crop')] bg-cover bg-center">
    <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

<div class="relative z-10 max-w-5xl w-full bg-white rounded-3xl shadow-2xl overflow-hidden grid grid-cols-1 lg:grid-cols-2 animate-fade-in">
    
     
    <div class="hidden lg:flex flex-col justify-between bg-gradient-to-br from-emerald-800 to-teal-900 p-12 text-white relative overflow-hidden">
        <div class="absolute top-0 right-0 w-96 h-96 bg-emerald-500 rounded-full blur-3xl opacity-20 -mr-20 -mt-20"></div>
        <div class="absolute bottom-0 left-0 w-72 h-72 bg-teal-400 rounded-full blur-3xl opacity-20 -ml-20 -mb-20"></div>
        
        <div class="relative">
            <div class="flex items-center gap-3 mb-8">
                <div class="w-12 h-12 bg-white/20 backdrop-blur rounded-xl flex items-center justify-center">
                    <i data-lucide="leaf" class="w-6 h-6 text-emerald-300"></i>
                </div>
                <span class="font-bold text-2xl tracking-tight font-display">APU Go Green</span>
            </div>
            <h2 class="text-4xl font-extrabold leading-tight mb-6 font-display">Drive Less,<br><span class="text-emerald-300">Live More Sustainably.</span></h2>
            <p class="text-emerald-100/80 text-lg">Join thousands of students and staff making a difference. Track impact, share rides, and earn rewards.</p>
        </div>

        <div class="relative space-y-4">
            <div class="flex items-center gap-4 p-4 bg-white/10 rounded-xl border border-white/10 backdrop-blur-sm">
                <div class="p-2 bg-emerald-500/20 rounded-lg"><i data-lucide="shield-check" class="text-emerald-300"></i></div>
                <div>
                    <h4 class="font-bold font-display">Verified Community</h4>
                    <p class="text-xs text-emerald-200">Only APU students & staff.</p>
                </div>
            </div>
            <form method="post" class="text-center">
                <input type="hidden" name="action" value="seed">
                <button class="text-xs text-emerald-500/50 hover:text-emerald-300 transition">Initialize Demo Data</button>
            </form>
        </div>
    </div>

     
    <div class="p-8 lg:p-16 bg-white flex flex-col justify-center">
        <?php $view = $_GET['view'] ?? 'login'; ?>
        
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold font-display text-slate-900 mb-2">
                <?php echo $view === 'register' ? 'Join the Movement' : 'Welcome Back'; ?>
            </h1>
            <p class="text-slate-500">
                <?php echo $view === 'register' ? 'Create your green profile today.' : 'Enter your details to access your dashboard.'; ?>
            </p>
        </div>

        <?php if($view === 'register'): ?>
            <form method="post" class="space-y-5" onsubmit="showLoader()">
                <input type="hidden" name="action" value="register">
                <div class="space-y-4">
                    <div class="relative">
                        <i data-lucide="user" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400"></i>
                        <input name="full_name" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" placeholder="Full Name" required>
                    </div>
                    <div class="relative">
                        <i data-lucide="mail" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400"></i>
                        <input type="email" name="email" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" placeholder="Email Address" required>
                    </div>
                    <div class="relative">
                        <i data-lucide="lock" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400"></i>
                        <input type="password" name="password" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" placeholder="Password" required>
                    </div>
                    <div class="relative">
                        <i data-lucide="users" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400"></i>
                        <select name="role" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition appearance-none text-slate-600">
                            <option value="Student">Student / Staff</option>
                            <option value="Driver">Community Partner (Driver)</option>
                             
                        </select>
                    </div>
                </div>
                <button class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200 hover:shadow-xl hover:-translate-y-0.5 transition duration-200" type="submit">Create Account</button>
            </form>
            <div class="mt-8 text-center text-sm text-slate-500">Already a member? <a href="index.php?view=login" class="text-emerald-600 font-bold hover:underline">Sign In</a></div>
        <?php else: ?>
            <form method="post" class="space-y-6" onsubmit="showLoader()">
                <input type="hidden" name="action" value="login">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400"></i>
                            <input type="email" name="email" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" placeholder="yourname@apu.edu.my" required>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="absolute left-4 top-3.5 w-5 h-5 text-slate-400"></i>
                            <input type="password" name="password" class="w-full pl-12 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:border-emerald-500 focus:ring-2 focus:ring-emerald-200 outline-none transition" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>
                <button class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold py-3.5 rounded-xl shadow-lg shadow-emerald-200 hover:shadow-xl hover:-translate-y-0.5 transition duration-200" type="submit">Access Dashboard</button>
            </form>
            <div class="mt-8 text-center text-sm text-slate-500">New here? <a href="index.php?view=register" class="text-emerald-600 font-bold hover:underline">Create Account</a></div>
        <?php endif; ?>
    </div>
</div>
</div>
 
<?php else: ?>
<div class="flex h-screen overflow-hidden bg-slate-100">
   
  <aside class="w-72 bg-slate-900 text-white hidden lg:flex flex-col shadow-2xl relative z-20">
    <div class="h-24 flex items-center px-8 border-b border-slate-800 bg-slate-950">
        <div class="w-10 h-10 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-xl flex items-center justify-center mr-3 shadow-lg shadow-emerald-900/50">
            <i data-lucide="leaf" class="w-6 h-6 text-white"></i>
        </div>
        <div>
            <span class="font-bold text-xl tracking-wide block font-display">GoGreen</span>
            <span class="text-[10px] text-emerald-400 uppercase tracking-widest">APU Campus</span>
        </div>
    </div>
    <nav class="flex-1 overflow-y-auto py-8 px-4 space-y-2">
      <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Main Menu</p>
      <a href="#" onclick="openPage('dashboard'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
        <i data-lucide="layout-dashboard" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Dashboard
      </a>
      <a href="#" onclick="openPage('rides'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
        <i data-lucide="car" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Search Carpool
      </a>
      <a href="#" onclick="openPage('logtrip'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
        <i data-lucide="map-pin" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Log Trip
      </a>
      <a href="#" onclick="openPage('rewards'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
        <i data-lucide="gift" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Rewards
      </a>
      <a href="#" onclick="openPage('wallet'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
        <i data-lucide="wallet" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> My Wallet
      </a>
      <a href="index.php?action=messages" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
        <i data-lucide="message-square" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Messages
      </a>
      <?php if(current_user_role()==='Driver'): ?>
        <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mt-8 mb-2">Driver Zone</p>
        <a href="#" onclick="openPage('createRide'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="plus-circle" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Offer Ride
        </a>
        <a href="#" onclick="openPage('manageVehicle'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="truck" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Manage Vehicle
        </a>
      <?php endif; ?>
      <?php if(current_user_role()==='Admin'): ?>
        <p class="px-4 text-xs font-bold text-slate-500 uppercase tracking-wider mt-8 mb-2">Admin</p>
        <a href="index.php?action=admin_verify" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="shield-check" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Verification
        </a>
        <a href="#" onclick="openPage('manageContent'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="file-text" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Manage Content
        </a>
        <a href="#" onclick="openPage('manageChallenges'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="flag" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Manage Challenges
        </a>
        <a href="#" onclick="openPage('manageRewards'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="gift" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> Manage Rewards
        </a>
        <a href="#" onclick="openPage('analytics'); return false;" class="nav-item group flex items-center px-4 py-3.5 rounded-xl text-slate-300 hover:bg-slate-800 hover:text-emerald-400 transition-all border border-transparent hover:border-slate-700">
            <i data-lucide="bar-chart-2" class="w-5 h-5 mr-3 transition group-hover:scale-110"></i> System Analytics
        </a>
      <?php endif; ?>
    </nav>
    <div class="p-6 border-t border-slate-800 bg-slate-950/50">
        <div class="flex items-center gap-4 p-3 rounded-xl bg-slate-900 border border-slate-800">
            <div class="w-10 h-10 rounded-full bg-gradient-to-r from-emerald-500 to-teal-500 flex items-center justify-center text-white font-bold shadow-lg">
                <?php echo strtoupper(substr(current_user_name(),0,1)); ?>
            </div>
            <div class="flex-1 overflow-hidden">
                <h4 class="text-sm font-bold truncate text-white"><?php echo htmlspecialchars(current_user_name()); ?></h4>
                <p class="text-[10px] text-emerald-400 uppercase tracking-wide"><?php echo htmlspecialchars(current_user_role()); ?></p>
            </div>
            <a href="#" onclick="openPage('profile'); return false;" class="text-slate-400 hover:text-emerald-400 transition hover:bg-slate-800 p-2 rounded-lg" title="Settings">
                <i data-lucide="settings" class="w-4 h-4"></i>
            </a>
            <a href="#" onclick="handleLogout(); return false;" class="text-slate-400 hover:text-red-400 transition hover:bg-slate-800 p-2 rounded-lg" title="Logout">
                <i data-lucide="log-out" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
  </aside>
  
   
  <main class="flex-1 overflow-y-auto relative">

 
<header class="sticky top-0 z-10 glass px-8 py-5 flex justify-between items-center transition-all duration-300">
  <div class="flex flex-col">
    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Dashboard</p>
    <h1 class="text-3xl font-display font-black text-slate-900 flex items-center gap-3">
        Hello, <span class="text-gradient-animate"><?php echo htmlspecialchars(current_user_name()); ?></span> 
        <span class="wave-hand cursor-default" title="Have a great day!">👋</span>
    </h1>
  </div>
  <div class="flex items-center gap-4">
    <div class="hidden md:flex flex-col items-end mr-2">
        <span class="text-sm font-bold text-slate-700"><?php echo date('l'); ?></span>
        <span class="text-xs text-slate-500"><?php echo date('F j, Y'); ?></span>
    </div>
    <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center text-slate-600 border border-slate-200">
        <i data-lucide="bell" class="w-5 h-5"></i>
    </div>
  </div>
</header>

<div class="p-8 lg:p-10 max-w-7xl mx-auto">

     <div id="page-dashboard" class="animate-fade-in space-y-8">
        
         
        <?php $reviews_needed = get_pending_reviews($mysqli, current_user_id()); 
        if(!empty($reviews_needed)): ?>
        <div class="bg-amber-50 border border-amber-100 p-6 rounded-2xl flex flex-col md:flex-row items-center justify-between gap-4 shadow-sm animate-slide-up">
            <div class="flex items-center gap-4">
                <div class="p-3 bg-amber-100 rounded-xl text-amber-600"><i data-lucide="star" class="w-6 h-6"></i></div>
                <div>
                    <h3 class="text-lg font-bold text-amber-900">Rate Your Recent Trip</h3>
                    <p class="text-sm text-amber-700">How was your ride to <span class="font-bold"><?php echo htmlspecialchars($reviews_needed[0]['destination']); ?></span>?</p>
                </div>
            </div>
            <form method="post" class="flex items-center gap-3 w-full md:w-auto" onsubmit="showLoader()">
                <input type="hidden" name="action" value="submit_review">
                <input type="hidden" name="ride_id" value="<?php echo $reviews_needed[0]['ride_id']; ?>">
                <select name="rating" class="p-2 border border-amber-200 rounded-lg bg-white text-sm focus:outline-none focus:border-amber-400">
                    <option value="5">★★★★★ Excellent</option>
                    <option value="4">★★★★ Good</option>
                    <option value="3">★★★ Average</option>
                    <option value="2">★★ Poor</option>
                    <option value="1">★ Terrible</option>
                </select>
                <input name="comment" placeholder="Optional comment..." class="p-2 border border-amber-200 rounded-lg bg-white text-sm w-full md:w-48 focus:outline-none focus:border-amber-400">
                <button class="bg-amber-500 text-white px-4 py-2 rounded-lg font-bold hover:bg-amber-600 transition text-sm">Submit</button>
            </form>
        </div>
        <?php endif; ?>

         
        <div class="relative h-32 md:h-40 rounded-3xl bg-gradient-to-r from-emerald-900 via-teal-900 to-emerald-900 overflow-hidden shadow-2xl flex items-center justify-center p-6 text-center border border-emerald-800/50">
            <div class="absolute inset-0 bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-black/20 to-transparent"></div>
            
            <div id="quote-1" class="quote-item absolute w-full px-12 transition-all duration-700">
                <p class="text-xl md:text-2xl font-display font-bold text-transparent bg-clip-text bg-gradient-to-r from-emerald-100 to-teal-200 italic leading-relaxed drop-shadow-lg">"The greatest threat to our planet is the belief that someone else will save it."</p>
                <p class="text-xs text-emerald-400 mt-2 font-bold tracking-widest uppercase">— Robert Swan</p>
            </div>
            <div id="quote-2" class="quote-item hidden absolute w-full px-12 transition-all duration-700">
                <p class="text-xl md:text-2xl font-display font-bold text-transparent bg-clip-text bg-gradient-to-r from-emerald-100 to-teal-200 italic leading-relaxed drop-shadow-lg">"We do not inherit the earth from our ancestors, we borrow it from our children."</p>
                <p class="text-xs text-emerald-400 mt-2 font-bold tracking-widest uppercase">— Native American Proverb</p>
            </div>
            <div id="quote-3" class="quote-item hidden absolute w-full px-12 transition-all duration-700">
                <p class="text-xl md:text-2xl font-display font-bold text-transparent bg-clip-text bg-gradient-to-r from-emerald-100 to-teal-200 italic leading-relaxed drop-shadow-lg">"Sustainability is no longer about doing less harm. It's about doing more good."</p>
                <p class="text-xs text-emerald-400 mt-2 font-bold tracking-widest uppercase">— Jochen Zeitz</p>
            </div>
            <div id="quote-4" class="quote-item hidden absolute w-full px-12 transition-all duration-700">
                <p class="text-xl md:text-2xl font-display font-bold text-transparent bg-clip-text bg-gradient-to-r from-emerald-100 to-teal-200 italic leading-relaxed drop-shadow-lg">"Act as if what you do makes a difference. It does."</p>
                <p class="text-xs text-emerald-400 mt-2 font-bold tracking-widest uppercase">— William James</p>
            </div>
        </div>

        <?php
          $uid = current_user_id();
          $stmt = $mysqli->prepare("SELECT COALESCE(SUM(co2_saved_kg),0) AS total_co2, COALESCE(SUM(distance_km),0) AS total_dist FROM trip_logs WHERE user_id = ?");
          $stmt->bind_param("i",$uid); $stmt->execute(); $stats = $stmt->get_result()->fetch_assoc(); $stmt->close();
          $points = 0;
          if(current_user_role()==='Student'){
            $stmt = $mysqli->prepare("SELECT total_points FROM customers WHERE user_id = ?");
            $stmt->bind_param("i",$uid); $stmt->execute(); $p = $stmt->get_result()->fetch_assoc(); $stmt->close();
            $points = $p['total_points'] ?? 0;
          }
        ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-emerald-100 text-emerald-600 rounded-xl"><i data-lucide="leaf" class="w-6 h-6"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-slate-800 font-display"><?php echo number_format($stats['total_co2'] ?? 0,2); ?> <span class="text-lg font-medium text-slate-400 font-sans">kg</span></div>
            <div class="text-sm font-medium text-slate-500 mt-1">CO₂ Saved (Lifetime)</div>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-blue-100 text-blue-600 rounded-xl"><i data-lucide="map" class="w-6 h-6"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-slate-800 font-display"><?php echo number_format($stats['total_dist'] ?? 0,2); ?> <span class="text-lg font-medium text-slate-400 font-sans">km</span></div>
            <div class="text-sm font-medium text-slate-500 mt-1">Total Distance</div>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-amber-100 text-amber-600 rounded-xl"><i data-lucide="wallet" class="w-6 h-6"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-slate-800 font-display">RM <?php echo number_format( ($stats['total_dist']??0) * 0.5, 2); ?></div>
            <div class="text-sm font-medium text-slate-500 mt-1">Cost Savings</div>
          </div>
          <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 hover:shadow-lg hover:-translate-y-1 transition duration-300">
            <div class="flex justify-between items-start mb-4">
                <div class="p-3 bg-purple-100 text-purple-600 rounded-xl"><i data-lucide="star" class="w-6 h-6"></i></div>
            </div>
            <div class="text-3xl font-extrabold text-slate-800 font-display"><?php echo number_format($points); ?></div>
            <div class="text-sm font-medium text-slate-500 mt-1">Green Points</div>
          </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
             
            <div class="xl:col-span-2 bg-white rounded-3xl shadow-sm border border-slate-100 p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-slate-900 font-display">Leaderboard</h3>
                        <p class="text-slate-500 text-sm">Top contributors by Lifetime CO₂ Savings</p>
                    </div>
                    <div class="p-2 bg-emerald-50 rounded-lg text-emerald-600"><i data-lucide="trophy"></i></div>
                </div>
                <div class="space-y-4">
                    <?php foreach(get_leaders($mysqli) as $i => $row): 
                        $rankColor = match($i) { 0 => 'bg-yellow-100 text-yellow-700', 1 => 'bg-gray-100 text-gray-700', 2 => 'bg-orange-100 text-orange-700', default => 'text-slate-400 font-normal' };
                        $trophy = match($i) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => '#'.($i+1) };
                    ?>
                    <div class="flex items-center p-4 rounded-2xl <?php echo $i<3 ? 'bg-gradient-to-r from-slate-50 to-white border border-slate-100 shadow-sm' : ''; ?> transition hover:bg-slate-50">
                        <div class="w-12 h-12 flex items-center justify-center font-bold text-xl rounded-full <?php echo $rankColor; ?> mr-4">
                            <?php echo $trophy; ?>
                        </div>
                        <div class="flex-1">
                            <div class="font-bold text-slate-800 text-lg"><?php echo htmlspecialchars($row['full_name']); ?></div>
                            <div class="text-xs text-slate-500 font-medium">Eco Warrior</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-emerald-600 text-lg font-display"><?php echo number_format($row['co2'],1); ?> kg</div>
                            <div class="text-xs text-slate-400">CO₂ Saved</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

             
            <div class="space-y-8">
                 
                <div class="bg-gradient-to-br from-emerald-600 to-teal-700 rounded-3xl shadow-xl shadow-emerald-200 p-8 text-white text-center relative overflow-hidden flex flex-col justify-center h-auto min-h-[300px]">
                    <div class="absolute top-0 right-0 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                    <div class="relative z-10">
                        <?php $latest_post = get_latest_content($mysqli); ?>
                        <div class="w-16 h-16 bg-white/20 backdrop-blur rounded-2xl flex items-center justify-center mx-auto mb-4">
                            <i data-lucide="<?php echo $latest_post ? 'newspaper' : 'zap'; ?>" class="w-8 h-8 text-yellow-300"></i>
                        </div>
                        <?php if($latest_post): ?>
                            <h4 class="font-bold text-xl mb-2 font-display"><?php echo htmlspecialchars($latest_post['title']); ?></h4>
                            <p class="text-emerald-100 text-sm leading-relaxed mb-6 line-clamp-4"><?php echo nl2br(htmlspecialchars($latest_post['body'])); ?></p>
                            <span class="text-xs text-emerald-300 block mt-2">Posted on: <?php echo date('M d, Y', strtotime($latest_post['created_at'])); ?></span>
                        <?php else: ?>
                            <h4 class="font-bold text-xl mb-2 font-display">Did You Know?</h4>
                            <p class="text-emerald-100 text-sm leading-relaxed mb-6">Carpooling just twice a week reduces your annual carbon footprint by 1,600 pounds.</p>
                            <button onclick="openPage('logtrip')" class="w-full bg-white text-emerald-700 font-bold py-3 rounded-xl hover:bg-emerald-50 transition shadow-lg">Start Logging</button>
                        <?php endif; ?>
                    </div>
                </div>
                
                 
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                    <div class="flex items-center justify-between mb-4">
                         <h4 class="font-bold text-slate-800 font-display">Active Challenges</h4>
                         <span class="p-1.5 bg-indigo-100 text-indigo-600 rounded-lg"><i data-lucide="flag" class="w-4 h-4"></i></span>
                    </div>
                    <?php $active_challenges = get_active_challenges($mysqli); if(empty($active_challenges)): ?>
                        <p class="text-slate-400 text-sm italic">No active challenges right now.</p>
                    <?php else: foreach($active_challenges as $ac): ?>
                        <div class="p-3 bg-indigo-50 border border-indigo-100 rounded-xl mb-3 last:mb-0">
                            <div class="font-bold text-indigo-900 text-sm"><?php echo htmlspecialchars($ac['title']); ?></div>
                            <div class="text-xs text-indigo-700 mt-1 line-clamp-2"><?php echo htmlspecialchars($ac['description']); ?></div>
                            <div class="text-[10px] text-indigo-500 mt-2 font-bold uppercase tracking-wider">Ends: <?php echo date('M d', strtotime($ac['end_date'])); ?></div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 p-6">
                    <h4 class="font-bold text-slate-800 mb-4 font-display">Quick Shortcuts</h4>
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="openPage('rides')" class="p-4 bg-slate-50 hover:bg-emerald-50 hover:text-emerald-600 rounded-2xl transition border border-slate-100 flex flex-col items-center gap-2">
                            <i data-lucide="car"></i> <span class="text-xs font-bold">Find Ride</span>
                        </button>
                        <button onclick="openPage('rewards')" class="p-4 bg-slate-50 hover:bg-purple-50 hover:text-purple-600 rounded-2xl transition border border-slate-100 flex flex-col items-center gap-2">
                            <i data-lucide="gift"></i> <span class="text-xs font-bold">Rewards</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

     
    <div id="page-profile" class="hidden animate-slide-up">
        <?php $profile = get_current_user_profile($mysqli, current_user_id()); ?>
        <div class="max-w-4xl mx-auto">
             
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-800 font-display">Account Settings</h2>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm transition hover:-translate-y-0.5"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back to Dashboard</button>
            </div>

            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden border border-slate-100">
                 
                <div class="h-48 bg-gradient-to-r from-emerald-800 to-teal-600 relative overflow-hidden">
                    <div class="absolute inset-0 bg-white/10 opacity-30" style="background-image: radial-gradient(circle, #fff 2px, transparent 2.5px); background-size: 20px 20px;"></div>
                    <div class="absolute -bottom-10 -right-10 w-64 h-64 bg-emerald-400 rounded-full blur-3xl opacity-20"></div>
                </div>

                 
                <div class="px-10 relative flex flex-col md:flex-row items-end -mt-12 mb-10 gap-6">
                    <div class="w-24 h-24 rounded-2xl bg-white p-1.5 shadow-xl rotate-3">
                        <div class="w-full h-full bg-slate-100 rounded-xl flex items-center justify-center text-3xl font-black text-slate-700 uppercase">
                            <?php echo substr($profile['full_name'], 0, 1); ?>
                        </div>
                    </div>
                    <div class="pb-2 flex-1">
                        <h1 class="text-3xl font-black text-slate-900 font-display"><?php echo htmlspecialchars($profile['full_name']); ?></h1>
                        <p class="text-slate-500 font-medium flex items-center gap-2">
                            <i data-lucide="badge-check" class="w-4 h-4 text-emerald-500"></i> <?php echo current_user_role(); ?>
                        </p>
                    </div>
                </div>

                 
                <form method="post" class="px-10 pb-10 space-y-8" onsubmit="showLoader()">
                    <input type="hidden" name="action" value="update_profile">
                    
                     
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-emerald-100 flex items-center justify-center text-emerald-600"><i data-lucide="user" class="w-4 h-4"></i></span>
                            Personal Information
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Full Name</label>
                                <input name="full_name" value="<?php echo htmlspecialchars($profile['full_name']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-semibold text-slate-700 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none" required>
                            </div>
                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Email Address</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($profile['email']); ?>" class="w-full bg-slate-50 border border-slate-200 rounded-xl p-3 font-semibold text-slate-700 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none" required>
                            </div>
                            <div class="space-y-2 md:col-span-2">
                                <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">Phone Number</label>
                                <div class="relative">
                                    <i data-lucide="phone" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400"></i>
                                    <input type="text" name="phone_number" value="<?php echo htmlspecialchars($profile['phone_number'] ?? ''); ?>" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none" placeholder="+60 12-345 6789">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="h-px bg-slate-100"></div>

                     
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center text-red-600"><i data-lucide="shield-lock" class="w-4 h-4"></i></span>
                            Security
                        </h3>
                        <div class="space-y-2">
                            <label class="text-xs font-bold text-slate-500 uppercase tracking-wide">New Password</label>
                            <div class="relative">
                                <i data-lucide="lock" class="absolute left-4 top-3.5 w-4 h-4 text-slate-400"></i>
                                <input type="password" name="password" class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl font-semibold text-slate-700 focus:bg-white focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition outline-none" placeholder="Leave blank to keep current password">
                            </div>
                            <p class="text-xs text-slate-400 mt-1">Must be at least 8 characters long.</p>
                        </div>
                    </div>

                     
                    <div class="flex items-center justify-end gap-4 pt-4">
                        <button type="button" onclick="openPage('dashboard')" class="px-6 py-3 rounded-xl border border-slate-200 text-slate-600 font-bold hover:bg-slate-50 transition">Cancel</button>
                        <button type="submit" class="px-8 py-3 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold shadow-lg shadow-emerald-200 hover:shadow-emerald-300 hover:-translate-y-0.5 transition duration-200 flex items-center gap-2">
                            <i data-lucide="save" class="w-4 h-4"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

     
    <div id="page-wallet" class="hidden animate-slide-up">
        <?php $profile = get_current_user_profile($mysqli, current_user_id()); ?>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 font-display">My Wallet</h2>
                    <p class="text-slate-500">Manage your balance securely.</p>
                </div>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i> Back
                </button>
            </div>
            
            <div class="bg-gradient-to-br from-slate-900 to-slate-800 rounded-3xl p-8 text-white shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 w-64 h-64 bg-emerald-500 opacity-10 rounded-full -mr-16 -mt-16 blur-3xl"></div>
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-center gap-6">
                    <div>
                        <p class="text-slate-400 font-medium mb-1">Current Balance</p>
                        <h1 class="text-5xl font-black font-display tracking-tight text-emerald-400">RM <?php echo number_format($profile['wallet_balance'], 2); ?></h1>
                    </div>
                    <button onclick="togglePaymentModal(true)" class="bg-emerald-500 text-white font-bold px-8 py-4 rounded-2xl shadow-lg hover:bg-emerald-400 transition flex items-center gap-2">
                        <i data-lucide="plus" class="w-5 h-5"></i> Top Up Now
                    </button>
                </div>
            </div>
            
             
            <div id="payment-modal" class="fixed inset-0 z-[100] hidden items-center justify-center p-4 bg-slate-900/60 backdrop-blur-md">
                <div class="bg-white w-full max-w-md rounded-3xl shadow-2xl overflow-hidden animate-slide-up">
                    <div class="p-6 border-b flex justify-between items-center">
                        <h3 class="font-bold text-xl">Secure Checkout</h3>
                        <button onclick="togglePaymentModal(false)" class="p-2 hover:bg-slate-100 rounded-full">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                    <form method="post" class="p-8 space-y-5" onsubmit="showLoader()">
                        <input type="hidden" name="action" value="top_up_wallet">
                        <div class="w-full h-44 bg-gradient-to-tr from-indigo-600 to-purple-600 rounded-2xl p-6 text-white relative shadow-xl mb-4">
                            <div class="absolute top-4 right-6 text-xl italic font-black">VISA</div>
                            <div class="mt-8 font-mono text-xl tracking-widest" id="card-num-disp">•••• •••• •••• ••••</div>
                            <div class="mt-8 flex justify-between items-end">
                                <div>
                                    <div class="text-[10px] uppercase opacity-60">Holder</div>
                                    <div id="card-name-disp" class="text-sm font-bold uppercase tracking-wide">Owner Name</div>
                                </div>
                                <div>
                                    <div class="text-[10px] uppercase opacity-60">Expiry</div>
                                    <div class="text-sm font-bold">12/28</div>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Amount (RM)</label>
                            <input type="number" name="amount" step="0.01" min="1" class="w-full border-2 rounded-xl p-3 focus:border-emerald-500 outline-none text-lg font-bold" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Name on Card</label>
                            <input name="card_name" oninput="document.getElementById('card-name-disp').innerText = this.value || 'Owner Name'" class="w-full border-2 rounded-xl p-3 outline-none focus:border-emerald-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Card Number</label>
                            <input name="card_number" maxlength="19" oninput="formatCard(this)" class="w-full border-2 rounded-xl p-3 outline-none focus:border-emerald-500 font-mono" placeholder="0000 0000 0000 0000" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Expiry Date</label>
                                <input placeholder="MM/YY" maxlength="5" class="w-full border-2 rounded-xl p-3 outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">CVV</label>
                                <input name="cvv" maxlength="3" class="w-full border-2 rounded-xl p-3 outline-none" placeholder="123" required>
                            </div>
                        </div>
                        <button class="w-full bg-slate-900 text-white font-bold py-4 rounded-2xl hover:bg-emerald-600 transition flex justify-center items-center gap-2">
                            <i data-lucide="lock" class="w-4 h-4"></i> Confirm Payment
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Transaction History</h3>
                <div class="space-y-4">
                    <?php $transactions = get_user_transactions($mysqli, current_user_id()); 
                    if(empty($transactions)): ?>
                        <p class="text-slate-400 italic text-center py-8">No transactions yet.</p>
                    <?php else: foreach($transactions as $t): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="flex items-center gap-4">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold <?php echo $t['type']=='Credit' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600'; ?>">
                                    <i data-lucide="<?php echo $t['type']=='Credit' ? 'arrow-down-left' : 'arrow-up-right'; ?>" class="w-5 h-5"></i>
                                </div>
                                <div>
                                    <div class="font-bold text-slate-800"><?php echo htmlspecialchars($t['description']); ?></div>
                                    <div class="text-xs text-slate-500"><?php echo date('M d, Y H:i', strtotime($t['created_at'])); ?></div>
                                </div>
                            </div>
                            <div class="font-bold font-mono <?php echo $t['type']=='Credit' ? 'text-emerald-600' : 'text-red-600'; ?>">
                                <?php echo $t['type']=='Credit' ? '+' : '-'; ?> RM <?php echo number_format($t['amount'], 2); ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

     
    <div id="page-rides" class="hidden animate-slide-up space-y-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 font-display">Find a Carpool</h2>
                <p class="text-slate-500">Available rides scheduled by the community.</p>
            </div>
            <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
        </div>
        <?php $rides = get_open_rides($mysqli); if(empty($rides)): ?>
            <div class="flex flex-col items-center justify-center py-20 bg-white rounded-3xl border border-dashed border-slate-300">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mb-4"><i data-lucide="car" class="w-10 h-10 text-slate-300"></i></div>
                <h3 class="text-lg font-bold text-slate-700">No rides available right now</h3>
                <p class="text-slate-500">Check back later or offer a ride yourself!</p>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach($rides as $r): ?>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-xl hover:-translate-y-1 transition duration-300 group">
                    <div class="bg-slate-50 p-5 border-b border-slate-100 flex justify-between items-center">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-600 bg-emerald-100 px-2 py-1 rounded">Scheduled</span>
                        <span class="text-sm font-medium text-slate-500 flex items-center gap-1"><i data-lucide="clock" class="w-3 h-3"></i> <?php echo date('M d, H:i', strtotime($r['departure_time'])); ?></span>
                    </div>
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="text-xl font-bold text-slate-800 font-display"><?php echo htmlspecialchars($r['origin']); ?></div>
                            <i data-lucide="arrow-right" class="w-5 h-5 text-slate-400"></i>
                            <div class="text-xl font-bold text-slate-800 font-display"><?php echo htmlspecialchars($r['destination']); ?></div>
                        </div>
                        
                         
                        <div class="mb-4 flex flex-wrap gap-2">
                            <span class="text-[10px] font-bold uppercase bg-slate-100 px-2 py-1 rounded text-slate-600 flex items-center gap-1"><i data-lucide="car" class="w-3 h-3"></i> <?php echo htmlspecialchars($r['vehicle_model'] ?: 'Standard Vehicle'); ?></span>
                            <span class="text-[10px] font-bold uppercase bg-slate-100 px-2 py-1 rounded text-slate-600 flex items-center gap-1"><i data-lucide="hash" class="w-3 h-3"></i> <?php echo htmlspecialchars($r['license_plate'] ?: 'N/A'); ?></span>
                        </div>

                         
                        <div class="mb-6">
                            <div class="flex justify-between items-center mb-1"><span class="text-[10px] font-bold text-slate-500 uppercase">Seats Taken</span><span class="text-xs font-bold text-slate-700"><?php echo (int)$r['bookings_count']; ?> / <?php echo (int)$r['capacity']; ?></span></div>
                            <div class="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden"><div class="bg-emerald-500 h-full transition-all" style="width: <?php echo ($r['capacity'] > 0) ? ($r['bookings_count'] / $r['capacity']) * 100 : 0; ?>%"></div></div>
                        </div>

                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-slate-200 flex items-center justify-center text-xs font-bold text-slate-600"><?php echo strtoupper(substr($r['driver_name']??'U',0,1)); ?></div>
                                <div class="text-sm text-slate-600">Driver: <span class="font-bold text-slate-900"><?php echo htmlspecialchars($r['driver_name'] ?? 'Unknown'); ?></span></div>
                            </div>
                            <div class="text-lg font-bold text-indigo-600 bg-indigo-50 px-3 py-1 rounded-lg">RM <?php echo number_format($r['price'], 2); ?></div>
                        </div>

                        <?php $is_full = ($r['bookings_count'] >= $r['capacity']); ?>
                        <form method="post" onsubmit="showLoader()">
                            <input type="hidden" name="action" value="book_ride">
                            <input type="hidden" name="ride_id" value="<?php echo (int)$r['ride_id']; ?>">
                            <?php if($is_full): ?>
                                <button type="button" disabled class="w-full py-3 rounded-xl bg-slate-100 text-slate-400 font-bold cursor-not-allowed">Vehicle Full</button>
                            <?php else: ?>
                                <button class="w-full py-3 rounded-xl bg-slate-900 text-white font-bold hover:bg-emerald-600 transition flex items-center justify-center gap-2">Book Seat <i data-lucide="ticket" class="w-4 h-4"></i></button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

     
    <div id="page-logtrip" class="hidden animate-slide-up">
        <div class="max-w-2xl mx-auto">
            <div class="mb-6 flex items-center justify-between">
                <h2 class="text-3xl font-bold text-slate-900 font-display">Log Eco Trip</h2>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Cancel</button>
            </div>
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8 relative overflow-hidden">
                <div class="absolute top-0 right-0 w-32 h-32 bg-emerald-100 rounded-full blur-3xl -mr-10 -mt-10 opacity-50"></div>
                <form method="post" class="space-y-6 relative z-10" onsubmit="showLoader()">
                    <input type="hidden" name="action" value="log_trip">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Transport Mode</label>
                        <div class="grid grid-cols-2 gap-3">
                            <label class="cursor-pointer">
                                <input type="radio" name="transport_type" value="Bicycle" class="peer sr-only" required>
                                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:bg-slate-50 transition text-center">
                                    <i data-lucide="bike" class="w-6 h-6 mx-auto mb-1 text-slate-400 peer-checked:text-emerald-600"></i>
                                    <span class="text-sm font-bold text-slate-600 peer-checked:text-emerald-800">Bicycle</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="transport_type" value="Walk" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:bg-slate-50 transition text-center">
                                    <i data-lucide="footprints" class="w-6 h-6 mx-auto mb-1 text-slate-400 peer-checked:text-emerald-600"></i>
                                    <span class="text-sm font-bold text-slate-600 peer-checked:text-emerald-800">Walk</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="transport_type" value="Public Bus" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:bg-slate-50 transition text-center">
                                    <i data-lucide="bus" class="w-6 h-6 mx-auto mb-1 text-slate-400 peer-checked:text-emerald-600"></i>
                                    <span class="text-sm font-bold text-slate-600 peer-checked:text-emerald-800">Bus</span>
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" name="transport_type" value="Carpool" class="peer sr-only">
                                <div class="p-4 rounded-xl border-2 border-slate-100 peer-checked:border-emerald-500 peer-checked:bg-emerald-50 hover:bg-slate-50 transition text-center">
                                    <i data-lucide="car" class="w-6 h-6 mx-auto mb-1 text-slate-400 peer-checked:text-emerald-600"></i>
                                    <span class="text-sm font-bold text-slate-600 peer-checked:text-emerald-800">Carpool</span>
                                </div>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Distance (KM)</label>
                        <input name="distance_km" type="number" step="0.01" class="w-full border-2 border-slate-100 rounded-xl p-4 text-lg font-mono focus:border-emerald-500 focus:outline-none transition" placeholder="0.00" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-2">Date</label>
                        <input name="log_date" type="date" class="w-full border-2 border-slate-100 rounded-xl p-4 focus:border-emerald-500 focus:outline-none transition" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <button class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold py-4 rounded-xl shadow-lg hover:shadow-xl hover:-translate-y-1 transition duration-300">Calculate & Save Trip</button>
                </form>
            </div>
        </div>
    </div>

     
    <div id="page-rewards" class="hidden animate-slide-up space-y-6">
        <div class="flex items-center justify-between mb-2">
            <div>
                <h2 class="text-3xl font-bold text-slate-900 font-display">Redeem Rewards</h2>
                <p class="text-slate-500">Use your green points for campus perks.</p>
            </div>
            <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach(get_rewards($mysqli) as $rw): ?>
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden flex flex-col h-full hover:border-purple-300 transition duration-300">
                <div class="h-32 bg-gradient-to-br from-purple-100 to-indigo-50 flex items-center justify-center">
                    <i data-lucide="gift" class="w-12 h-12 text-purple-400"></i>
                </div>
                <div class="p-6 flex-1 flex flex-col">
                    <h4 class="font-bold text-lg text-slate-900 mb-1 font-display"><?php echo htmlspecialchars($rw['title']); ?></h4>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-4">
                        <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded font-bold text-xs"><?php echo (int)$rw['points_cost']; ?> PTS</span>
                        <span>• <?php echo (int)$rw['stock']; ?> left</span>
                    </div>
                    <div class="mt-auto">
                        <?php if(current_user_role()==='Student'): ?>
                            <form method="post" onsubmit="showLoader()">
                                <input type="hidden" name="action" value="claim_reward">
                                <input type="hidden" name="reward_id" value="<?php echo (int)$rw['reward_id']; ?>">
                                <button class="w-full py-2.5 rounded-xl border-2 border-purple-600 text-purple-600 font-bold hover:bg-purple-600 hover:text-white transition">Claim Reward</button>
                            </form>
                        <?php else: ?>
                            <button disabled class="w-full py-2.5 rounded-xl bg-slate-100 text-slate-400 font-bold cursor-not-allowed">Students Only</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

     
    <?php if(current_user_role()==='Driver'): ?>
    <div id="page-createRide" class="hidden animate-slide-up">
        <div class="max-w-2xl mx-auto bg-white rounded-3xl shadow-xl border border-slate-100 p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-slate-900 font-display">Offer a Ride</h2>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800">Close</button>
            </div>
            <form method="post" class="space-y-4" onsubmit="showLoader()">
                <input type="hidden" name="action" value="create_ride">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Origin</label>
                        <input name="origin" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Destination</label>
                        <input name="destination" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" required>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Departure Time</label>
                        <input type="datetime-local" name="departure_time" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Price per Seat (RM)</label>
                        <input type="number" name="price" step="0.01" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" placeholder="0.00" required>
                    </div>
                </div>
                <button class="w-full bg-emerald-600 text-white font-bold py-3 rounded-xl hover:bg-emerald-700 transition">Post Ride</button>
            </form>
        </div>
    </div>
    
     
    <div id="page-manageVehicle" class="hidden animate-slide-up">
        <?php $driver_details = get_driver_details($mysqli, current_user_id()); ?>
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 font-display">Manage Vehicle</h2>
                    <p class="text-slate-500">Update car details and manage active routes.</p>
                </div>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
            </div>

             
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i data-lucide="truck" class="w-5 h-5 text-emerald-600"></i> Vehicle Information</h3>
                <form method="post" class="space-y-4" onsubmit="showLoader()">
                    <input type="hidden" name="action" value="update_vehicle">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Vehicle Model</label>
                            <input name="model" value="<?php echo htmlspecialchars($driver_details['vehicle_model'] ?? ''); ?>" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" placeholder="e.g. Perodua Myvi" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">License Plate</label>
                            <input name="plate" value="<?php echo htmlspecialchars($driver_details['license_plate'] ?? ''); ?>" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" placeholder="e.g. VAA 1234" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Capacity</label>
                            <input type="number" name="capacity" value="<?php echo htmlspecialchars($driver_details['capacity'] ?? 4); ?>" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" min="1" max="10" required>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button class="bg-slate-800 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-slate-900 transition">Update Details</button>
                    </div>
                </form>
            </div>

            
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">My Active Routes</h3>
                <div class="space-y-3">
                    <?php $my_rides = get_driver_rides($mysqli, current_user_id()); if(empty($my_rides)): ?>
                        <p class="text-slate-400 italic text-center py-4">No active routes posted.</p>
                    <?php else: foreach($my_rides as $mr): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div>
                                <div class="font-bold text-slate-800 text-lg flex items-center gap-2">
                                    <?php echo htmlspecialchars($mr['origin']); ?> 
                                    <i data-lucide="arrow-right" class="w-4 h-4 text-slate-400"></i> 
                                    <?php echo htmlspecialchars($mr['destination']); ?>
                                </div>
                                <div class="text-xs text-slate-500 font-mono mt-1 flex items-center gap-2">
                                    <span><i data-lucide="clock" class="w-3 h-3 inline mr-1"></i> <?php echo date('M d, H:i', strtotime($mr['departure_time'])); ?></span>
                                    <?php if(($mr['status']??'')==='Completed'): ?>
                                        <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase">Completed</span>
                                    <?php else: ?>
                                        <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold uppercase">Scheduled</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <?php if(($mr['status']??'')!=='Completed'): ?>
                                    <form method="post" onsubmit="return confirm('Mark this ride as completed?');">
                                        <input type="hidden" name="action" value="complete_ride">
                                        <input type="hidden" name="ride_id" value="<?php echo $mr['ride_id']; ?>">
                                        <button class="text-emerald-500 hover:text-emerald-600 p-2 border border-emerald-100 hover:bg-emerald-50 rounded-lg transition" title="Complete Trip">
                                            <i data-lucide="check-circle" class="w-5 h-5"></i>
                                        </button>
                                    </form>
                                    <button onclick="broadcastAlert(<?php echo $mr['ride_id']; ?>)" class="text-amber-500 hover:text-amber-600 p-2 border border-amber-100 hover:bg-amber-50 rounded-lg transition" title="Broadcast Delay/Alert">
                                        <i data-lucide="megaphone" class="w-5 h-5"></i>
                                    </button>
                                    <form method="post" onsubmit="return confirm('Delete this route?');">
                                        <input type="hidden" name="action" value="delete_ride">
                                        <input type="hidden" name="ride_id" value="<?php echo $mr['ride_id']; ?>">
                                        <button class="text-red-400 hover:text-red-600 p-2 border border-red-100 hover:bg-red-50 rounded-lg transition"><i data-lucide="trash-2" class="w-5 h-5"></i></button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
     
    <?php if(current_user_role()==='Admin'): ?>
    <div id="page-manageContent" class="hidden animate-slide-up">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 font-display">Manage Content</h2>
                    <p class="text-slate-500">Post news and tips for the community.</p>
                </div>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
            </div>
            
             
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Post New Tip/Article</h3>
                <form method="post" class="space-y-4" onsubmit="showLoader()">
                    <input type="hidden" name="action" value="create_post">
                    <div>
                        <input name="title" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none font-bold" placeholder="Article Title" required>
                    </div>
                    <div>
                        <textarea name="body" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none h-32 resize-none" placeholder="Write your content here..." required></textarea>
                    </div>
                    <div class="flex justify-end">
                        <button class="bg-emerald-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-emerald-700 transition flex items-center gap-2"><i data-lucide="send" class="w-4 h-4"></i> Publish Post</button>
                    </div>
                </form>
            </div>

             
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Recent Posts</h3>
                <div class="space-y-4">
                    <?php $posts = get_all_content($mysqli); if(empty($posts)): ?>
                        <p class="text-slate-400 italic text-center py-4">No content posted yet.</p>
                    <?php else: foreach($posts as $p): ?>
                        <div class="flex items-start justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div>
                                <h4 class="font-bold text-slate-800"><?php echo htmlspecialchars($p['title']); ?></h4>
                                <p class="text-sm text-slate-500 line-clamp-1"><?php echo htmlspecialchars($p['body']); ?></p>
                                <span class="text-xs text-slate-400 mt-1 block"><?php echo date('M d, Y H:i', strtotime($p['created_at'])); ?></span>
                            </div>
                            <form method="post" onsubmit="return confirm('Delete this post?');">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?php echo $p['post_id']; ?>">
                                <button class="text-red-400 hover:text-red-600 p-2"><i data-lucide="trash-2" class="w-5 h-5"></i></button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    
     
    <div id="page-manageChallenges" class="hidden animate-slide-up">
        <div class="max-w-4xl mx-auto space-y-6">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 font-display">Manage Challenges</h2>
                    <p class="text-slate-500">Create time-bound campaigns.</p>
                </div>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
            </div>
            
             
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Create New Campaign</h3>
                <form method="post" class="space-y-4" onsubmit="showLoader()">
                    <input type="hidden" name="action" value="create_challenge">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                            <input name="title" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" placeholder="e.g. Car-Free Week" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Start Date</label>
                            <input type="date" name="start_date" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">End Date</label>
                            <input type="date" name="end_date" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Description</label>
                            <textarea name="description" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-emerald-500 outline-none h-24 resize-none" placeholder="Describe the challenge..." required></textarea>
                        </div>
                    </div>
                    <div class="flex justify-end">
                        <button class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl font-bold hover:bg-indigo-700 transition flex items-center gap-2"><i data-lucide="plus" class="w-4 h-4"></i> Create Challenge</button>
                    </div>
                </form>
            </div>

             
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">All Challenges</h3>
                <div class="space-y-3">
                    <?php $challenges = get_all_challenges($mysqli); if(empty($challenges)): ?>
                        <p class="text-slate-400 italic text-center py-4">No challenges created.</p>
                    <?php else: foreach($challenges as $c): ?>
                        <div class="flex items-center justify-between p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div>
                                <h4 class="font-bold text-slate-800"><?php echo htmlspecialchars($c['title']); ?></h4>
                                <div class="text-xs text-slate-500 font-mono mt-1">
                                    <?php echo date('M d', strtotime($c['start_date'])) . ' - ' . date('M d, Y', strtotime($c['end_date'])); ?>
                                </div>
                            </div>
                            <form method="post" onsubmit="return confirm('Delete this challenge?');">
                                <input type="hidden" name="action" value="delete_challenge">
                                <input type="hidden" name="challenge_id" value="<?php echo $c['challenge_id']; ?>">
                                <button class="text-red-400 hover:text-red-600 p-2"><i data-lucide="trash-2" class="w-5 h-5"></i></button>
                            </form>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
    
     
    <div id="page-manageRewards" class="hidden animate-slide-up">
        <div class="max-w-6xl mx-auto space-y-6">
            <div class="flex items-center justify-between mb-2">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 font-display">Manage Rewards</h2>
                    <p class="text-slate-500">Update inventory and issue manual points.</p>
                </div>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                 
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 h-fit">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i data-lucide="plus-circle" class="w-5 h-5 text-purple-600"></i> Add New Reward</h3>
                    <form method="post" class="space-y-4" onsubmit="showLoader()">
                        <input type="hidden" name="action" value="add_reward">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Title</label>
                            <input name="title" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-purple-500 outline-none" placeholder="e.g. Free Coffee Voucher" required>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Cost (Points)</label>
                                <input type="number" name="points_cost" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-purple-500 outline-none" required>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Stock</label>
                                <input type="number" name="stock" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-purple-500 outline-none" required>
                            </div>
                        </div>
                        <button class="w-full bg-purple-600 text-white font-bold py-3 rounded-xl hover:bg-purple-700 transition">Add to Inventory</button>
                    </form>
                </div>

                 
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6 h-fit">
                    <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2"><i data-lucide="star" class="w-5 h-5 text-yellow-500"></i> Give Points (Manual)</h3>
                    <form method="post" class="space-y-4" onsubmit="showLoader()">
                        <input type="hidden" name="action" value="give_points">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Student Email</label>
                            <input type="email" name="user_email" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-yellow-500 outline-none" placeholder="student@apu.edu.my" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Points Amount</label>
                            <input type="number" name="points" class="w-full border-2 border-slate-100 rounded-xl p-3 focus:border-yellow-500 outline-none" placeholder="e.g. 50" required>
                        </div>
                        <button class="w-full bg-yellow-500 text-white font-bold py-3 rounded-xl hover:bg-yellow-600 transition">Assign Points</button>
                    </form>
                </div>
            </div>

            
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Current Inventory</h3>
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-slate-400 text-xs uppercase border-b border-slate-100">
                                <th class="pb-3 pl-2">Item</th>
                                <th class="pb-3">Cost</th>
                                <th class="pb-3">Stock</th>
                                <th class="pb-3 text-right pr-2">Action</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm text-slate-700">
                            <?php foreach(get_rewards($mysqli) as $rw): ?>
                            <tr class="border-b border-slate-50 hover:bg-slate-50/50 transition">
                                <td class="py-4 pl-2 font-bold"><?php echo htmlspecialchars($rw['title']); ?></td>
                                <td class="py-4"><span class="bg-purple-100 text-purple-700 px-2 py-1 rounded text-xs font-bold"><?php echo $rw['points_cost']; ?> pts</span></td>
                                <td class="py-4"><?php echo $rw['stock']; ?> left</td>
                                <td class="py-4 text-right pr-2">
                                    <form method="post" onsubmit="return confirm('Remove reward?');" class="inline">
                                        <input type="hidden" name="action" value="delete_reward">
                                        <input type="hidden" name="reward_id" value="<?php echo $rw['reward_id']; ?>">
                                        <button class="text-red-400 hover:text-red-600 p-1"><i data-lucide="trash-2" class="w-4 h-4"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
     
    <div id="page-analytics" class="hidden animate-slide-up">
        <?php $analytics = get_system_analytics($mysqli); ?>
        <div class="max-w-6xl mx-auto space-y-8">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-3xl font-bold text-slate-900 font-display">System Analytics</h2>
                    <p class="text-slate-500">Overview of system performance and impact.</p>
                </div>
                <button onclick="openPage('dashboard')" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2 bg-white px-4 py-2 rounded-lg border shadow-sm"><i data-lucide="arrow-left" class="w-4 h-4"></i> Back</button>
            </div>

             
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-2 text-slate-500 text-sm font-bold uppercase"><i data-lucide="users" class="w-4 h-4"></i> Total Users</div>
                    <div class="text-3xl font-black text-slate-800 font-display"><?php echo number_format($analytics['users']); ?></div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-2 text-slate-500 text-sm font-bold uppercase"><i data-lucide="map" class="w-4 h-4"></i> Total Trips</div>
                    <div class="text-3xl font-black text-slate-800 font-display"><?php echo number_format($analytics['trips']); ?></div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-emerald-100 bg-emerald-50/50 shadow-sm">
                    <div class="flex items-center gap-3 mb-2 text-emerald-600 text-sm font-bold uppercase"><i data-lucide="leaf" class="w-4 h-4"></i> Total CO₂ Saved</div>
                    <div class="text-3xl font-black text-emerald-800 font-display"><?php echo number_format($analytics['co2'], 1); ?> kg</div>
                </div>
                <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-2 text-slate-500 text-sm font-bold uppercase"><i data-lucide="navigation" class="w-4 h-4"></i> Total Distance</div>
                    <div class="text-3xl font-black text-slate-800 font-display"><?php echo number_format($analytics['dist'], 1); ?> km</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                 
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Transport Mode Breakdown</h3>
                    <div class="space-y-6">
                        <?php 
                        $total_usage = 0; foreach($analytics['modes'] as $m) $total_usage += $m['usage_count'];
                        foreach($analytics['modes'] as $m): 
                            $pct = $total_usage > 0 ? ($m['usage_count'] / $total_usage) * 100 : 0;
                        ?>
                        <div>
                            <div class="flex justify-between text-sm font-bold text-slate-700 mb-1">
                                <span><?php echo htmlspecialchars($m['transport_type']); ?></span>
                                <span><?php echo number_format($pct, 1); ?>%</span>
                            </div>
                            <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden">
                                <div class="bg-emerald-500 h-3 rounded-full" style="width: <?php echo $pct; ?>%"></div>
                            </div>
                            <div class="text-xs text-slate-400 mt-1"><?php echo $m['usage_count']; ?> trips</div>
                        </div>
                        <?php endforeach; ?>
                        <?php if(empty($analytics['modes'])) echo "<p class='text-slate-400 italic'>No data available.</p>"; ?>
                    </div>
                </div>

                 
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                    <h3 class="text-lg font-bold text-slate-800 mb-6">Most Popular Routes</h3>
                    <div class="space-y-4">
                        <?php if(empty($analytics['routes'])): ?>
                            <p class="text-slate-400 italic">No routes recorded yet.</p>
                        <?php else: foreach($analytics['routes'] as $i => $r): ?>
                            <div class="flex items-center justify-between p-4 bg-slate-50 rounded-2xl border border-slate-100">
                                <div class="flex items-center gap-4">
                                    <div class="w-8 h-8 rounded-full bg-white flex items-center justify-center font-bold text-slate-400 border border-slate-200 shadow-sm">
                                        #<?php echo $i+1; ?>
                                    </div>
                                    <div>
                                        <div class="font-bold text-slate-800 text-sm"><?php echo htmlspecialchars($r['origin']); ?> <span class="text-slate-400 mx-1">→</span> <?php echo htmlspecialchars($r['destination']); ?></div>
                                    </div>
                                </div>
                                <div class="px-3 py-1 bg-white rounded-lg border border-slate-200 text-xs font-bold text-slate-600 shadow-sm">
                                    <?php echo $r['frequency']; ?> offers
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

     
    <?php if(isset($_GET['action']) && $_GET['action'] === 'messages'): ?>
    <script>document.addEventListener('DOMContentLoaded', () => { document.getElementById('page-dashboard').classList.add('hidden'); });</script>
    <div class="animate-slide-up max-w-4xl mx-auto h-[80vh] bg-white rounded-3xl shadow-2xl border border-slate-200 overflow-hidden flex flex-col">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 class="font-bold text-xl text-slate-800 font-display">Messages</h3>
            <a href="index.php" class="text-slate-400 hover:text-slate-600 bg-white p-2 rounded-full border shadow-sm"><i data-lucide="x" class="w-5 h-5"></i></a>
        </div>
        <div class="flex-1 overflow-y-auto p-6 space-y-4 bg-slate-50/50">
            <?php $msgs = get_messages_for($mysqli, current_user_id()); if(empty($msgs)): ?>
                <div class="text-center py-20 opacity-50">
                    <i data-lucide="message-circle" class="w-16 h-16 mx-auto mb-2 text-slate-300"></i>
                    <p>No messages found</p>
                </div>
            <?php else: foreach($msgs as $m): $isMe = ($m['sender_id'] == current_user_id()); ?>
                <div class="flex flex-col <?php echo $isMe ? 'items-end' : 'items-start'; ?>">
                    <div class="max-w-md px-5 py-3 rounded-2xl text-sm shadow-sm <?php echo $isMe ? 'bg-emerald-600 text-white rounded-br-none' : 'bg-white border border-slate-200 text-slate-700 rounded-bl-none'; ?>">
                        <?php echo nl2br(htmlspecialchars($m['message_text'])); ?>
                    </div>
                    <span class="text-[10px] text-slate-400 mt-1 px-1 font-medium"><?php echo $isMe ? 'You' : htmlspecialchars($m['sender_name']); ?> • <?php echo date('H:i', strtotime($m['sent_at'])); ?></span>
                </div>
            <?php endforeach; endif; ?>
        </div>
        <div class="p-4 bg-white border-t border-slate-100">
            <form method="post" class="flex gap-3" onsubmit="showLoader()">
                <input type="hidden" name="action" value="send_message">
                <input name="to_user" placeholder="User ID" class="w-24 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500 bg-slate-50" required>
                <input name="text" placeholder="Type a message..." class="flex-1 border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:border-emerald-500 bg-slate-50" required>
                <button class="bg-emerald-600 text-white p-3 rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-200 transition"><i data-lucide="send" class="w-5 h-5"></i></button>
            </form>
        </div>
    </div>
    <?php endif; ?>

     
    <?php if(isset($_GET['action']) && $_GET['action'] === 'admin_verify' && current_user_role()==='Admin'): ?>
    <script>document.addEventListener('DOMContentLoaded', () => { document.getElementById('page-dashboard').classList.add('hidden'); });</script>
    <div class="max-w-3xl mx-auto bg-white p-8 rounded-3xl shadow-xl border border-slate-100 animate-slide-up">
        <div class="flex justify-between items-center mb-6">
            <h3 class="font-bold text-xl font-display">Driver Verification Queue</h3>
            <a href="index.php" class="text-sm font-bold text-slate-500">Back to Dashboard</a>
        </div>
        <div class="space-y-3">
        <?php
          $res = $mysqli->query("SELECT cp.*, u.email FROM community_partners cp JOIN users u ON u.user_id = cp.user_id WHERE cp.is_verified = 0");
          if($res->num_rows == 0) echo "<p class='text-slate-400 italic text-center py-8'>No pending verifications.</p>";
          while($row = $res->fetch_assoc()):
        ?>
          <div class="p-5 border border-slate-200 rounded-2xl flex justify-between items-center hover:bg-slate-50 transition">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-slate-200 rounded-full flex items-center justify-center font-bold text-slate-600"><?php echo strtoupper(substr($row['full_name'],0,1)); ?></div>
                <div><div class="font-bold text-slate-900"><?php echo htmlspecialchars($row['full_name']); ?></div><div class="text-xs text-slate-500 font-mono"><?php echo htmlspecialchars($row['email']); ?></div></div>
            </div>
            <form method="post" onsubmit="showLoader()">
              <input type="hidden" name="action" value="approve_driver">
              <input type="hidden" name="approve_id" value="<?php echo (int)$row['partner_id']; ?>">
              <button class="bg-emerald-600 text-white px-5 py-2 rounded-xl text-sm font-bold hover:bg-emerald-700 shadow-md transition">Approve</button>
            </form>
          </div>
        <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>

</div>
  </main>
  <?php endif; ?>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => { 
        if(window.lucide) lucide.createIcons();
        
         
        const quotes = document.querySelectorAll('.quote-item');
        let currentQuote = 0;
        if(quotes.length > 0) {
             
            quotes[0].classList.remove('hidden');
            quotes[0].classList.add('quote-enter');
            
            setInterval(() => {
                quotes[currentQuote].classList.add('hidden');
                quotes[currentQuote].classList.remove('quote-enter');
                
                currentQuote = (currentQuote + 1) % quotes.length;
                
                quotes[currentQuote].classList.remove('hidden');
                quotes[currentQuote].classList.add('quote-enter');
            }, 5000);  
        }
    });
    function showLoader() { const l = document.getElementById('global-loader'); if(l) { l.classList.remove('hidden'); l.classList.add('flex'); } }
    function handleLogout() { showLoader(); setTimeout(() => { window.location.href = 'index.php?logout=1'; }, 1000); }
    function openPage(pageId) {
        const pages = ['page-dashboard', 'page-rides', 'page-logtrip', 'page-rewards', 'page-createRide', 'page-profile', 
        'page-manageContent', 'page-manageChallenges', 'page-manageRewards', 'page-analytics', 'page-manageVehicle', 'page-wallet'];
        pages.forEach(id => { const el = document.getElementById(id); if(el) el.classList.add('hidden'); });
        const target = document.getElementById('page-' + pageId);
        if(target) { target.classList.remove('hidden'); target.classList.remove('animate-slide-up'); void target.offsetWidth; target.classList.add('animate-slide-up'); }
    }
    function broadcastAlert(rideId) {
        const msg = prompt("Enter alert message for passengers (e.g., Delay 10 mins):");
        if(msg) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `<input type='hidden' name='action' value='broadcast_ride_alert'>
                              <input type='hidden' name='ride_id' value='${rideId}'>
                              <input type='hidden' name='alert_message' value='${msg}'>`;
            document.body.appendChild(form);
            showLoader();
            form.submit();
        }
    }
    
     
    function togglePaymentModal(show) {
        const modal = document.getElementById('payment-modal');
        if (show) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        } else {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    }
    
    
    function formatCard(input) {
        let value = input.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
        if (formattedValue.length > 19) formattedValue = formattedValue.substr(0, 19);
        input.value = formattedValue;
        
         
        const display = document.getElementById('card-num-disp');
        if (display) {
            if (value.length > 0) {
                let maskedValue = value.replace(/./g, '•');
                let displayValue = maskedValue.match(/.{1,4}/g)?.join(' ') || maskedValue;
                display.textContent = displayValue.padEnd(19, '•').substr(0, 19);
            } else {
                display.textContent = '•••• •••• •••• ••••';
            }
        }
    }
    
     
    document.addEventListener('click', function(event) {
        const modal = document.getElementById('payment-modal');
        if (modal && event.target === modal) {
            togglePaymentModal(false);
        }
    });
</script>
</body>
</html>