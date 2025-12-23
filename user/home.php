<?php 
include "../partials/navbar.php";
include "../auth/auth_check.php";
include "../includes/db.php";

$res = mysqli_query($conn, "SELECT * FROM restaurants");
?>




<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Food Delivery — Mock</title>
<style>
  :root{
    --sidebar-w: 220px;
    --accent: #00b26a;
    --muted:#8b8b8b;
    --card-bg:#fff;
    --page-bg:#fafafa;
    --radius:12px;
    --gap:20px;
  }
  *{box-sizing:border-box}
  body{
    margin:0;
    font-family: "Helvetica Neue", Arial, sans-serif;
    background:var(--page-bg);
    color:#111;
  }

  /* LAYOUT */
  .app{
    display:flex;
    min-height:100vh;
  }

  /* SIDEBAR */
  .sidebar{
    margin-top: 25px;
    width:var(--sidebar-w);
    background:#fff;
    border-right:1px solid #e6e6e6;
    padding:20px 14px;
    position:fixed;
    top:0; left:0; bottom:0;
    overflow:auto;
  }
  .brand{
    display:flex;
    align-items:center;
    gap:10px;
    margin-bottom:18px;
  }
  
 a{
    text-decoration: none;
    color: black;
 }

  .side-list{list-style:none;padding:0;margin-top:6px}
  .side-list li{
    display:flex;align-items:center;gap:10px;
    padding:10px 8px;border-radius:8px;color:#222;font-size:15px;cursor:pointer;
  }
  .side-list li:hover{background:#f5f5f5}
  .side-list li .ico{width:28px;height:28px;border-radius:8px;background:#f0f0f0;display:inline-flex;align-items:center;justify-content:center}

  /* MAIN */
  .main{
    margin-top: 25px;
    margin-left:var(--sidebar-w);
    flex:1;
    padding:22px 28px;
    min-width:0;
  }

  /* NAVBAR */
  .topbar{
    background: #fff;
    position:fixed;
    top:0; left:0; right: 0;
    display:flex;
    align-items:center;
    gap:18px;
    margin-bottom:18px;
    padding-bottom: 10px;
    border-bottom: #111;
    
  }
  .hamburger{margin-left: 10px; width:36px;height:36px;border-radius:8px;background:#fff;border:1px solid #e6e6e6;display:flex;align-items:center;justify-content:center;cursor:pointer}
  .logo-sm{font-weight:700;font-size:18px}
  .switch{
    display:inline-flex;border-radius:999px;overflow:hidden;border:1px solid #e6e6e6;
  }
  .switch button{
    padding:8px 14px;border:0;background:transparent;cursor:pointer;font-size:14px;
  }
  .switch .active{background:#111;color:#fff}

  .location{
    padding:8px 12px;border-radius:20px;background:#fff;border:1px solid #eee;font-size:14px;
    display:flex;align-items:center;gap:8px;
  }

  .search{
    margin-left:10px;flex:1;display:flex;align-items:center;
  }
  .search input{
    width:100%;padding:12px 16px;border-radius:30px;border:1px solid #e6e6e6;background:#fff;font-size:15px;
  }

  .actions{display:flex;gap:10px;align-items:center;margin-left:auto}
  .actions .btn{padding:8px 12px;border-radius:20px;border:1px solid #111;background:green;cursor:pointer}
  .actions .cart{display:flex;align-items:center;gap:8px;padding:8px 10px;border-radius:20px;background:green;border:1px solid #e6e6e6}

  .actions :hover {
    cursor: pointer;
  }
  /* CATEGORY STRIP */
  .categories{
    display:flex;gap:18px;align-items:center;padding:16px 0;overflow:auto;
    border-bottom:1px solid #f0f0f0;margin-bottom:18px;
  }
  .cat{
    min-width:92px;text-align:center;font-size:13px;color:#222;cursor:pointer;
  }
  .cat .circle{
    width:64px;height:64px;border-radius:50%;background:#fff;border:1px solid #eee;display:flex;align-items:center;justify-content:center;margin:0 auto 8px;font-size:28px;
    box-shadow:0 1px 2px rgba(0,0,0,0.04)
  }

  .cart {
    color: green;
  }
  /* HERO (two-column) */
  .hero{
    display:grid;grid-template-columns:1fr 340px;gap:20px;align-items:start;margin-top:8px;
  }
  .hero-left{
    background:transparent;padding:6px 2px;
  }
  .kicker{color:var(--muted);font-size:14px;margin-bottom:6px}
  .hero h2{
    font-size:44px;margin:6px 0 12px;line-height:1;
  }
  .hero p{color:var(--muted);margin-bottom:18px}

  .promo{
    background:var(--accent);color:white;border-radius:14px;padding:18px;display:flex;flex-direction:column;gap:12px;align-items:flex-start;
    min-height:120px;
  }
  .promo small{opacity:0.95}
  .promo .cta{background:#fff;color:var(--accent);padding:8px 12px;border-radius:999px;border:none;cursor:pointer;font-weight:600}

  /* SECTION (rows) */
  .section{
    margin-top:26px;
  }
  .section .header{
    display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;
  }
  .section h3{margin:0;font-size:20px}
  .seeall{display:flex;gap:12px;align-items:center;color:var(--muted);font-size:13px}

  /* HORIZONTAL CAROUSEL */
  .row{
    display:flex;gap:14px;overflow:auto;padding-bottom:8px;
  }

  .card{
    width:360px;flex:0 0 360px;background:var(--card-bg);border-radius:12px;border:1px solid #eee;overflow:visible;
    box-shadow:0 6px 18px rgba(10,10,10,0.04);
  }
  .card .img{
    height:140px;background-size:cover;background-position:center;
  }
  .badge{
    position:static;left:0px;top:0px;background:#e22;color:#fff;padding:6px 10px;width: 220px; border-radius:999px;font-size:13px;font-weight:700;box-shadow:0 6px 12px rgba(0,0,0,0.12)
  }
  .card .meta{padding:12px 14px}
  .card .meta h4{margin:0 0 6px;font-size:16px}
  .card .meta .sub{color:var(--muted);font-size:13px}

  /* SMALL CARD (for smaller cards like stores circle) */
  .small{
    width:220px;flex:0 0 220px;background:#fff;border-radius:12px;border:1px solid #eee;padding:12px;display:flex;gap:12px;align-items:center
  }
  .small .logo{width:64px;height:64px;border-radius:50%;background:#f2f2f2;display:flex;align-items:center;justify-content:center;font-weight:700}

  /* circular store list */
  .store-list{display:flex;gap:16px;align-items:center;overflow:auto;padding:8px 0; margin-left: 1px;}
  .store{
    width:88px;flex:0 0 88px;text-align:center;font-size:13px;color:#333; padding-left: 10px;
  }
  .store .slogo{margin-left: 20px; width:80px;height:80px;border-radius:50%;border:6px solid #fff;box-shadow:0 2px 8px rgba(0,0,0,0.06);display:flex;align-items:center;justify-content:center; padding: 45px; background:#e9f7f1;margin:0 auto 6px}


  .circular-container {
    /* Define the size of the circle (Diameter: 160px) */
    width: 130px;
    height: 130px;
    
    /* Make the container a perfect circle (50% of the width/height) */
    border-radius: 50%; 
    
    /* Hide any part of the image that extends beyond the circle */
    overflow: hidden; 
    
    /* Set the border (The "little border" requested) */
    border: 3px solid #f0f0f0; /* Example: light gray border */
    
    /* Optional: Add a subtle shadow for depth */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.circular-image {
    /* Ensure the image fills the container entirely */
    width: 100%;
    height: 100%;
    
    /* CRITICAL STEP: Crops and scales the image to cover the entire container 
       without stretching the image itself. */
    object-fit: cover; 
}

.text-under {
    margin-top: 5px;
    text-align: center;
}

  
  /* Responsive */
  @media (max-width:1000px){
    .hero{grid-template-columns:1fr;gap:12px}
    .card{width:300px;flex:0 0 300px}
    .categories .cat{min-width:82px}
  }

  @media (max-width:700px){
    .sidebar{display:none}
    .main{margin-left:0;padding:14px}
    .card{width:260px;flex:0 0 260px}
  }
</style>
</head>
<body>

<h2>🍽 Restaurants</h2>
<a href="../restaurant/add_resaurant.php">
    🍽️ Add Your Restaurant
</a>

<?php while ($r = mysqli_fetch_assoc($res)): ?>
    <div style="border:1px solid #ccc; padding:10px; margin:10px;">
        <h3><?= $r['name'] ?></h3>
        <p><?= $r['description'] ?></p>
        <a href="restaurant.php?id=<?= $r['id'] ?>">View Menu</a>
    </div>
<?php endwhile; ?>
<?php
$restaurants = mysqli_query($conn, "
    SELECT id, name, description 
    FROM restaurants 
    WHERE status = 'active'
");
?>

<?php while ($r = mysqli_fetch_assoc($restaurants)): ?>
  <div class="restaurant-card">
    <h4><?= htmlspecialchars($r['name']) ?></h4>
    <p><?= htmlspecialchars($r['description']) ?></p>

    <a href="restaurant.php?id=<?= $r['id'] ?>" class="order-btn">
      View Menu
    </a>
  </div>
<?php endwhile; ?>
<div class="app">

  <!-- LEFT SIDEBAR -->
  <aside class="sidebar" aria-hidden="false">

    <ul class="side-list">
      <li><span class="ico">🏠</span> Home</li>
      <li><span class="ico">🍩</span> Convenience</li>
      <li><span class="ico">🍺</span> Alcohol</li>
      <li><span class="ico">🥂</span> Soft Drinks</li>
      <li><span class="ico">🩺</span> Healthy</li>
      <li><span class="ico">🍽️</span> Ethiopian</li>
      <li><span class="ico">🥪</span> Breakfast</li>
      <li><span class="ico">🥘</span> Launch</li>
      <li><span class="ico">🏷️</span> Dinner</li>
      <li><span class="ico">🌮</span> Fast Food</li>
      
    </ul>
  </aside>

  <!-- MAIN -->
  <main class="main">

    <!-- TOP BAR -->
    <div class="topbar">
        <a href="index2.html">
      <div class="hamburger">☰</div> </a>
      <div class="logo-sm">BeU Delivery</div>


      <div class="search">
        <input placeholder="Search Uber Eats" />
      </div>

      <div class="actions">
        <div class="cart">🛒 <span style="font-size:13px;color:var(--muted)">0</span></div>
       
      </div>
    </div>

    <!-- CATEGORY STRIP -->
    <div class="categories" role="list">
      <div class="cat"><div class="circle">🍌</div>Grocery</div>
      <div class="cat"><div class="circle">🍕</div>Pizza</div>
      <div class="cat"><div class="circle">🍜</div>Soup</div>
      <div class="cat"><div class="circle">🍣</div>Sushi</div>
      <div class="cat"><div class="circle">🥗</div>Healthy</div>
      <div class="cat"><div class="circle">🌮</div>Mexican</div>
      <div class="cat"><div class="circle">🍩</div>Bakery</div>
      <div class="cat"><div class="circle">🥟</div>Chinese</div>
    </div>

    <!-- HERO: left big text + right promo -->
    <section class="hero">
      <div class="hero-left">
        
        <h2>Search for a favorite restaurant, cuisine, or dish.</h2>
        <p style="max-width:720px;color:var(--muted)">
          Browse top offers, delivery times, ratings and more. Scroll down to see curated deals and national favorites.
        </p>
      </div>

      <aside class="promo">
        <div style="font-weight:700;font-size:18px">Subway National Cookie Day</div>
        <div>6 free cookies with $25+ • Thru 12/14. T&C apply.</div>
        <button class="cta">Order now</button>
      </aside>
    </section>

    <!-- SECTIONS rows -->
    <section class="section">
      <div class="header">
        <h3>Most loved deals on BeU Delivery</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <a href="Restaurants/kaldis.html">
            <div class="card">
            <div class="img" style="background-image:url('Images/kaldis.jpg')">
                <div class="badge">Save on Select Items</div>
            </div>
            <div class="meta">
                <h4>kaldis Coffee</h4>
                <div class="sub">⭐ 4.8 (10,000+) · 31 min</div>
            </div>
            </div>
        </a>

        <div class="card">
          <div class="img" style="background-image:url('Images/doka.jpg')">
            <div class="badge">Top Offer · 20% off</div>
          </div>
          <div class="meta">
            <h4>Doka Cafe</h4>
            <div class="sub">⭐ 4.6 (1,500+) · 10 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('Images/habesha-2000.jpg')">
            <div class="badge">Top Offer · Spend $40, Save $11</div>
          </div>
          <div class="meta">
            <h4>2000 Habesha</h4>
            <div class="sub">⭐ 4.8 (9,000+) · 25 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('Images/yod.jpg')">
            <div class="badge">Top Offer · Buy 1 Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Yod Abisinia</h4>
            <div class="sub">⭐ 4.7 (5,000+) · 10 min</div>
          </div>
        </div>
      </div>
    </section>

    <!-- BUY 1 GET 1 -->
    <section class="section">
      <div class="header">
        <h3>Buy 1, get 1 free</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1008')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Koya Omakase</h4>
            <div class="sub">⭐ 4.4 (1,000+) · 20 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1024')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Hub Thai</h4>
            <div class="sub">⭐ 4.8 (5,000+) · 25 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1036')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Cap't Loui</h4>
            <div class="sub">⭐ 4.7 (66) · 24 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1048')">
            <div class="badge">Top Offer</div>
          </div>
          <div class="meta">
            <h4>Ootoya</h4>
            <div class="sub">⭐ 4.8 (1,000+) · 20 min</div>
          </div>
        </div>
      </div>
    </section>

    <!-- NATIONAL FAVORITES -->
    <section class="section">
      <div class="header">
        <h3>National favorites</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <div class="card" style="width:460px;flex:0 0 460px">
          <div class="img" style="background-image:url('https://picsum.photos/900/400?image=1025');height:160px">
            <div class="badge">Free Item (Spend $14)</div>
          </div>
          <div class="meta">
            <h4>Domino's</h4>
            <div class="sub">⭐ 4.4 (500+) · 19 min</div>
          </div>
        </div>

        <div class="card" style="width:360px;flex:0 0 360px">
          <div class="img" style="background-image:url('https://picsum.photos/900/400?image=1011')">
            <div class="badge">Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Shake Shack</h4>
            <div class="sub">⭐ 4.6 (7,000+) · 13 min</div>
          </div>
        </div>

        <div class="card" style="width:360px;flex:0 0 360px">
          <div class="img" style="background-image:url('https://picsum.photos/900/400?image=1003')">
            <div class="badge">Spend $25, Save $8</div>
          </div>
          <div class="meta">
            <h4>P.F. Chang's</h4>
            <div class="sub">⭐ 4.1 (1,500+) · 10 min</div>
          </div>
        </div>
      </div>
    </section>

    <!-- STORES NEAR YOU (circular) -->
    <section class="section">
      <div class="header">
        <h3>Stores near you</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="store-list">
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
        <div>
            <div class="circular-container">
                <img src="rest1.avif" class="circular-image">
            </div>
            <div class="text-under">Wegmans<br><small style="color:var(--muted)">31 min</small></div>
        </div>
      </div>
    </section>

    <!-- BUY 1 GET 1 -->
    <section class="section">
      <div class="header">
        <h3>Buy 1, get 1 free</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1008')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Koya Omakase</h4>
            <div class="sub">⭐ 4.4 (1,000+) · 20 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1024')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Hub Thai</h4>
            <div class="sub">⭐ 4.8 (5,000+) · 25 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1036')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Cap't Loui</h4>
            <div class="sub">⭐ 4.7 (66) · 24 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1048')">
            <div class="badge">Top Offer</div>
          </div>
          <div class="meta">
            <h4>Ootoya</h4>
            <div class="sub">⭐ 4.8 (1,000+) · 20 min</div>
          </div>
        </div>
      </div>
    </section>

    <!-- BUY 1 GET 1 -->
    <section class="section">
      <div class="header">
        <h3>Buy 1, get 1 free</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1008')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Koya Omakase</h4>
            <div class="sub">⭐ 4.4 (1,000+) · 20 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1024')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Hub Thai</h4>
            <div class="sub">⭐ 4.8 (5,000+) · 25 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1036')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Cap't Loui</h4>
            <div class="sub">⭐ 4.7 (66) · 24 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1048')">
            <div class="badge">Top Offer</div>
          </div>
          <div class="meta">
            <h4>Ootoya</h4>
            <div class="sub">⭐ 4.8 (1,000+) · 20 min</div>
          </div>
        </div>
      </div>
    </section>

    <!-- BUY 1 GET 1 -->
    <section class="section">
      <div class="header">
        <h3>Buy 1, get 1 free</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1008')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Koya Omakase</h4>
            <div class="sub">⭐ 4.4 (1,000+) · 20 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1024')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Hub Thai</h4>
            <div class="sub">⭐ 4.8 (5,000+) · 25 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1036')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Cap't Loui</h4>
            <div class="sub">⭐ 4.7 (66) · 24 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1048')">
            <div class="badge">Top Offer</div>
          </div>
          <div class="meta">
            <h4>Ootoya</h4>
            <div class="sub">⭐ 4.8 (1,000+) · 20 min</div>
          </div>
        </div>
      </div>
    </section>

    <!-- BUY 1 GET 1 -->
    <section class="section">
      <div class="header">
        <h3>Buy 1, get 1 free</h3>
        <div class="seeall">See all ➜</div>
      </div>

      <div class="row">
        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1008')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Koya Omakase</h4>
            <div class="sub">⭐ 4.4 (1,000+) · 20 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1024')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Hub Thai</h4>
            <div class="sub">⭐ 4.8 (5,000+) · 25 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1036')">
            <div class="badge">Top Offer · Buy 1, Get 1 Free</div>
          </div>
          <div class="meta">
            <h4>Cap't Loui</h4>
            <div class="sub">⭐ 4.7 (66) · 24 min</div>
          </div>
        </div>

        <div class="card">
          <div class="img" style="background-image:url('https://picsum.photos/640/400?image=1048')">
            <div class="badge">Top Offer</div>
          </div>
          <div class="meta">
            <h4>Ootoya</h4>
            <div class="sub">⭐ 4.8 (1,000+) · 20 min</div>
          </div>
        </div>
      </div>
    </section>
  </main>
</div>
</body>
</html>




