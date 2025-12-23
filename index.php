<?php
// landing page (no logic yet)
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uber Eats Landing Page Clone</title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
      /* Basic Reset and Variables */
:root {
    --uber-black: #000000;
    --uber-green: #06C167;
    --text-color: #262626;
    --light-grey: #f6f6f6;
    --dark-grey-footer: #333333;
}

body {
    font-family: 'Uber Move Text', Arial, sans-serif;
    margin: 0;
    padding: 0;
    color: var(--text-color);
    background-color: #ffffff;
}

.for-background {
  background-image: url('images/Gemini_Generated_Image_h3yu5kh3yu5kh3yu.png');
    background-size: cover;
    background-position: center;
    height: 100vh;
    margin-bottom: 60px;
}

h1, h2, h3 {
    font-weight: 700;
    margin-top: 0;
}

a {
    text-decoration: none;
    color: var(--text-color);
}

.content-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* --- 1. Header Navigation --- */


.header-content {
    display: flex;
    justify-content: space-between;
    
    padding: 15px 20px;
    margin-left: 20px;
    margin-right: 20px;
}

.header-left {
    
    display: flex;
    gap: 15px;
}


.menu-button {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: var(--text-color);
}

.menuu {
  margin-top: 6px;
}

.logo {
    font-size: 1.5em;
    font-weight: bold;
}

.delivery-address-bar {
    display: flex;
    align-items: center;
    background-color: var(--light-grey);
    padding: 8px 15px;
    border-radius: 20px;
    width: 250px;
}

.delivery-address-bar input {
    border: none;
    background: none;
    outline: none;
    margin-left: 8px;
    width: 100%;
}

.delivery-address-bar .icon {
    font-size: 14px;
    color: #666;
}


.header-right a {
  margin-top: 10px;
    margin-left: 10px;
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: 500;
    transition: background-color 0.2s;
}

.btn-primary {
    background-color: var(--uber-black);
    color: #fff;
}
.btn-primary:hover {
    background-color: #333;
}

.btn-secondary {
    background-color: #fff;
    border: 1px solid #eee;
}
.btn-secondary:hover {
    background-color: var(--light-brown);
}

/* --- 2. Hero Section --- */
.hero-section {
    /* Random Food Image for background */
    
    display: block;
    align-items: center;
    padding: 0 10%;
    margin-bottom: 50px;
    margin-top: 310px;
    width: 1800;
}

.hero-content {
  
    color: var(--uber-black);
    max-width: 700px;
}

.hero-content h1 {
    font-size: 3em;
    margin-bottom: 25px;
}

.search-module {
    background: none;
    padding: 0px;
    border-radius: 8px;
    
}

.search-inputs {
    display: flex;
    gap: 8px;
}

.search-inputs input {
    flex-grow: 1;
    padding: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
}


.delivery-time-select .fa-caret-down {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: #666;
}

.search-btn {
    background-color: var(--uber-black);
    color: #fff;
    padding: 15px 25px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    cursor: pointer;
    transition: background-color 0.2s;
}

/* --- 3. Feature/Callout Cards --- */
.callout-cards-section {
    padding: 20px 0;
   
}

.callout-cards-container {
    max-width: 1700px;
    
    margin: auto;
    padding: 0 20px;
    display: flex;
    gap: 20px;
    justify-content: space-between;
}

.card {
  margin-top: 30px;
  
    flex: 1;
    border-radius: 8px;
    overflow: hidden;
    position: relative;
    color: var(--text-color);
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.card img {
    width: 100%;
    height: 290px; /* Reduced height to match screenshot */
    object-fit: cover;
}

.card-text {
    padding: 15px;
}

.card h3 {
    font-size: 1.2em;
    margin-bottom: 5px;
}

.card p {
    font-size: 0.9em;
    color: var(--uber-green);
    margin: 0;
}

.card:hover {
    padding-top: 3px;
    padding-left: 4px;
    border: #000000;
}

/* --- 4. Cities Map Section --- */
.cities-map-section {
    padding: 50px 0 30px;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.section-header h2 {
    font-size: 2em;
}

.section-header .view-all {
    color: var(--uber-green);
    font-weight: 500;
}

.map-placeholder {
    height: 350px;
    background-color: #f0f8ff; /* Light blue map background */
    background-image: url('images/map.jpg'); /* Placeholder map image */
    background-size: cover;
    background-position: center;
    border-radius: 8px;
    margin-bottom: 40px;
    border: 1px solid #eee;
}

.city-grid, .country-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 15px;
}

.city-column, .country-column {
    list-style: none;
    padding: 0;
    margin: 0;
}

.city-column li, .country-column li {
    margin-bottom: 15px;
}

.city-column a, .country-column a {
    font-size: 0.9em;
    color: #666;
}

.city-column a:hover, .country-column a:hover {
    color: var(--text-color);
}



.section-divider {
    border: none;
    border-top: 1px solid #eee;
    margin: 40px 0;
}

/* Footer specific styling */
footer {
    padding-top: 20px;
}

.footer-top {
    display: flex;
    justify-content: space-betweStoresen;
    align-items: center;
    margin-bottom: 30px;
}

.footer-logo {
    font-size: 1.8em;
}

.app-downloads img {
    height: 35px;
    margin-right: 10px;
}

.footer-grid-links {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    padding-bottom: 50px;
}

.footer-column-links {
    list-style: none;
    padding: 0;
    margin-left: 40px;
    grid-column: span 2; /* Make columns wider */
}

.footer-column-links:last-child {
    grid-column: span 2;
}

.footer-column-links li {
    margin-bottom: 12px;
}

.footer-column-links a {
    font-size: 0.85em;
    color: #666;
    display: flex;
    align-items: center;
}

.footer-column-links a:hover {
    color: var(--text-color);
}

.footer-column-links .fa-globe {
    margin-right: 5px;
}
    </style>
</head>
<body>
  <section class="for-background">
    <header>
        <div class="header-content">
            <div class="header-left">
                <a href="index2.html">
                  <div class="menuu">
                    <button class="menu-button"><i class="fas fa-bars"></i></button>
                  </div>
                </a>
                <a href="index.html"><div class="logo">BeU Delivery</div></a>
                <div class="delivery-address-bar">
                    <span class="icon"><i class="fas fa-location-dot"></i></span>
                    <input type="text" placeholder="Enter delivery address">
                </div>
            </div>
            <div class="header-right">
                <a href="SignUp/signup.html" class="btn-primary">Sign up</a>
                <a href="Login/login.html" class="btn-secondary">Log in</a>
            </div>
        </div>
    </header>

    <section class="hero-section">
        <div class="hero-content">
            <h1>Order delivery near you</h1>
            <div class="search-module">
                <div class="search-inputs">
                    <input type="text" placeholder="Enter delivery address">
                    
                    <button class="search-btn">Search here</button>
                </div>
            </div>
        </div>
    </section>

  </section>

    <section class="callout-cards-section">
        <div class="callout-cards-container">
            <a href="#" class="card">
                <img src="images/feed.jpg" alt="Person working on laptop and eating salad">
                <div class="card-text">
                    <h3>Feed your employees</h3>
                    <p>Create a business account</p>
                </div>
            </a>
            <a href="Add restaurant.html/getStarted.html" class="card">
                <img src="images/restaurant.jpg" alt="Chef cooking on a stove">
                <div class="card-text">
                    <h3>Your restaurant, delivered</h3>
                    <p>Add your restaurant</p>
                </div>
            </a>
            <a href="#" class="card">
                <img src="images/delivery.jpg" alt="Delivery driver on a bike">
                <div class="card-text">
                    <h3>Deliver with Uber Eats</h3>
                    <p>Sign up to deliver</p>
                </div>
            </a>
        </div>
    </section>

    <section class="cities-map-section">
        <div class="content-container">
            <div class="section-header">
                <h2>Cities near me</h2>
                <a href="#" class="view-all">View all 50+ cities</a>
            </div>
            <div class="map-placeholder">
                            </div>

            <div class="city-grid">
                <ul class="city-column">
                    <li><a href="#">Addis Ababa</a></li>
                    <li><a href="#">Adama</a></li>
                    <li><a href="#">Jimma</a></li>
                    <li><a href="#">Dese</a></li>
                    <li><a href="#">Hawassa</a></li>
                    <li><a href="#">Asosa</a></li>
                </ul>
                <ul class="city-column">
                    <li><a href="#">Arba Minch</a></li>
                    <li><a href="#">Debre Birhan</a></li>
                    <li><a href="#">Kombolcha</a></li>
                    <li><a href="#">Boditi</a></li>
                    <li><a href="#">Fiche</a></li>
                    <li><a href="#">Finote Selam</a></li>
                </ul>
                <ul class="city-column">
                    <li><a href="#">Gondar</a></li>
                    <li><a href="#">Dire Dawa</a></li>
                    <li><a href="#">Jijiga</a></li>
                    <li><a href="#">Mekele</a></li>
                    <li><a href="#">Bahir Dar</a></li>
                    <li><a href="#">Shashemene </a></li>
                </ul>
                <ul class="city-column">
                    <li><a href="#">Aksum</a></li>
                    <li><a href="#">Bishoftu</a></li>
                    <li><a href="#">Harar</a></li>
                    <li><a href="#">Soddo</a></li>
                    <li><a href="#">Hosaina</a></li>
                    <li><a href="#">Adigrat</a></li>
                </ul>
            </div>
        </div>
    </section>
    
    <hr class="section-divider">

   

            <footer>
                <div class="footer-top">
                    <h2 class="footer-logo">BeU Delivery</h2>
                    <div class="app-downloads">
                        <a href="#"><img src="https://d3i4yxtzktqr9z.cloudfront.net/uber-eats/97c6c747d3329f62c8e3489853904557.svg" alt="Download on the App Store"></a>
                        <a href="#"><img src="https://d3i4yxtzktqr9z.cloudfront.net/uber-eats/382d6194b91739c636e1b2f4f1073167.svg" alt="Get it on Google Play"></a>
                    </div>
                </div>

                <div class="footer-grid-links">
                    <ul class="footer-column-links">
                        <li><a href="#">Get Help</a></li>
                        <li><a href="#">Buy gift cards</a></li>
                        <li><a href="#">Add your restaurant</a></li>
                        <li><a href="#">Sign up to deliver</a></li>
                        <li><a href="#">Create a business account</a></li>
                    </ul>
                    <ul class="footer-column-links">
                        <li><a href="#">Restaurants near me</a></li>
                        <li><a href="#">View all cities</a></li>
                        <li><a href="#">View all countries</a></li>
                        <li><a href="#">Pickup near me</a></li>
                        <li><a href="#">About BeU Delivery</a></li>
                        <li><a href="#">Shop groceries</a></li>
                        <li><a href="#"><i class="fas fa-globe"></i> English</a></li>
                    </ul>
                </div>
            </footer>
        </div>
    </section>

</body>
</html>