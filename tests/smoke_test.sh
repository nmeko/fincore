#!/usr/bin/env bash
# End-to-end security and behavior checks against a running FinCore instance.
# Usage: tests/smoke_test.sh http://localhost:8000 admin 'your-password'
# Needs: curl. Writes a test customer and a test sale to the database.
set -u
BASE=${1:-http://localhost:8000}; USER=${2:-admin}; PASS=${3:?password required}
JAR=$(mktemp); trap 'rm -f "$JAR"' EXIT
fails=0
check() { if [ "$2" = "$3" ]; then echo "PASS  $1"; else echo "FAIL  $1 (expected $3, got $2)"; fails=$((fails+1)); fi; }
code() { curl -s -o /dev/null -w '%{http_code}' -b "$JAR" -c "$JAR" "$@"; }
token() { curl -s -b "$JAR" -c "$JAR" "$BASE/$1" | grep -o 'name="csrf" value="[^"]*"' | head -1 | cut -d'"' -f4; }

check "pages require login (sales.php)" "$(code "$BASE/sales.php")" 302
check "login rejects missing CSRF token" "$(code -d "username=$USER&password=$PASS" "$BASE/login.php")" 403
T=$(token login.php)
check "login rejects wrong password" "$(curl -s -b "$JAR" -c "$JAR" --data-urlencode "csrf=$T" -d "username=$USER&password=wrong" "$BASE/login.php" | grep -c 'Invalid username')" 1
check "login works with the right password" "$(code --data-urlencode "csrf=$T" --data-urlencode "username=$USER" --data-urlencode "password=$PASS" "$BASE/login.php")" 302

for page in index customers employees expenses inventory payments products sales; do
  body=$(curl -s -b "$JAR" "$BASE/$page.php")
  check "$page.php renders without PHP errors" "$(echo "$body" | grep -ciE 'fatal error|warning:|notice:|deprecated:')" 0
done

check "writes without a CSRF token are refused" "$(code -d 'action=delete&id=1' "$BASE/customers.php")" 403

XSS='Eve" onmouseover="alert(1)'
T=$(token customers.php)
curl -s -o /dev/null -b "$JAR" -c "$JAR" --data-urlencode "csrf=$T" -d action=add --data-urlencode "name=$XSS" -d 'email=eve@example.com&phone=555-0199' "$BASE/customers.php"
PAGE=$(curl -s -b "$JAR" "$BASE/customers.php")
check "adding a customer saves it" "$(echo "$PAGE" | grep -c 'Eve&quot; onmouseover=&quot;alert(1)')" 1
check "customer names can't inject HTML/JS" "$(echo "$PAGE" | grep -c 'onmouseover="alert')" 0
check "search with SQL characters is handled" "$(code "$BASE/customers.php?q=%27%20OR%201%3D1%20--%20")" 200

T=$(token sales.php)
check "negative sale quantity is rejected" "$(curl -s -L -b "$JAR" -c "$JAR" --data-urlencode "csrf=$T" -d 'action=add&customer_id=1&employee_id=1&product_id=1&qty=-5&date=2026-01-01' "$BASE/sales.php" | grep -c 'quantity of at least 1')" 1

T=$(token customers.php)
check "deleting a customer with sales shows a friendly error" "$(curl -s -L -b "$JAR" -c "$JAR" --data-urlencode "csrf=$T" -d 'action=delete&id=1' "$BASE/customers.php" | grep -c 'other records still refer')" 1

EVE=$(curl -s -b "$JAR" "$BASE/customers.php?q=eve%40example.com" | grep -o 'name="id" value="[0-9]*"' | head -1 | grep -o '[0-9][0-9]*')
T=$(token customers.php)
check "deleting a customer without sales works" "$(curl -s -L -b "$JAR" -c "$JAR" --data-urlencode "csrf=$T" -d "action=delete&id=$EVE" "$BASE/customers.php" | grep -c 'Customer deleted')" 1

check "logout by GET is ignored" "$(curl -s -o /dev/null -w '%{redirect_url}' -b "$JAR" "$BASE/logout.php" | grep -c index.php)" 1
T=$(token index.php)
code --data-urlencode "csrf=$T" "$BASE/logout.php" >/dev/null
check "logout by POST ends the session" "$(code "$BASE/index.php")" 302

echo; [ $fails -eq 0 ] && echo "All checks passed." || { echo "$fails check(s) failed."; exit 1; }
