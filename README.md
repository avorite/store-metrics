# Store Metrics

A WordPress plugin for displaying comprehensive store statistics for WooCommerce - including most popular products, total sales, total deals, and ROI calculations.

## Description

Store Metrics provides shop owners and administrators with an easy-to-use dashboard that displays key metrics about their WooCommerce store performance. Monitor your most profitable products, track sales, and get insights about your return on investment all in one place.

**Key Features:**
- Top 5 products display with images and sales data
- Monthly sales statistics 
- ROI calculation based on product costs and expenses
- Easy filtering by year and month
- Cost price tracking for products

## Requirements

- WordPress 5.0 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `store-metrics` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Go to "Store Metrics" in the WordPress admin menu to view your statistics

## Usage

### Viewing Statistics
1. Navigate to "Store Metrics" in your WordPress admin menu
2. Use the year and month dropdown filters to select your desired timeframe
3. View your store statistics for the selected period

### Setting Product Cost Prices
1. Edit any product in WooCommerce
2. Find the "Cost Price" field in the Product Data > General tab
3. Enter the cost price for the product
4. Save the product

### Configuring ROI Settings
1. Go to the Store Metrics page
2. Enter your PR Budget and Additional Costs for the selected month
3. Click "Save Settings"
4. View the calculated ROI based on your inputs

## Frequently Asked Questions

**Q: How is ROI calculated?**  
A: ROI is calculated by taking the total sales, subtracting the total cost of products sold and your marketing expenses (PR Budget and Additional Costs), then dividing that result by your total expenses.

**Q: Can I export the statistics?**  
A: Currently, the statistics are only available for viewing in the dashboard. Export functionality may be added in future updates.

**Q: Is this plugin compatible with multisite?**  
A: Yes, the plugin works with WordPress multisite installations.

## Changelog

### 1.0.0
- Initial release

## Credits

Developed by Maxim Shyian

## License

This plugin is licensed under the GPL v2 or later.

```php
/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
```
