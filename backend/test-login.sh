#!/bin/bash
# Test script to verify login endpoint

echo "Testing login endpoint..."
echo ""

# First, register a test user if needed
echo "1. Attempting to register test user..."
curl -X POST https://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"testpass123"}' \
  -v
echo ""
echo ""

# Now try to login
echo "2. Attempting to login..."
curl -X POST https://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"testpass123"}' \
  -v
echo ""
