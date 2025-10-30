#!/bin/bash

# API Testing Script for The Last Messenger Backend
# Tests various API endpoints to verify JSON responses

API_URL="http://localhost:8000"
HEADER_JSON="Content-Type: application/json"

echo "════════════════════════════════════════════════════════════════"
echo "🧪 API Authentication & Response Format Testing"
echo "════════════════════════════════════════════════════════════════"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Test 1: Health Check
echo -e "${BLUE}Test 1: Health Check Endpoint (No Auth Required)${NC}"
echo "GET $API_URL/api/health"
RESPONSE=$(curl -s -X GET "$API_URL/api/health" \
  -H "$HEADER_JSON")
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q '"status"'; then
  echo -e "${GREEN}✓ PASS${NC} - Returns JSON (not HTML)\n"
else
  echo -e "${RED}✗ FAIL${NC} - Response is not JSON\n"
fi

# Test 2: Public Articles Endpoint
echo -e "${BLUE}Test 2: Get Articles (No Auth Required)${NC}"
echo "GET $API_URL/api/articles"
RESPONSE=$(curl -s -X GET "$API_URL/api/articles?per_page=5" \
  -H "$HEADER_JSON")
if echo "$RESPONSE" | grep -q '"success"'; then
  echo -e "${GREEN}✓ PASS${NC} - Returns JSON format\n"
else
  echo -e "${RED}✗ FAIL${NC} - Response is not JSON\n"
fi

# Test 3: Protected Endpoint Without Token (Should Return JSON Error)
echo -e "${BLUE}Test 3: Create Article Without Token (Auth Required)${NC}"
echo "POST $API_URL/api/articles (no Authorization header)"
RESPONSE=$(curl -s -X POST "$API_URL/api/articles" \
  -H "$HEADER_JSON" \
  -d '{"title":"test"}')
echo "Response: $RESPONSE"
if echo "$RESPONSE" | grep -q '"success"' && echo "$RESPONSE" | grep -q 'Unauthenticated'; then
  echo -e "${GREEN}✓ PASS${NC} - Returns JSON error (not HTML login page)\n"
else
  if echo "$RESPONSE" | grep -q '<html>'; then
    echo -e "${RED}✗ FAIL${NC} - Response is HTML (login page)\n"
  else
    echo -e "${YELLOW}⚠ CHECK${NC} - Response format may need adjustment\n"
  fi
fi

# Test 4: Hadith Endpoint
echo -e "${BLUE}Test 4: Get Hadith (No Auth Required)${NC}"
echo "GET $API_URL/api/hadith"
RESPONSE=$(curl -s -X GET "$API_URL/api/hadith?per_page=5" \
  -H "$HEADER_JSON")
if echo "$RESPONSE" | grep -q '"success"' || echo "$RESPONSE" | grep -q '"data"'; then
  echo -e "${GREEN}✓ PASS${NC} - Returns JSON format\n"
else
  echo -e "${RED}✗ FAIL${NC} - Response is not JSON\n"
fi

# Test 5: Seerah Events Endpoint
echo -e "${BLUE}Test 5: Get Seerah Events (No Auth Required)${NC}"
echo "GET $API_URL/api/seerah"
RESPONSE=$(curl -s -X GET "$API_URL/api/seerah?per_page=5" \
  -H "$HEADER_JSON")
if echo "$RESPONSE" | grep -q '"success"' || echo "$RESPONSE" | grep -q '"data"'; then
  echo -e "${GREEN}✓ PASS${NC} - Returns JSON format\n"
else
  echo -e "${RED}✗ FAIL${NC} - Response is not JSON\n"
fi

# Test 6: Quran Endpoint
echo -e "${BLUE}Test 6: Get Quran Surahs (No Auth Required)${NC}"
echo "GET $API_URL/api/quran/surahs"
RESPONSE=$(curl -s -X GET "$API_URL/api/quran/surahs" \
  -H "$HEADER_JSON")
if echo "$RESPONSE" | grep -q '"success"' || echo "$RESPONSE" | grep -q '"data"'; then
  echo -e "${GREEN}✓ PASS${NC} - Returns JSON format\n"
else
  echo -e "${RED}✗ FAIL${NC} - Response is not JSON\n"
fi

echo ""
echo "════════════════════════════════════════════════════════════════"
echo "📋 Test Summary"
echo "════════════════════════════════════════════════════════════════"
echo ""
echo "✓ Key Points:"
echo "  1. Public endpoints return JSON with 'success' and 'data' fields"
echo "  2. Protected endpoints without auth return JSON 401 error"
echo "  3. NO HTML login pages should be returned for API requests"
echo ""
echo "🔑 To Test Protected Endpoints:"
echo ""
echo "  1. Register/Login to get token:"
echo "     curl -X POST $API_URL/api/auth/login \\"
echo "       -H 'Content-Type: application/json' \\"
echo "       -d '{\"email\":\"test@example.com\",\"password\":\"password\"}'"
echo ""
echo "  2. Use token in protected requests:"
echo "     curl -X POST $API_URL/api/articles \\"
echo "       -H 'Content-Type: application/json' \\"
echo "       -H 'Authorization: Bearer YOUR_TOKEN' \\"
echo "       -d '{...}'"
echo ""
echo "════════════════════════════════════════════════════════════════"
