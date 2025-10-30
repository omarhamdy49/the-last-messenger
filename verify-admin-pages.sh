#!/bin/bash

# Admin Pages Verification Script
# Verifies that all admin page routes and views are properly configured

echo "═════════════════════════════════════════════════════════════"
echo "🧪 Admin Pages Verification"
echo "═════════════════════════════════════════════════════════════"
echo ""

# Color codes
GREEN='\033[0;32m'
RED='\033[0;31m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

BASE_URL="http://localhost:8000/su/portal/v1/admin"

# Function to test admin page
test_admin_page() {
    local page_name=$1
    local page_url=$2

    echo -e "${BLUE}Testing: $page_name${NC}"
    echo "URL: $page_url"

    # Make request and check for HTML response (not login page redirect)
    RESPONSE=$(curl -s -w "\n%{http_code}" "$page_url")
    HTTP_CODE=$(echo "$RESPONSE" | tail -n 1)
    BODY=$(echo "$RESPONSE" | head -n -1)

    if [ "$HTTP_CODE" == "302" ] || [ "$HTTP_CODE" == "301" ]; then
        echo -e "${YELLOW}⚠ REDIRECT${NC} (HTTP $HTTP_CODE) - Login required or page moved"
    elif [ "$HTTP_CODE" == "200" ]; then
        if echo "$BODY" | grep -q "<html>"; then
            echo -e "${GREEN}✓ PASS${NC} (HTTP 200) - HTML page loaded"
        else
            echo -e "${RED}✗ FAIL${NC} (HTTP 200) - Not HTML content"
        fi
    elif [ "$HTTP_CODE" == "404" ]; then
        echo -e "${RED}✗ FAIL${NC} (HTTP 404) - Page not found"
    elif [ "$HTTP_CODE" == "500" ]; then
        echo -e "${RED}✗ FAIL${NC} (HTTP 500) - Server error"
        echo "Error: $BODY"
    else
        echo -e "${RED}✗ FAIL${NC} (HTTP $HTTP_CODE) - Unknown error"
    fi
    echo ""
}

echo "Testing Admin Index Pages:"
echo "──────────────────────────────────────────────────────────────"

test_admin_page "Dashboard" "$BASE_URL/dashboard"
test_admin_page "Articles" "$BASE_URL/articles"
test_admin_page "Hadith" "$BASE_URL/hadith"
test_admin_page "Media Programs" "$BASE_URL/media-programs"
test_admin_page "Seerah Events" "$BASE_URL/seerah-events"
test_admin_page "Sahaba" "$BASE_URL/sahaba"
test_admin_page "Quran" "$BASE_URL/quran"
test_admin_page "Users" "$BASE_URL/users"
test_admin_page "Roles" "$BASE_URL/roles"
test_admin_page "Activity Logs" "$BASE_URL/activity-logs"
test_admin_page "Genealogy" "$BASE_URL/genealogy"

echo "═════════════════════════════════════════════════════════════"
echo "📋 Test Summary"
echo "═════════════════════════════════════════════════════════════"
echo ""
echo "Note: Tests require authentication. If getting redirects to login,"
echo "this is expected behavior. Log in first with admin credentials."
echo ""
echo "For authenticated testing:"
echo ""
echo "1. Register/Login via API:"
echo "   curl -X POST $BASE_URL/auth/login \\"
echo "     -H 'Content-Type: application/json' \\"
echo "     -d '{\"email\":\"admin@example.com\",\"password\":\"password\"}'"
echo ""
echo "2. Get session/cookie from login response"
echo ""
echo "3. Use cookie in admin page requests:"
echo "   curl -X GET $BASE_URL/media-programs \\"
echo "     -H 'Cookie: session_name=session_value'"
echo ""
echo "═════════════════════════════════════════════════════════════"
