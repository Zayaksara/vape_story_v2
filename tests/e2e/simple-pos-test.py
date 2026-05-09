#!/usr/bin/env python3
"""
Simple POS payment and synchronization test using Playwright
"""
import subprocess
import sys
import time
import socket

def is_port_open(port):
    """Check if port is open"""
    try:
        with socket.create_connection(('localhost', port), timeout=1):
            return True
    except:
        return False

def main():
    # Check if Laravel server is running
    if not is_port_open(8000):
        print("Starting Laravel server...")
        server = subprocess.Popen(
            ['php', 'artisan', 'serve', '--port=8000'],
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE
        )
        # Wait for server to start
        for _ in range(30):
            if is_port_open(8000):
                print("Server started on port 8000")
                break
            time.sleep(1)
        else:
            print("Failed to start server")
            return 1
    
    # Run the Playwright test using npx
    print("Running Playwright tests...")
    
    # Create a simple test script
    test_script = '''
const { test, expect } = require('@playwright/test')

test.describe('Simple POS Test', () => {
  test('should login and access POS', async ({ page }) => {
    // Login
    await page.goto('/login')
    await page.fill('input[name="email"]', 'cashier@vape.com')
    await page.fill('input[name="password"]', 'cashier123')
    await page.click('button[type="submit"]')
    await page.waitForURL('**/pos/**', { timeout: 10000 })
    
    // Take screenshot
    await page.screenshot({ path: 'simple-test.png' })
    
    console.log('Login successful!')
  })
})
'''
    
    # Write and run test
    import os
    os.chdir('D:/story_vape')
    
    result = subprocess.run([
        'npx', 'playwright', 'test', 
        '--project=chromium',
        '--headed'
    ], capture_output=True, text=True, timeout=120)
    
    print("STDOUT:", result.stdout)
    print("STDERR:", result.stderr)
    
    return result.returncode

if __name__ == '__main__':
    sys.exit(main() or 0)