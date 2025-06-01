<?php
// Start session and handle database connection
require_once 'config.php';

// Handle order saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_order'])) {
    $reference = $_POST['reference'];
    $network = $_POST['network'];
    $phone = $_POST['phone'];
    $bundle = $_POST['bundle'];
    $price = $_POST['price'];
    
    $stmt = $conn->prepare("INSERT INTO orders (reference, network, phone, bundle, price) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssd", $reference, $network, $phone, $bundle, $price);
    $stmt->execute();
    
    // Store in session for success page
    $_SESSION['last_order'] = $reference;
    $_SESSION['delivery_phone'] = $phone;
    
    header("Location: index.php?success=true");
    exit;
}

// If order is completed, show success message
if (isset($_GET['success']) && isset($_SESSION['last_order'])) {
    $order_ref = $_SESSION['last_order'];
    $phone = $_SESSION['delivery_phone'] ?? '';
    unset($_SESSION['last_order']);
    unset($_SESSION['delivery_phone']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Bundle Sales</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://js.paystack.co/v1/inline.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    
    body {
      background: linear-gradient(135deg, #1a2a6c, #b21f1f, #1a2a6c);
      color: #fff;
      min-height: 100vh;
      padding: 20px;
    }
    
    .container {
      max-width: 1200px;
      margin: 0 auto;
    }
    
    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 20px 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      margin-bottom: 30px;
    }
    
    .logo {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 1.8rem;
      font-weight: bold;
    }
    
    .logo i {
      color: #4CAF50;
    }
    
    nav ul {
      display: flex;
      list-style: none;
      gap: 20px;
    }
    
    nav a {
      color: #fff;
      text-decoration: none;
      padding: 8px 15px;
      border-radius: 5px;
      transition: all 0.3s;
    }
    
    nav a:hover {
      background: rgba(255,255,255,0.1);
    }
    
    .card {
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      border-radius: 15px;
      padding: 25px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
      margin-bottom: 30px;
    }
    
    .main-content {
      display: grid;
      grid-template-columns: 1fr;
      gap: 25px;
    }
    
    @media (min-width: 768px) {
      .main-content {
        grid-template-columns: 1fr 1fr;
      }
    }
    
    .form-group {
      margin-bottom: 20px;
    }
    
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      font-size: 1.1rem;
    }
    
    .form-group input, 
    .form-group select {
      width: 100%;
      padding: 14px;
      border-radius: 8px;
      border: none;
      background: rgba(255, 255, 255, 0.15);
      color: white;
      font-size: 1rem;
    }
    
    .form-group input:focus, 
    .form-group select:focus {
      outline: 2px solid #4CAF50;
      background: rgba(255, 255, 255, 0.2);
    }
    
    .submit-btn {
      background: linear-gradient(to right, #4CAF50, #2E7D32);
      color: white;
      border: none;
      padding: 16px;
      border-radius: 8px;
      cursor: pointer;
      width: 100%;
      font-size: 1.1rem;
      font-weight: bold;
      transition: all 0.3s;
      margin-top: 10px;
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    }
    
    .submit-btn:hover {
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(0,0,0,0.3);
    }
    
    .submit-btn:active {
      transform: translateY(0);
    }
    
    .network-buttons {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 12px;
      margin-bottom: 20px;
    }
    
    @media (min-width: 480px) {
      .network-buttons {
        grid-template-columns: repeat(3, 1fr);
      }
    }
    
    .network-btn {
      padding: 14px 10px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: bold;
      transition: all 0.3s;
      font-size: 0.9rem;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      gap: 5px;
    }
    
    .mtn { background: #FFC107; color: #000; }
    .airteltigo { background: #E91E63; color: white; }
    .telecel { background: #2196F3; color: white; }
    
    .active-network {
      transform: scale(1.05);
      box-shadow: 0 0 15px rgba(255,255,255,0.5);
    }
    
    .success-message {
      background: rgba(40, 167, 69, 0.3);
      padding: 25px;
      border-radius: 10px;
      margin-bottom: 30px;
      text-align: center;
      border: 2px solid #28a745;
    }
    
    .bundle-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
      gap: 15px;
      margin-top: 15px;
    }
    
    .bundle-option {
      background: rgba(255,255,255,0.1);
      border-radius: 8px;
      padding: 15px;
      text-align: center;
      cursor: pointer;
      transition: all 0.3s;
    }
    
    .bundle-option:hover {
      background: rgba(255,255,255,0.2);
      transform: translateY(-5px);
    }
    
    .bundle-option.active {
      background: #4CAF50;
      transform: scale(1.05);
    }
    
    .bundle-amount {
      font-size: 1.3rem;
      font-weight: bold;
      margin-bottom: 5px;
    }
    
    .bundle-price {
      color: #FFC107;
      font-weight: 600;
    }
    
    footer {
      text-align: center;
      padding: 30px 0;
      margin-top: 50px;
      border-top: 1px solid rgba(255,255,255,0.1);
      font-size: 0.9rem;
      color: rgba(255,255,255,0.7);
    }
  </style>
</head>
<body>
  <div class="container">
    <header>
      <div class="logo">
        <i class="fas fa-mobile-alt"></i>
        <span>DataBundleGH</span>
      </div>
      <nav>
        <ul>
          <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
          <li><a href="login.php"><i class="fas fa-tachometer-alt"></i> Admin</a></li>
        </ul>
      </nav>
    </header>
    
    <?php if (isset($_GET['success']) && isset($order_ref)): ?>
    <div class="success-message">
      <h3><i class="fas fa-check-circle"></i> Order Successful!</h3>
      <p>Your data bundle will be delivered to <?= htmlspecialchars($phone) ?> shortly.</p>
      <p>Transaction Reference: <?= htmlspecialchars($order_ref) ?></p>
      <p>Thank you for your purchase!</p>
    </div>
    <?php endif; ?>
    
    <div class="main-content">
      <div class="card">
        <h2><i class="fas fa-mobile-alt"></i> Buy Data Bundle</h2>
        <p class="subtitle">Delivery time is between 20mins to 40mins after payment</p>
        
        <div class="form-group">
          <label>Select Network</label>
          <div class="network-buttons">
            <button type="button" class="network-btn mtn active-network" data-network="mtn">
              <i class="fas fa-signal"></i> MTN
            </button>
            <button type="button" class="network-btn airteltigo" data-network="airteltigo">
              <i class="fas fa-signal"></i> AirtelTigo
            </button>
            <button type="button" class="network-btn telecel" data-network="telecel">
              <i class="fas fa-signal"></i> Telecel
            </button>
          </div>
          <input type="hidden" name="network" id="network" value="mtn">
        </div>
        
        <div class="form-group">
          <label for="phone">Beneficiary Phone Number</label>
          <input type="tel" id="phone" name="phone" placeholder="e.g. 0551234567" required>
        </div>
        
        <div class="form-group">
          <label>Select Data Bundle</label>
          <div class="bundle-grid">
            <div class="bundle-option" data-bundle="1GB">
              <div class="bundle-amount">1GB</div>
              <div class="bundle-price">GH₵ 5.80</div>
            </div>
            <div class="bundle-option" data-bundle="2GB">
              <div class="bundle-amount">2GB</div>
              <div class="bundle-price">GH₵ 10.80</div>
            </div>
            <div class="bundle-option" data-bundle="3GB">
              <div class="bundle-amount">3GB</div>
              <div class="bundle-price">GH₵ 15.80</div>
            </div>
            <div class="bundle-option" data-bundle="4GB">
              <div class="bundle-amount">4GB</div>
              <div class="bundle-price">GH₵ 20.80</div>
            </div>
            <div class="bundle-option" data-bundle="5GB">
              <div class="bundle-amount">5GB</div>
              <div class="bundle-price">GH₵ 25.80</div>
            </div>
            <div class="bundle-option" data-bundle="6GB">
              <div class="bundle-amount">6GB</div>
              <div class="bundle-price">GH₵ 30.80</div>
            </div>
            <div class="bundle-option" data-bundle="7GB">
              <div class="bundle-amount">7GB</div>
              <div class="bundle-price">GH₵ 35.80</div>
            </div>
            <div class="bundle-option" data-bundle="8GB">
              <div class="bundle-amount">8GB</div>
              <div class="bundle-price">GH₵ 40.80</div>
            </div>
            <div class="bundle-option" data-bundle="10GB">
              <div class="bundle-amount">10GB</div>
              <div class="bundle-price">GH₵ 46.80</div>
            </div>
            <div class="bundle-option" data-bundle="15GB">
              <div class="bundle-amount">15GB</div>
              <div class="bundle-price">GH₵ 69.70</div>
            </div>
            <div class="bundle-option" data-bundle="20GB">
              <div class="bundle-amount">20GB</div>
              <div class="bundle-price">GH₵ 90.00</div>
            </div>
            <div class="bundle-option" data-bundle="25GB">
              <div class="bundle-amount">25GB</div>
              <div class="bundle-price">GH₵ 109.80</div>
            </div>

            <div class="bundle-option" data-bundle="30GB">
              <div class="bundle-amount">30GB</div>
              <div class="bundle-price">GH₵ 129.00</div>
            </div>

            <div class="bundle-option" data-bundle="40GB">
              <div class="bundle-amount">40GB</div>
              <div class="bundle-price">GH₵ 174.00</div>
            </div>

            <div class="bundle-option" data-bundle="50GB">
              <div class="bundle-amount">50GB</div>
              <div class="bundle-price">GH₵ 212.00</div>
            </div>
          </div>
          <input type="hidden" id="bundle" name="bundle" value="">
          <input type="hidden" id="price" name="price" value="">
        </div>
        
        <button type="button" class="submit-btn" onclick="payWithPaystack()">
          <i class="fas fa-bolt"></i> Buy Now
        </button>
      </div>
      
      <div class="card">
        <h2><i class="fas fa-info-circle"></i> How It Works</h2>
        <ol style="padding-left: 20px; margin-top: 15px; line-height: 2;">
          <li>Select your mobile network</li>
          <li>Enter your phone number</li>
          <li>Choose your data bundle</li>
          <li>Buy Now</li>
          <li>Receive data between 20min to 40mins time after payment</li>
        </ol>
        
        <h3 style="margin-top: 25px;"><i class="fas fa-shield-alt"></i> Secure Payment</h3>
        <p>All payments are processed through secure payment gateway.</p>
        <p>We never store your payment details.</p>
        
        <h3 style="margin-top: 25px;"><i class="fas fa-headset"></i> Support</h3>
        <p>Having issues? Contact our support team:</p>
        <p><i class="fas fa-phone"></i> 0551668735 </p>
        <p><i class="fas fa-envelope"></i> support@buyalldatagh.com</p>
      </div>
    </div>
    
    <footer>
      <p>&copy; 2025 DataBundleGH. All rights reserved.</p>
      <p>Instant data bundle delivery for MTN, AirtelTigo, and Telecel customers</p>
    </footer>
  </div>

  <script>
  // Define pricing for each network and bundle
  const networkPricing = {
    mtn: {
      "1GB": 5.80,
      "2GB": 10.80,
      "3GB": 15.80,
      "4GB": 20.80,
      "5GB": 25.80,
      "6GB": 29.00,
      "7GB": 35.00,
      "8GB": 38.00,
      "10GB": 45.00,
      "15GB": 68.00,
      "20GB": 90.00,
      "25GB": 109.80,
      "30GB": 129.00,
      "40GB": 174.50,
      "50GB": 212.00
    },
    airteltigo: {
      "1GB": 7.00,
      "2GB": 11.00,
      "3GB": 16.00,
      "4GB": 20.00,
      "5GB": 25.00,
      "6GB": 29.00,
      "7GB": 35.00,
      "8GB": 38.00,
      "10GB": 45.00,
      "15GB": 68.00,
      "20GB": 90.00,
      "25GB": 110.00,
      "30GB": 130.00,

      
      
    },
    telecel: {
      "5GB": 25.00,
      "10GB": 47.00,
      "15GB": 65.00,
      "20GB": 85.00,
      "25GB": 115.00,
      "30GB": 124.80,
      "40GB": 177.30,
      "50GB": 198.50
    }
  };

  // Initialize with MTN prices when page loads
  document.addEventListener('DOMContentLoaded', function() {
    updateBundlePrices('mtn');
  });

  // Network selection
  const networkBtns = document.querySelectorAll('.network-btn');
  networkBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      networkBtns.forEach(b => b.classList.remove('active-network'));
      this.classList.add('active-network');
      const selectedNetwork = this.dataset.network;
      document.getElementById('network').value = selectedNetwork;
      
      // Update prices for the selected network
      updateBundlePrices(selectedNetwork);
      
      // Clear any selected bundle
      bundleOptions.forEach(opt => opt.classList.remove('active'));
      document.getElementById('bundle').value = '';
      document.getElementById('price').value = '';
    });
  });

  // Function to update bundle prices based on selected network
  function updateBundlePrices(network) {
    const bundles = document.querySelectorAll('.bundle-option');
    
    bundles.forEach(bundle => {
      const bundleSize = bundle.dataset.bundle;
      const price = networkPricing[network][bundleSize];
      
      if (price) {
        // Update the displayed price
        bundle.querySelector('.bundle-price').textContent = `GH₵ ${price.toFixed(2)}`;
        // Update the data-price attribute
        bundle.dataset.price = price;
        bundle.style.display = 'block';
      } else {
        // Hide bundles not available for this network
        bundle.style.display = 'none';
      }
    });
  }

  // Bundle selection
  const bundleOptions = document.querySelectorAll('.bundle-option');
  bundleOptions.forEach(option => {
    option.addEventListener('click', function() {
      bundleOptions.forEach(opt => opt.classList.remove('active'));
      this.classList.add('active');
      document.getElementById('bundle').value = this.dataset.bundle;
      document.getElementById('price').value = this.dataset.price;
    });
  });
    
  // Paystack payment integration
  function payWithPaystack() {
    const network = document.getElementById('network').value;
    const phone = document.getElementById('phone').value;
    const bundle = document.getElementById('bundle').value;
    const price = document.getElementById('price').value;
    
    if (!phone || !bundle || !price) {
      alert('Please fill in all fields and select a bundle');
      return;
    }
    
    // Generate a random email since Paystack requires one
    const tempEmail = 'buyalldatagh_' + Math.random().toString(36).substring(2, 11) + '@example.com';
    
    const handler = PaystackPop.setup({
      key: 'pk_live_fb10659567aa8cf4166135eaf4142103fd6dfd55',
      email: tempEmail,
      amount: parseFloat(price) * 100, // Convert to kobo
      currency: 'GHS',
      ref: 'DATA_' + Math.floor(Math.random() * 1000000000 + 1),
      metadata: {
        custom_fields: [
          {
            display_name: "Network",
            variable_name: "network",
            value: network
          },
          {
            display_name: "Bundle",
            variable_name: "bundle",
            value: bundle
          },
          {
            display_name: "Phone",
            variable_name: "phone",
            value: phone
          }
        ]
      },
      callback: function(response) {
        // Payment successful - save the order
        saveOrder(response.reference, network, phone, bundle, price);
      },
      onClose: function() {
        console.log('Payment window closed');
      }
    });
    
    handler.openIframe();
  }
  
  // Function to save the order after successful payment
  function saveOrder(reference, network, phone, bundle, price) {
    // Create a FormData object to send to the server
    const formData = new FormData();
    formData.append('save_order', 'true');
    formData.append('reference', reference);
    formData.append('network', network);
    formData.append('phone', phone);
    formData.append('bundle', bundle);
    formData.append('price', price);
    
    // Use AJAX to save the order
    fetch('', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (response.ok) {
        window.location.href = 'index.php?success=true';
      } else {
        throw new Error('Network response was not ok');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred while processing your order');
    });
  }
  </script>
</body>
</html>