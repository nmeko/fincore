-- 1. Total Monthly Revenue
SELECT YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month, SUM(Amount_paid) AS Total_Revenue
FROM payments
GROUP BY YEAR(Payment_date), MONTH(Payment_date)
ORDER BY Year, Month;

-- 2. Top 5 Customers by Spending
SELECT c.Name, SUM(s.Total_amount) AS Total_Spent
FROM customer c
JOIN sales s ON c.Customer_ID = s.Customer_ID
GROUP BY c.Customer_ID, c.Name
ORDER BY Total_Spent DESC
LIMIT 5;

-- 3. Current Inventory Levels
SELECT p.Product_name, p.Price, i.Quantity
FROM inventory i
JOIN product p ON i.Product_ID = p.Product_ID
ORDER BY i.Quantity ASC;

-- 4. Monthly Profit
-- Months are totaled separately first so it runs under MySQL 8's ONLY_FULL_GROUP_BY;
-- a month with no expenses counts them as 0 instead of making profit NULL.
SELECT
    r.Year, r.Month, r.Revenue,
    COALESCE(e.Expenses, 0) AS Expenses,
    r.Revenue - COALESCE(e.Expenses, 0) AS Profit
FROM (
    SELECT YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month, SUM(Amount_paid) AS Revenue
    FROM payments
    GROUP BY YEAR(Payment_date), MONTH(Payment_date)
) r
LEFT JOIN (
    SELECT YEAR(Date) AS Year, MONTH(Date) AS Month, SUM(Amount) AS Expenses
    FROM expenses
    GROUP BY YEAR(Date), MONTH(Date)
) e ON e.Year = r.Year AND e.Month = r.Month
ORDER BY r.Year, r.Month;

-- 5. Customer Purchase History
SELECT c.Name, s.Sale_ID, s.Date, s.Total_amount, p.Payment_status
FROM customer c
JOIN sales s ON c.Customer_ID = s.Customer_ID
JOIN payments p ON s.Sale_ID = p.Sale_ID
ORDER BY c.Name, s.Date DESC;

-- 6. Most Sold Products
SELECT p.Product_name, SUM(sp.Quantity) AS Total_Sold
FROM product p
JOIN sale_product sp ON p.Product_ID = sp.Product_ID
GROUP BY p.Product_ID, p.Product_name
ORDER BY Total_Sold DESC;

-- 7. Outstanding Payments
SELECT c.Name, s.Sale_ID, s.Date, p.Amount_paid, p.Payment_status
FROM payments p
JOIN sales s ON p.Sale_ID = s.Sale_ID
JOIN customer c ON s.Customer_ID = c.Customer_ID
WHERE p.Payment_status = 'Pending'
ORDER BY s.Date ASC;

-- 8. Expenses Breakdown by Category
SELECT Category, SUM(Amount) AS Total, MONTH(Date) AS Month
FROM expenses
GROUP BY Category, MONTH(Date)
ORDER BY Month, Total DESC;
