# ZKTeco Attendance System API Documentation

This document describes the available API endpoints for the ZKTeco Attendance System.

## Authentication

No authentication is required for the attendance system endpoints. The dashboard and all API endpoints are publicly accessible.

## Base URL

```
http://your-domain.com/attendance
```

## Endpoints

### Device Management

#### Test Device Connection
```http
POST /attendance/test-connection
```

**Response:**
```json
{
  "success": true,
  "connected": true,
  "message": "Device connected successfully",
  "device_info": {
    "serial_number": "ABC123456",
    "firmware_version": "6.60",
    "device_name": "SpeedFace-V3L"
  }
}
```

#### Get Device Information
```http
GET /attendance/device-info
```

**Response:**
```json
{
  "serial_number": "ABC123456",
  "firmware_version": "6.60",
  "device_name": "SpeedFace-V3L",
  "user_count": 150,
  "log_count": 2500
}
```

#### Get Device Users
```http
GET /attendance/device-users
```

**Response:**
```json
[
  {
    "uid": "1",
    "userid": "EMP001",
    "name": "John Doe",
    "role": "User",
    "password": "",
    "cardno": "123456"
  }
]
```

#### Sync Device Users to Database
```http
POST /attendance/sync-device-users
```

**Response:**
```json
{
  "success": true,
  "message": "Device users synchronized successfully",
  "data": {
    "total_device_users": 10,
    "synced_count": 3,
    "updated_count": 7,
    "errors": []
  }
}
```

### Attendance Log Management

#### Sync Attendance Logs
```http
POST /attendance/sync-logs
```

**Response:**
```json
{
  "success": true,
  "total_logs": 50,
  "synced_count": 45,
  "errors": []
}
```

#### Get Attendance Logs
```http
GET /attendance/logs
```

**Query Parameters:**
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)
- `user_id` (optional): Filter by user ID
- `device_user_id` (optional): Filter by device user ID
- `verification_type` (optional): Filter by verification type
- `per_page` (optional): Number of records per page (default: 50)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "device_user_id": "1",
      "user_id": 2,
      "punch_time": "2024-01-15 09:00:00",
      "punch_state": "Check In",
      "verification_type": "Fingerprint",
      "device_ip": "192.168.1.201",
      "user": {
        "id": 2,
        "name": "John Doe",
        "email": "john@example.com",
        "employee_id": "EMP001"
      }
    }
  ],
  "current_page": 1,
  "per_page": 50,
  "total": 150
}
```

#### Get User Summary
```http
GET /attendance/user-summary
```

**Query Parameters:**
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)

**Response:**
```json
[
  {
    "user_id": 2,
    "user_name": "John Doe",
    "employee_id": "EMP001",
    "total_logs": 20,
    "first_punch": "2024-01-15 09:00:00",
    "last_punch": "2024-01-15 18:00:00",
    "total_hours": "9.0"
  }
]
```

#### Export Attendance Logs
```http
GET /attendance/export
```

**Query Parameters:**
- `date_from` (optional): Start date (YYYY-MM-DD)
- `date_to` (optional): End date (YYYY-MM-DD)
- `user_id` (optional): Filter by user ID

**Response:** CSV file download

### Real-time Operations

#### Real-time Sync
```http
POST /attendance/realtime-sync
```

**Response:**
```json
{
  "success": true,
  "new_logs": 3,
  "last_sync": "2024-01-15 10:30:00"
}
```

#### Clear Device Logs
```http
POST /attendance/clear-device-logs
```

**Response:**
```json
{
  "success": true,
  "message": "Device logs cleared successfully"
}
```

## Error Responses

All endpoints may return error responses in the following format:

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Validation error message"]
  }
}
```

## HTTP Status Codes

- `200 OK`: Request successful
- `400 Bad Request`: Invalid request parameters
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Access denied
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation errors
- `500 Internal Server Error`: Server error

## Rate Limiting

API endpoints are rate-limited to prevent abuse. The current limits are:
- 60 requests per minute for authenticated users
- Device sync operations: 10 requests per minute

## Webhook Support

For real-time notifications, you can set up webhooks to receive attendance log updates:

```json
{
  "event": "attendance.logged",
  "data": {
    "device_user_id": "1",
    "user_id": 2,
    "punch_time": "2024-01-15 09:00:00",
    "punch_state": "Check In",
    "verification_type": "Fingerprint"
  },
  "timestamp": "2024-01-15T09:00:00Z"
}
```

## SDK Examples

### JavaScript/Axios
```javascript
// Test connection
const response = await axios.post('/attendance/test-connection');
console.log(response.data);

// Get logs
const logs = await axios.get('/attendance/logs?date_from=2024-01-01');
console.log(logs.data);
```

### PHP/Guzzle
```php
// Test connection
$response = $client->post('/attendance/test-connection');
$data = json_decode($response->getBody(), true);

// Sync logs
$response = $client->post('/attendance/sync-logs');
$result = json_decode($response->getBody(), true);
```

### cURL
```bash
# Test connection
curl -X POST http://your-domain.com/attendance/test-connection \
  -H "Authorization: Bearer your-token"

# Get logs
curl -X GET "http://your-domain.com/attendance/logs?date_from=2024-01-01" \
  -H "Authorization: Bearer your-token"
```