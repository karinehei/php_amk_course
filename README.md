# Final Task - Alko Product Catalog

## Overview

The **Alko Product Catalog** is a web-based application designed for filtering, searching, and browsing a database of Alko products. Users can filter the products based on attributes like product type, country of manufacture, bottle size, price range, and energy range. The app includes pagination and dynamic dropdowns, making navigation intuitive and efficient.


### Key Features
- **Dynamic Filtering**: Filter products by type, country, bottle size, price range, and energy range.
- **Responsive Pagination**: Navigate between pages with pagination controls.
- **Interactive Forms**: Dynamic dropdowns and manual input fields for range filters.
- **Database Integration**: Fetch product data directly from a MySQL database.
- **Separation of Concerns**: Modular code structure with reusable components.

---

## File Structure

### Project Root
- `index.php`: Main entry point of the application.
- `composer.json` / `composer.lock`: PHP dependencies for the project.

### `src/` Directory
- **`logic/`**: Backend logic and database-related operations.
    - `data_fetch.php`: Functions to fetch data from the database.
    - `data_preparation.php`: Prepares data for display (e.g., pagination logic).
    - `db_connection.php`: Handles the database connection setup.
    - `filter_logic.php`: Logic for managing filter inputs and validation.
    - `filters.php`: Defines global filter variables and structures.
    - `update_price_list.php`: Script to update the product price list in the database.
- **`partials/`**: Reusable frontend components.
    - `filter_form.php`: Generates the filter form for the catalog.
    - `footer.php`: Contains the footer HTML.
    - `header.php`: Contains the header HTML.
    - `pagination_logic.php`: Logic for creating pagination components.
    - `table_rows.php`: Generates table rows for the product display.

### `scripts/` Directory
- `inputs.js`: JavaScript for handling frontend interactions (e.g., dropdowns, input validation).

### `css/` Directory
- `styles.css`: Custom styles for the application.

### Database
- `db.sql`: SQL file for setting up the database schema and inserting initial data.

---

## Installation

### Prerequisites
- PHP 7.4 or higher
- Composer
- MySQL
- Apache

