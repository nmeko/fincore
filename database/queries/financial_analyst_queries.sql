-- FinCore Business Management System (FBMS)
-- Financial Analyst SQL Layer
-- Contributor: Josh Mays
-- Role: Financial Systems Analyst

USE fincore;

-- 1) Monthly Revenue Summary
SELECT 
    YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month_Number,
    MONTHNAME(Payment_date) AS Month_Name,
    SUM(Amount_paid) AS Total_Revenue
FROM payments
GROUP BY YEAR(Payment_date), MONTH(Payment_date), MONTHNAME(Payment_date)
ORDER BY Year, Month_Number;

-- 2) Monthly Expense Summary
SELECT 
    YEAR(Date) AS Year, MONTH(Date) AS Month_Number,
    MONTHNAME(Date) AS Month_Name,
    SUM(Amount) AS Total_Expenses
FROM expenses
GROUP BY YEAR(Date), MONTH(Date), MONTHNAME(Date)
ORDER BY Year, Month_Number;

-- 3) Monthly Profit Analysis
SELECT 
    r.Year,
    r.Month_Number,
    r.Month_Name,
    r.Total_Revenue,
    e.Total_Expenses,
    (r.Total_Revenue - e.Total_Expenses) AS Profit
FROM
(
    SELECT 
        YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month_Number,
        MONTHNAME(Payment_date) AS Month_Name,
        SUM(Amount_paid) AS Total_Revenue
    FROM payments
    GROUP BY YEAR(Payment_date), MONTH(Payment_date), MONTHNAME(Payment_date)
) r
JOIN
(
    SELECT 
        YEAR(Date) AS Year, MONTH(Date) AS Month_Number,
        MONTHNAME(Date) AS Month_Name,
        SUM(Amount) AS Total_Expenses
    FROM expenses
    GROUP BY YEAR(Date), MONTH(Date), MONTHNAME(Date)
) e
ON r.Year = e.Year AND r.Month_Number = e.Month_Number
ORDER BY r.Year, r.Month_Number;

-- 4) Profit Margin by Month
SELECT 
    r.Year,
    r.Month_Number,
    r.Month_Name,
    r.Total_Revenue,
    e.Total_Expenses,
    (r.Total_Revenue - e.Total_Expenses) AS Profit,
    ROUND(((r.Total_Revenue - e.Total_Expenses) / r.Total_Revenue) * 100, 2) AS Profit_Margin_Percent
FROM
(
    SELECT 
        YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month_Number,
        MONTHNAME(Payment_date) AS Month_Name,
        SUM(Amount_paid) AS Total_Revenue
    FROM payments
    GROUP BY YEAR(Payment_date), MONTH(Payment_date), MONTHNAME(Payment_date)
) r
JOIN
(
    SELECT 
        YEAR(Date) AS Year, MONTH(Date) AS Month_Number,
        MONTHNAME(Date) AS Month_Name,
        SUM(Amount) AS Total_Expenses
    FROM expenses
    GROUP BY YEAR(Date), MONTH(Date), MONTHNAME(Date)
) e
ON r.Year = e.Year AND r.Month_Number = e.Month_Number
ORDER BY r.Year, r.Month_Number;

-- 5) Top 5 Customers by Spending
SELECT 
    c.Customer_ID,
    c.Name,
    SUM(s.Total_amount) AS Total_Spent
FROM customer c
JOIN sales s 
    ON c.Customer_ID = s.Customer_ID
GROUP BY c.Customer_ID, c.Name
ORDER BY Total_Spent DESC
LIMIT 5;

-- 6) Outstanding Payments Summary
SELECT 
    c.Customer_ID,
    c.Name,
    s.Sale_ID,
    s.Date AS Sale_Date,
    p.Amount_paid,
    p.Payment_status
FROM payments p
JOIN sales s 
    ON p.Sale_ID = s.Sale_ID
JOIN customer c 
    ON s.Customer_ID = c.Customer_ID
WHERE p.Payment_status = 'Pending'
ORDER BY s.Date ASC;

-- 7) Total Outstanding Balance
SELECT 
    SUM(Amount_paid) AS Total_Outstanding_Balance
FROM payments
WHERE Payment_status = 'Pending';

-- 8) Expense Breakdown by Category
SELECT 
    Category,
    SUM(Amount) AS Total_Expense
FROM expenses
GROUP BY Category
ORDER BY Total_Expense DESC;

-- 9) Expense Breakdown by Category and Month
SELECT 
    YEAR(Date) AS Year, MONTH(Date) AS Month_Number,
    MONTHNAME(Date) AS Month_Name,
    Category,
    SUM(Amount) AS Total_Expense
FROM expenses
GROUP BY YEAR(Date), MONTH(Date), MONTHNAME(Date), Category
ORDER BY Year, Month_Number, Total_Expense DESC;

-- 10) Customer Purchase History
SELECT 
    c.Customer_ID,
    c.Name,
    s.Sale_ID,
    s.Date,
    s.Total_amount,
    p.Payment_status
FROM customer c
JOIN sales s 
    ON c.Customer_ID = s.Customer_ID
JOIN payments p 
    ON s.Sale_ID = p.Sale_ID
ORDER BY c.Name, s.Date DESC;

-- 11) Revenue vs Pending Revenue
SELECT 
    Payment_status,
    COUNT(*) AS Number_of_Payments,
    SUM(Amount_paid) AS Total_Amount
FROM payments
GROUP BY Payment_status;

-- 12) Most Sold Products
SELECT 
    pr.Product_ID,
    pr.Product_name,
    SUM(sp.Quantity) AS Total_Units_Sold,
    SUM(sp.Quantity * sp.Unit_price) AS Total_Product_Revenue
FROM product pr
JOIN sale_product sp
    ON pr.Product_ID = sp.Product_ID
GROUP BY pr.Product_ID, pr.Product_name
ORDER BY Total_Units_Sold DESC, Total_Product_Revenue DESC;

-- 13) Inventory Value Estimate
SELECT 
    pr.Product_ID,
    pr.Product_name,
    i.Quantity,
    pr.Price,
    (i.Quantity * pr.Price) AS Inventory_Value
FROM inventory i
JOIN product pr
    ON i.Product_ID = pr.Product_ID
ORDER BY Inventory_Value DESC;

-- 14) Total Inventory Value
SELECT 
    SUM(i.Quantity * pr.Price) AS Total_Inventory_Value
FROM inventory i
JOIN product pr
    ON i.Product_ID = pr.Product_ID;

-- 15) Dashboard Summary Query
SELECT 
    r.Year,
    r.Month_Number,
    r.Month_Name,
    r.Total_Revenue,
    e.Total_Expenses,
    (r.Total_Revenue - e.Total_Expenses) AS Profit,
    ROUND(((r.Total_Revenue - e.Total_Expenses) / r.Total_Revenue) * 100, 2) AS Profit_Margin_Percent,
    IFNULL(o.Pending_Amount, 0) AS Pending_Collections
FROM
(
    SELECT 
        YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month_Number,
        MONTHNAME(Payment_date) AS Month_Name,
        SUM(Amount_paid) AS Total_Revenue
    FROM payments
    GROUP BY YEAR(Payment_date), MONTH(Payment_date), MONTHNAME(Payment_date)
) r
JOIN
(
    SELECT 
        YEAR(Date) AS Year, MONTH(Date) AS Month_Number,
        MONTHNAME(Date) AS Month_Name,
        SUM(Amount) AS Total_Expenses
    FROM expenses
    GROUP BY YEAR(Date), MONTH(Date), MONTHNAME(Date)
) e
    ON r.Year = e.Year AND r.Month_Number = e.Month_Number
LEFT JOIN
(
    SELECT 
        YEAR(Payment_date) AS Year, MONTH(Payment_date) AS Month_Number,
        SUM(Amount_paid) AS Pending_Amount
    FROM payments
    WHERE Payment_status = 'Pending'
    GROUP BY YEAR(Payment_date), MONTH(Payment_date)
) o
    ON r.Year = o.Year AND r.Month_Number = o.Month_Number
ORDER BY r.Year, r.Month_Number;
