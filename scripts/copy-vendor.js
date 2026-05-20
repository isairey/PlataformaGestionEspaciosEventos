const fs = require('fs');
const path = require('path');

// Create vendor directory if it doesn't exist
const vendorDir = path.join(__dirname, '..', 'public', 'js', 'vendor');
if (!fs.existsSync(vendorDir)) {
  fs.mkdirSync(vendorDir, { recursive: true });
  console.log('Created vendor directory');
}

// Copy html5-qrcode library
const source = path.join(__dirname, '..', 'node_modules', 'html5-qrcode', 'html5-qrcode.min.js');
const destination = path.join(vendorDir, 'html5-qrcode.min.js');

if (fs.existsSync(source)) {
  fs.copyFileSync(source, destination);
  console.log('✓ Copied html5-qrcode.min.js to public/js/vendor/');
} else {
  console.error('✗ Source file not found:', source);
  process.exit(1);
}
