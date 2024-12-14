CREATE TABLE alko_price_list (
     id INT AUTO_INCREMENT PRIMARY KEY,
     number VARCHAR(255),
     name VARCHAR(255),
     manufacturer VARCHAR(255),
     bottle_size VARCHAR(50),
     price DECIMAL(10, 2),
     liter_price DECIMAL(10, 2),
     type VARCHAR(255),
     country_of_manufacture VARCHAR(255),
     vintage YEAR,
     alcohol_percentage DECIMAL(10, 2),
     energy_kcal_per_100ml DECIMAL(10, 1)
);


