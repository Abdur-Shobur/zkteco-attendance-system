#!/bin/bash

# Setup Laravel Task Scheduler for ZKTeco Attendance System
# This script helps set up the cron job for automatic attendance log synchronization

echo "🕐 Setting up Laravel Task Scheduler for ZKTeco Attendance System"
echo "================================================================="

# Get the current directory
CURRENT_DIR=$(pwd)

echo "📍 Current project directory: $CURRENT_DIR"
echo ""

echo "📋 To enable automatic attendance log synchronization every minute, you need to add the following cron job:"
echo ""
echo "1. Open your crontab:"
echo "   crontab -e"
echo ""
echo "2. Add this line to run Laravel scheduler every minute:"
echo "   * * * * * cd $CURRENT_DIR && php artisan schedule:run >> /dev/null 2>&1"
echo ""
echo "3. Save and exit the crontab editor"
echo ""
echo "📝 Alternative: You can also run this command to add the cron job automatically:"
echo "   (crontab -l 2>/dev/null; echo \"* * * * * cd $CURRENT_DIR && php artisan schedule:run >> /dev/null 2>&1\") | crontab -"
echo ""
echo "✅ Once set up, the system will automatically sync attendance logs from your ZKTeco device every minute!"
echo ""
echo "🔍 To test the scheduler manually, run:"
echo "   php artisan schedule:run"
echo ""
echo "📊 To test the sync command directly, run:"
echo "   php artisan attendance:sync"
echo ""
echo "📝 To view scheduled tasks, run:"
echo "   php artisan schedule:list"
echo ""
echo "⚠️  Note: Make sure your ZKTeco device is accessible and configured properly in your .env file"
echo "   ZKTECO_DEVICE_IP=your_device_ip"
echo "   ZKTECO_DEVICE_PORT=4370"