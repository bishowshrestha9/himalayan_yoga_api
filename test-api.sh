#!/bin/bash

# Step 1: Login and get token
echo "Step 1: Logging in..."
LOGIN_RESPONSE=$(curl -s -X POST 'http://127.0.0.1:8000/api/auth/login' \
  -H 'Content-Type: application/json' \
  -d '{
    "email": "admin2@admin.com",
    "password": "password"
  }')

echo "Login Response: $LOGIN_RESPONSE"
echo ""

# Extract token (requires jq - install with: brew install jq)
TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.access_token')

if [ "$TOKEN" = "null" ] || [ -z "$TOKEN" ]; then
  echo "Error: Failed to get token. Response was: $LOGIN_RESPONSE"
  exit 1
fi

echo "Token received: ${TOKEN:0:20}..."
echo ""

# Step 2: Create blog with token
echo "Step 2: Creating blog..."
curl -X 'POST' \
  'http://127.0.0.1:8000/api/blogs' \
  -H 'accept: application/json' \
  -H 'Content-Type: application/json' \
  -H "Authorization: Bearer $TOKEN" \
  -d '{
  "title": "Blog Title",
  "description": "Blog Description",
  "image": "https://example.com/image.jpg",
  "status": true
}'

echo ""
echo "Done!"

