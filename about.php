<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$pageTitle = 'About Us - BighatMeal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        :root {
            --primary: #28a745;
            --primary-light: #34ce57;
            --secondary: #ff6b6b;
            --dark: #2c3e50;
            --light: #f8f9fa;
            --gradient: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            color: #333;
            line-height: 1.7;
            background-color: #fff;
            overflow-x: hidden;
            padding-top: 80px;
        }
        
        /* Typography */
        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
            color: var(--dark);
        }
        
        h1 { font-size: 3.5rem; }
        h2 { font-size: 2.5rem; margin-bottom: 1.5rem; }
        h3 { font-size: 1.8rem; margin-bottom: 1.2rem; }
        
        /* Buttons */
        .btn {
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            z-index: 1;
            border: none;
        }
        
        .btn-success {
            background: var(--gradient);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
        }
        
        .btn-outline-success {
            border: 2px solid var(--primary);
            color: var(--primary);
            background: transparent;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .btn-outline-success:hover {
            background: var(--primary);
            color: white;
        }
        
        /* Hero Section */
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), 
                        url('https://images.unsplash.com/photo-1504674900247-087703934569?ixlib=rb-4.0.3&auto=format&fit=crop&w=1350&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 120px 0;
            position: relative;
            overflow: hidden;
        }
        
        .hero-section h1 {
            font-size: 4rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .hero-section .lead {
            font-size: 1.5rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        /* Cards */
        .card {
            border: none;
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            background: white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            margin-bottom: 30px;
            position: relative;
            z-index: 1;
        }
        
        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--gradient);
            transform: scaleX(0);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }
        
        .card:hover::before {
            transform: scaleX(1);
        }
        
        .card-body {
            padding: 2rem;
        }
        
        .card-title {
            color: var(--primary);
            font-weight: 700;
            margin-bottom: 1rem;
            font-size: 1.4rem;
        }
        
        /* Feature Icons */
        .feature-icon {
            font-size: 2.5rem;
            background: var(--gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1.5rem;
            display: inline-block;
        }
        
        /* Mission & Vision */
        .mission-vision .card {
            border: 2px solid transparent;
            background: linear-gradient(white, white) padding-box,
                        var(--gradient) border-box;
            border-radius: 15px;
        }
        
        /* Join Section */
        .join-section {
            background: var(--gradient);
            color: white;
            padding: 5rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .join-section::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            transform: rotate(30deg);
            pointer-events: none;
        }
        
        .join-section h2 {
            color: white;
            margin-bottom: 1.5rem;
        }
        
        .join-section .lead {
            font-size: 1.3rem;
            opacity: 0.9;
            margin-bottom: 2rem;
        }
        
        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate {
            animation: fadeInUp 0.8s ease-out forwards;
            opacity: 0;
        }
        
        .delay-1 { animation-delay: 0.2s; }
        .delay-2 { animation-delay: 0.4s; }
        .delay-3 { animation-delay: 0.6s; }
        .delay-4 { animation-delay: 0.8s; }
        
        /* Responsive */
        @media (max-width: 768px) {
            h1 { font-size: 2.5rem; }
            h2 { font-size: 2rem; }
            
            .hero-section {
                padding: 80px 0;
                text-align: center;
            }
            
            .hero-section h1 {
                font-size: 2.5rem;
            }
            
            .btn {
                width: 100%;
                margin-bottom: 10px;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="animate">About BighatMeal</h1>
                    <p class="lead animate delay-1">Empowering you to eat better with fully customizable, nutritious meals.</p>
                    <div class="mt-4 animate delay-2">
                        <a href="menu.php" class="btn btn-light btn-lg me-3">View Menu</a>
                        <a href="customize-meal.php" class="btn btn-outline-light btn-lg">Customize Meal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-5 py-5">
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <section class="mb-5 animate">
                    <h2 class="text-center mb-5">Our <span class="text-success">Story</span></h2>
                    <p>BighatMeal was born out of a simple idea — to make healthy eating easy, accessible, and personalized for everyone. As health-conscious individuals, we noticed how difficult it was to customize meals according to one's specific nutrition needs like calories, protein, carbs, and more — especially when ordering online.</p>
                    <p>We started as a small team passionate about food, fitness, and technology. Over time, we developed a platform that lets you create your own custom meals from a wide range of fresh fruits and vegetables, while tracking their nutritional value in real-time.</p>
                    <p>Today, we're proud to be serving individuals who want control over what they eat — whether they're fitness enthusiasts, patients with dietary restrictions, or just people who care about what goes on their plate.</p>
                </section>

                <section class="mb-5 py-5">
                    <h2 class="text-center mb-5">🌟 What We <span class="text-success">Offer</span></h2>
                    <div class="row g-4 mb-4">
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-success">Customizable Meals</h5>
                                    <p class="card-text">Select from 30+ fresh fruits and vegetables. Choose your portion size, and get an instant breakdown of calories, protein, carbs, fat, fiber, and sugar.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-success">Transparent Nutrition Info</h5>
                                    <p class="card-text">Every item in our menu includes nutritional values based on 100g, so you always know what you're consuming.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-success">Simple & Smart Cart</h5>
                                    <p class="card-text">The cart dynamically updates your total nutritional values and pricing — making meal planning smarter and simpler.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <h5 class="card-title text-success">Affordable & Healthy</h5>
                                    <p class="card-text">We ensure competitive prices with no compromise on quality. You build the meal, we handle the rest.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5 py-5 mission-vision">
                    <div class="row">
                        <div class="col-md-6 mb-4 animate">
                            <div class="card h-100 border-success">
                                <div class="card-body">
                                    <h3 class="h4 text-success">🎯 Our Mission</h3>
                                    <p>To empower people to eat better by giving them the freedom to design their own meals, with full transparency and health data — because food isn't one-size-fits-all.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100 border-success">
                                <div class="card-body">
                                    <h3 class="h4 text-success">🚀 Our Vision</h3>
                                    <p>We aim to become India's most trusted custom-meal platform — helping people move away from fast food and toward conscious eating that fits their health, taste, and budget.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                </div>
                </div>
            </div>
        </div>
    </div>
    
    <section class="join-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h2 class="mb-4">🙌 Join the BighatMeal Family</h2>
                    <p class="lead mb-4">Whether you're looking to eat clean, track your macros, or simply enjoy fresh, nutritious meals — BighatMeal is here to support your journey.</p>
                    <p class="h5 text-muted">One click, one bowl, one healthy choice at a time.</p>
                </section>

                <div class="row g-4 mb-5">
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon">
                                    <i class="bi bi-egg-fried"></i>
                                </div>
                                <h5 class="card-title text-success">Customizable Meals</h5>
                                <p class="card-text">Select from 30+ fresh ingredients and customize your perfect meal.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="feature-icon">
                                    <i class="bi bi-graph-up"></i>
                                </div>
                                <h5 class="card-title text-success">Track Nutrition</h5>
                                <p class="card-text">Know exactly what you're eating with our detailed nutrition information.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center py-4">
                    <a href="menu.php" class="btn btn-success btn-lg px-4 me-2">View Our Menu</a>
                    <a href="customize-meal.php" class="btn btn-outline-success btn-lg px-4">Customize Your Meal</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
