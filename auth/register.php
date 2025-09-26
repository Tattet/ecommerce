<?php
require_once '../include/config.php';

// If user is already logged in, redirect them to the main index page
if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Input Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password) || $password !== $confirm_password || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 6) {
        $error = "Please check your inputs: make sure passwords match and are at least 6 characters long.";
    } else {
        // Check for Existing User
        $sql = "SELECT user_id FROM users WHERE email = :email";
        if ($stmt = $pdo->prepare($sql)) {
            $stmt->bindParam(":email", $email, PDO::PARAM_STR);
            if ($stmt->execute() && $stmt->rowCount() == 1) {
                $error = "This email is already registered.";
            } else {
                // Hash Password and Insert
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql_insert = "INSERT INTO users (full_name, email, password_hash) VALUES (:full_name, :email, :password_hash)";
                
                if ($stmt_insert = $pdo->prepare($sql_insert)) {
                    $stmt_insert->bindParam(":full_name", $full_name, PDO::PARAM_STR);
                    $stmt_insert->bindParam(":email", $email, PDO::PARAM_STR);
                    $stmt_insert->bindParam(":password_hash", $hashed_password, PDO::PARAM_STR);
                    
                    if ($stmt_insert->execute()) {
                        $success = "Registration successful! Redirecting to login...";
                        header("refresh:3; url=login.php"); 
                    } else {
                        $error = "A database error occurred during registration.";
                    }
                    unset($stmt_insert);
                }
            }
            unset($stmt);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <style>
        /* ----------------------------------------------------------------
           1. BASE STYLES & LAYOUT
           ---------------------------------------------------------------- */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center; 
            align-items: center; 
            padding: 20px;
        }

        /* ----------------------------------------------------------------
           2. AUTH CONTAINER (The main box for the form)
           ---------------------------------------------------------------- */
        .auth-container {
            background: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 30px;
            font-size: 1.8em;
            font-weight: 700;
        }

        /* ----------------------------------------------------------------
           3. FORM ELEMENTS
           ---------------------------------------------------------------- */
        form div {
            margin-bottom: 20px;
        }

        label {
            display: block; 
            margin-bottom: 8px;
            font-weight: 600;
            color: #555;
            font-size: 0.95em;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
            background-color: #f9f9f9;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.2);
            background-color: #fff;
            outline: none;
        }

        input[type="submit"] {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 14px 25px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1em;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: background-color 0.3s ease, transform 0.1s;
            margin-top: 10px;
        }

        input[type="submit"]:hover {
            background-color: #0056b3; 
        }

        input[type="submit"]:active {
            transform: translateY(1px);
        }

        /* ----------------------------------------------------------------
           4. MESSAGES AND LINKS
           ---------------------------------------------------------------- */
        .error, .success {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: 500;
        }

        .error {
            color: #842029;
            background-color: #f8d7da;
            border: 1px solid #f5c2c7;
        }

        .success {
            color: #0f5132;
            background-color: #d1e7dd;
            border: 1px solid #badbcc;
        }

        .auth-container p {
            text-align: center;
            margin-top: 25px;
            font-size: 0.9em;
        }

        a {
            color: #007bff;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.2s;
        }

        a:hover {
            color: #0056b3;
            text-decoration: underline;
        }

        /* ----------------------------------------------------------------
           5. RESPONSIVENESS
           ---------------------------------------------------------------- */
        @media (max-width: 480px) {
            .auth-container {
                padding: 30px 20px;
                margin: 10px;
            }
            h2 {
                font-size: 1.6em;
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <h2>Customer Registration</h2>
        <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
        <?php if (!empty($success)) echo "<p class='success'>$success</p>"; ?>
        
        <form action="" method="post">
            <div><label>Full Name</label><input type="text" name="full_name" required></div>
            <div><label>Email</label><input type="email" name="email" required></div>    
            <div><label>Password</label><input type="password" name="password" required></div>
            <div><label>Confirm Password</label><input type="password" name="confirm_password" required></div>
            <div><input type="submit" value="Register"></div>
            <p>Already have an account? <a href="login.php">Login here</a>.</p>
        </form>
    </div>
</body>
</html>
