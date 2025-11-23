# EuroParcel - PrestaShop Shipping Module

![Version](https://img.shields.io/badge/version-1.0.2-blue.svg)
![PrestaShop](https://img.shields.io/badge/PrestaShop-1.7%20%7C%208.x-brightgreen.svg)
![License](https://img.shields.io/badge/license-AFL--3.0-orange.svg)

**EuroParcel** is a comprehensive shipping module for PrestaShop that integrates multiple Romanian courier services, offering both home delivery and locker pickup options for your customers.

---

## 📋 Table of Contents

- [Features](#features)
- [Supported Carriers](#supported-carriers)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Customer Experience](#customer-experience)
- [Technical Details](#technical-details)
- [Troubleshooting](#troubleshooting)
- [Support](#support)
- [License](#license)

---

## ✨ Features

### Dual Delivery Methods
- **Home Delivery**: Traditional courier delivery to customer's address
- **Locker Pickup**: Self-service parcel lockers for convenient 24/7 pickup

### Carrier Management
- Support for **6 major Romanian courier services**
- Support for **4 locker networks** (EasyBox, FANbox, DPD Box, Cargus Locker)
- Configurable carrier selection
- Automatic carrier ID mapping

### Interactive Locker Selection
- **Real-time locker map** powered by EuroParcel Maps API
- Location-based locker search
- Filter by carrier/locker type
- Customer address auto-population
- Visual locker selection interface

### Smart Features
- **Customer preference memory**: Remembers customer's last selected locker
- **Session persistence**: Locker selection maintained during checkout
- **Validation**: Prevents order completion without locker selection (when required)
- **Admin visibility**: View selected locker details in order management

### Security & Compliance
- **SQL injection protection** using PrestaShop's DbQuery class
- **CSRF token validation** on all AJAX requests
- **Directory listing prevention**
- **Secure data sanitization**
- PrestaShop coding standards compliant

---

## 🚚 Supported Carriers

### Home Delivery Services
| Carrier | Service ID | Description |
|---------|-----------|-------------|
| Cargus | 1 | Cargus National - Delivery to address |
| DPD | 2 | DPD Standard - Delivery to address |
| Fan Courier | 3 | Fan Courier - Delivery to address |
| GLS | 4 | GLS National - Delivery to address |
| Bookurier | 5 | Bookurier - Delivery to address |
| SameDay | 6 | SameDay - Delivery to address |

### Locker Networks
| Locker Type | Carrier ID | Description |
|-------------|-----------|-------------|
| EasyBox | 6 | Sameday EasyBox - Delivery to locker |
| FANbox | 3 | Fan Courier FANbox - Delivery to locker |
| DPD Box | 2 | DPD Box - Delivery to locker |
| Cargus Locker | 1 | Cargus - Delivery to locker |

---

## 📦 Requirements

### PrestaShop Version
- **Minimum**: PrestaShop 1.7.0
- **Maximum**: PrestaShop 8.x
- **Recommended**: PrestaShop 1.7.6+ or 8.x

### Server Requirements
- **PHP**: 7.1 or higher (7.4+ recommended)
- **MySQL**: 5.6+ or MariaDB 10.0+
- **PHP Extensions**:
  - `json` (for data handling)
  - `curl` (for API communication)
  - `mysqli` or `pdo_mysql` (for database)

### PrestaShop Configuration
- Clean URLs enabled (recommended)
- SSL/HTTPS enabled (recommended for security)
- JavaScript enabled in browser

---

## 🔧 Installation

### Method 1: Manual Installation (Recommended)

1. **Download the module**
   ```bash
   # Download or clone the module to your local machine
   git clone https://github.com/yourcompany/europarcel.git
   ```

2. **Upload to PrestaShop**
   - Via FTP: Upload the `europarcel` folder to `/modules/` directory
   - Via SSH:
   ```bash
   cd /path/to/prestashop/modules/
   # Upload the europarcel folder here
   ```

3. **Set proper permissions**
   ```bash
   chmod 755 europarcel/
   chmod 644 europarcel/*.php
   chmod 644 europarcel/config.xml
   ```

4. **Install via Back Office**
   - Go to: **Modules > Module Manager**
   - Search for "EuroParcel"
   - Click **Install**
   - Click **Configure**

### Method 2: ZIP Upload

1. **Create ZIP archive**
   - Compress the `europarcel` folder into `europarcel.zip`
   - Ensure the folder structure is: `europarcel.zip/europarcel/europarcel.php`

2. **Upload via Back Office**
   - Go to: **Modules > Module Manager**
   - Click **Upload a module**
   - Select `europarcel.zip`
   - Click **Install**

### Post-Installation

After installation, the module will:
- ✅ Create two carriers: "EuroParcel" and "EuroParcel Locker"
- ✅ Add custom fields to `orders` table (locker_id, carrier_id, service_id)
- ✅ Add custom field to `customer` table (locker preference)
- ✅ Register necessary hooks
- ✅ Set default configurations

---

## ⚙️ Configuration

### Access Configuration

1. Go to **Modules > Module Manager**
2. Search for "EuroParcel"
3. Click **Configure**

### Settings

#### 1. Default Home Delivery Carrier

Select which carrier should be used when customers choose the "EuroParcel" (home delivery) option:

- Cargus National
- DPD Standard
- Fan Courier
- GLS National
- Bookurier
- SameDay (default)

**Note**: This setting determines which carrier's API will be used for creating shipping labels.

#### 2. Available Locker Types

Check the locker types you want to offer to your customers:

- ☑️ **Sameday EasyBox**: Sameday's locker network
- ☑️ **Fan Courier FANbox**: Fan Courier's locker network
- ☑️ **DPD Box**: DPD's locker network
- ☑️ **Cargus Locker**: Cargus locker network

**Important**: Only selected locker types will appear in the map selection interface.

#### 3. Carrier IDs (Information Only)

The configuration page displays:
- EuroParcel Carrier ID
- EuroParcel Locker Carrier ID

These are auto-generated during installation and are used internally.

### Carrier Configuration

After installation, configure the carriers in PrestaShop:

1. Go to **Shipping > Carriers**
2. Find "EuroParcel" and "EuroParcel Locker"
3. Configure:
   - **Shipping zones**: Enable for your zones (usually Romania)
   - **Price ranges**: Set shipping costs by price or weight
   - **Tax**: Apply appropriate tax rules
   - **Group access**: Set which customer groups can use this carrier

---

## 💡 Usage

### For Store Administrators

#### Managing Orders with Lockers

When a customer selects a locker, the order will show:

1. **In Order Details**:
   - Carrier: "EuroParcel Locker"
   - Locker information displayed in order panel
   - Locker ID stored in database

2. **Database Fields** (accessible via Order object):
   - `europarcel_locker_id`: ID of the selected locker
   - `europarcel_carrier_id`: Carrier ID (1-6)
   - `europarcel_service_id`: 1 for home delivery, 2 for locker

#### Exporting Order Data

Use the Order webservice or export functionality to get:
```php
$order = new Order($order_id);
$locker_id = $order->europarcel_locker_id;
$carrier_id = $order->europarcel_carrier_id;
$service_id = $order->europarcel_service_id;
```

### For Developers

#### Accessing Locker Data

```php
// Get order with locker information
$order = new Order($order_id);

if ($order->europarcel_service_id == 2) {
    // This is a locker delivery
    $locker_id = $order->europarcel_locker_id;
    $carrier_id = $order->europarcel_carrier_id;
    
    // Use this data to create shipping labels via carrier API
}
```

#### Using Carrier Logos in Templates

All carrier logos are available in `/views/img/` directory:

```php
// In your template (.tpl file)
<img src="{$module_dir}views/img/cargus.webp" alt="Cargus" class="carrier-logo">
<img src="{$module_dir}views/img/dpd.webp" alt="DPD" class="carrier-logo">
<img src="{$module_dir}views/img/fan-courier.webp" alt="Fan Courier" class="carrier-logo">
```

```php
// In PHP code
$logoPath = $this->_path . 'views/img/cargus.webp';

// Available logos:
// - bookurier.webp
// - cargus.webp
// - cargus-ship-go.webp
// - dpd.webp
// - easybox.webp (locker)
// - fan-courier.webp
// - fanbox.webp (locker)
// - gls.webp
// - sameday.webp
```

#### Hook Integration

The module uses these hooks:
- `displayHeader`: Load JavaScript and CSS
- `displayCarrierExtraContent`: Show locker selection interface
- `actionValidateStepComplete`: Validate locker selection before proceeding
- `actionValidateOrder`: Save locker data to order
- `displayOrderConfirmation`: Show selected locker on confirmation page
- `displayAdminOrderMain`: Show locker info in admin order page
- `actionCarrierUpdate`: Update carrier IDs on carrier update

---

## 🛒 Customer Experience

### Checkout Flow

#### Step 1: Select Shipping Method

Customer sees two options:
- **EuroParcel** - Delivery to address (24-48 hours)
- **EuroParcel Locker** - Delivery to locker (next day)

#### Step 2: Select Locker (if Locker option chosen)

1. Button appears: **"Choose Locker"** or **"Change Locker"**
2. Click opens **interactive map modal**
3. Map shows available lockers based on:
   - Customer's delivery address
   - Selected locker types in admin settings
4. Customer clicks on locker marker
5. Locker details displayed
6. Customer confirms selection
7. Modal closes, selected locker shown in checkout

#### Step 3: Validation

- Customer **cannot proceed** to payment without selecting a locker (if locker delivery chosen)
- Validation message appears if attempting to continue without selection

#### Step 4: Order Confirmation

- Selected locker details shown on confirmation page
- Email confirmation includes locker information
- Customer can see locker address and pickup instructions

### Customer Convenience Features

- **Locker Preference Memory**: Module remembers customer's last selected locker
- **Easy Changes**: Can change locker selection before completing order
- **Visual Selection**: Map-based interface makes finding nearby lockers easy
- **Address Auto-fill**: Map automatically shows lockers near delivery address

---

## 🔧 Technical Details

### Database Schema

#### Added Columns to `ps_orders` table:

```sql
europarcel_locker_id INT UNSIGNED NULL DEFAULT NULL
  -- Stores the ID of selected locker (for locker deliveries)
  
europarcel_carrier_id INT UNSIGNED NULL DEFAULT NULL
  -- Stores the carrier ID (1-6) from EuroParcel system
  
europarcel_service_id INT UNSIGNED NULL DEFAULT NULL
  -- 1 = Home delivery, 2 = Locker delivery
```

#### Added Column to `ps_customer` table:

```sql
europarcel_locker_data TEXT NULL DEFAULT NULL
  -- JSON encoded last selected locker data for preference memory
```

### File Structure

```
europarcel/
├── europarcel.php              # Main module class
├── config.xml                  # Module metadata
├── logo.png                    # Module logo (PrestaShop admin)
├── LICENSE                     # AFL 3.0 license
├── README.md                   # This file
├── SECURITY_FIXES.md           # Security fixes documentation
├── index.php                   # Security file
│
├── controllers/
│   ├── index.php
│   └── front/
│       ├── index.php
│       └── ajax.php            # AJAX handler for locker selection
│
├── override/
│   ├── index.php
│   └── classes/
│       ├── index.php
│       └── order/
│           ├── index.php
│           └── Order.php       # Adds locker fields to Order class
│
└── views/
    ├── index.php
    ├── css/
    │   ├── index.php
    │   └── europarcel.css      # Module styles
    ├── img/                    # Carrier logos
    │   ├── index.php
    │   ├── logo.png            # Module logo
    │   ├── bookurier.webp      # Bookurier logo
    │   ├── cargus.webp         # Cargus logo
    │   ├── cargus-ship-go.webp # Cargus Ship&Go logo
    │   ├── dpd.webp            # DPD logo
    │   ├── easybox.webp        # EasyBox locker logo
    │   ├── fan-courier.webp    # Fan Courier logo
    │   ├── fanbox.webp         # FANbox locker logo
    │   ├── gls.webp            # GLS logo
    │   └── sameday.webp        # SameDay logo
    ├── js/
    │   ├── index.php
    │   ├── europarcel-modal.js     # Modal handling
    │   └── europarcel-checkout.js  # Checkout integration
    └── templates/
        ├── index.php
        ├── front/
        │   └── index.php
        └── hook/
            ├── index.php
            ├── carrier_extra_content.tpl         # Locker selection UI
            ├── displayAdminOrder.tpl             # Admin order info
            └── order_confirmation_locker.tpl     # Confirmation page
```

### API Integration

**EuroParcel Maps API**: `https://maps.europarcel.com`

The module integrates with EuroParcel's hosted map service:

**Request Parameters**:
- `country_code`: Always 'RO' (Romania)
- `locality_name`: City from delivery address
- `county_name`: State/county from delivery address
- `carrier_id`: Comma-separated carrier IDs (from configuration)
- `callback`: Set to 'parent' for iframe messaging

**Response**: PostMessage event with selected locker data
```javascript
{
  type: 'locker-selected',
  locker: {
    id: 'locker_id',
    name: 'Locker Name',
    address: 'Street Address',
    city: 'City',
    carrier_id: 6,
    // ... additional fields
  }
}
```

### Security Features

1. **SQL Injection Prevention**
   - All queries use DbQuery class or prepared statements
   - All user inputs properly sanitized

2. **CSRF Protection**
   - Token validation on all AJAX requests
   - PrestaShop's built-in token system

3. **XSS Prevention**
   - All output escaped in templates
   - JSON validation on data storage

4. **Directory Protection**
   - index.php files in all directories
   - Prevents directory listing

---

## 🐛 Troubleshooting

### Common Issues

#### 1. Locker Selection Modal Not Opening

**Symptoms**: Button click does nothing

**Solutions**:
- Check browser console for JavaScript errors
- Verify JavaScript files are loaded:
  - `europarcel-modal.js`
  - `europarcel-checkout.js`
- Clear PrestaShop cache: Performance > Clear Cache
- Check if jQuery is loaded (required)

#### 2. Locker Selection Not Saved

**Symptoms**: Selected locker disappears after refresh

**Solutions**:
- Check AJAX endpoint is accessible: `/module/europarcel/ajax`
- Verify cookies are enabled in browser
- Check server error logs for PHP errors
- Verify CSRF token is being generated

#### 3. Cannot Proceed to Payment with Locker

**Symptoms**: Validation error even after selecting locker

**Solutions**:
- Verify locker data is saved in session/cookie
- Check browser console for JavaScript errors
- Try selecting locker again
- Clear browser cookies and retry

#### 4. Module Installation Fails

**Symptoms**: Error during installation

**Solutions**:
- Check MySQL user has ALTER TABLE permissions
- Verify database connection is stable
- Check error logs in PrestaShop admin
- Try manual SQL field addition (see below)

**Manual SQL Fix**:
```sql
-- Run if installation fails
ALTER TABLE `ps_orders` 
  ADD `europarcel_locker_id` INT UNSIGNED NULL DEFAULT NULL,
  ADD `europarcel_carrier_id` INT UNSIGNED NULL DEFAULT NULL,
  ADD `europarcel_service_id` INT UNSIGNED NULL DEFAULT NULL;

ALTER TABLE `ps_customer` 
  ADD `europarcel_locker_data` TEXT NULL DEFAULT NULL;
```

#### 5. Carriers Not Showing in Checkout

**Symptoms**: EuroParcel carriers don't appear

**Solutions**:
- Check carrier is enabled: Shipping > Carriers
- Verify shipping zones are configured
- Check price ranges are set up
- Ensure carrier is associated with correct zones
- Verify carrier is not deleted (deleted = 0)

### Debug Mode

Enable PrestaShop debug mode to see detailed errors:

1. Edit `config/defines.inc.php`
2. Set: `define('_PS_MODE_DEV_', true);`
3. Check errors in browser and `/var/logs/`

### Getting Help

If issues persist:

1. **Check Logs**:
   - PrestaShop logs: `/var/logs/`
   - Server error logs: `/var/log/apache2/error.log` or nginx equivalent
   - Browser console (F12)

2. **Verify Installation**:
   ```sql
   -- Check if fields exist
   SHOW COLUMNS FROM ps_orders LIKE 'europarcel%';
   SHOW COLUMNS FROM ps_customer LIKE 'europarcel%';
   
   -- Check carriers
   SELECT id_carrier, name, deleted, active 
   FROM ps_carrier 
   WHERE name LIKE '%EuroParcel%';
   ```

3. **Test AJAX Endpoint**:
   - Visit: `https://yourstore.com/module/europarcel/ajax?action=save_locker_session`
   - Should return JSON error (without token)

---

## 🔄 Uninstallation

### Standard Uninstall

1. Go to **Modules > Module Manager**
2. Search for "EuroParcel"
3. Click **Uninstall**

**What happens**:
- ✅ Carriers marked as deleted (not removed)
- ✅ Configuration values removed
- ⚠️ Database fields **NOT removed** (data preservation)

### Complete Removal (Optional)

If you want to remove all traces including data:

```sql
-- Remove database fields
ALTER TABLE `ps_orders` 
  DROP COLUMN `europarcel_locker_id`,
  DROP COLUMN `europarcel_carrier_id`,
  DROP COLUMN `europarcel_service_id`;

ALTER TABLE `ps_customer` 
  DROP COLUMN `europarcel_locker_data`;

-- Delete carriers completely
DELETE FROM ps_carrier WHERE name LIKE '%EuroParcel%';
DELETE FROM ps_carrier_group WHERE id_carrier IN 
  (SELECT id_carrier FROM ps_carrier WHERE name LIKE '%EuroParcel%');
DELETE FROM ps_carrier_zone WHERE id_carrier IN 
  (SELECT id_carrier FROM ps_carrier WHERE name LIKE '%EuroParcel%');
```

⚠️ **Warning**: This will delete all historical locker data from orders.

---

## 📞 Support

### Documentation
- **Module Documentation**: This README file
- **PrestaShop Docs**: https://devdocs.prestashop.com/

### Getting Help
- **Issues**: Open an issue on GitHub
- **Email**: support@europarcel.com
- **Website**: https://www.europarcel.com

### Contributing
Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

---

## 📜 License

This module is licensed under the **Academic Free License (AFL 3.0)**.

```
Academic Free License ("AFL") v. 3.0

This Academic Free License (the "License") applies to any original work of
authorship (the "Original Work") whose owner (the "Licensor") has placed the
following licensing notice adjacent to the copyright notice for the Original Work:

Licensed under the Academic Free License version 3.0
```

**You are free to**:
- ✅ Use commercially
- ✅ Modify
- ✅ Distribute
- ✅ Use privately

**Conditions**:
- 📄 Include license and copyright notice
- 📄 State changes made to the code

Full license text: http://opensource.org/licenses/afl-3.0.php

---

## 📊 Changelog

### Version 1.0.2 (2025-11-20)

**Enhancements**:
- ✨ Automatic carrier logo copying
- ✨ Added tracking URL support for both carriers
- 🔧 Improved .htaccess to allow PNG and CSS files

**Technical Improvements**:
- Added `copyCarrierLogo()` method for automatic logo deployment
- Carrier logos now automatically copied to `/img/s/` directory
- Tracking URL configured: `https://www.eawb.ro/tracking?awb=@`
- Module info URL added to config.xml

---

### Version 1.0.0 (2025-10-21)

**Initial Release**

✨ **Features**:
- Support for 6 home delivery carriers
- Support for 4 locker networks
- Interactive EuroParcel map integration
- Customer locker preference memory
- Admin order information display
- Checkout validation

🔒 **Security**:
- SQL injection protection via DbQuery
- CSRF token validation
- XSS prevention
- Directory listing protection

🐛 **Fixes**:
- Proper data type handling for database fields
- Session persistence for locker selection
- Carrier ID tracking improvements

---

## 🙏 Acknowledgments

- **PrestaShop Team**: For the excellent e-commerce platform
- **EuroParcel**: For the maps API and locker network integration
- **Romanian Carriers**: Cargus, DPD, Fan Courier, GLS, SameDay, Bookurier

---

## 📈 Roadmap

### Planned Features (Future Versions)

- 🔄 **Automatic label generation** via carrier APIs
- 📧 **Email notifications** with tracking numbers
- 📊 **Shipping reports** and analytics
- 🌍 **Multi-country support** (beyond Romania)
- 📱 **Mobile app integration**
- 🔔 **Webhook support** for delivery status updates
- 💰 **Dynamic pricing** based on package weight/size
- 📦 **AWB generation** for all supported carriers

---

**Made with ❤️ for PrestaShop merchants**

For the latest updates, visit: https://github.com/europarcel/prestashop-module

