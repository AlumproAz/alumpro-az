# Alumpro.Az Management System

Professional alüminium profil və şüşə məhsulları idarəetmə sistemi.

## Xüsusiyyətlər

- 🏢 Çox mağazalı dəstək
- 📦 Anbar idarəsi
- 🛒 Sifariş sistemi
- 👥 Müştəri idarəsi
- 💬 Real-time dəstək chat
- 📊 Hesabatlar və analitika
- 📱 PWA dəstəyi
- 🔔 Bildiriş sistemi
- 📨 WhatsApp inteqrasiyası

## Quraşdırma

1. **Sistem tələbləri:**
   - PHP 7.4+
   - MySQL 5.7+
   - Apache/Nginx
   - Composer
   - Node.js 14+

2. **Quraşdırma addımları:**
```bash
# Repository-ni klonlayın
git clone https://github.com/kodaz-az/alumpro-az.git

# Dependencies quraşdırın
composer install
npm install

# .env faylını konfiqurasiya edin
cp .env.example .env

# Verilənlər bazasını import edin
mysql -u root -p alumpro < config/database.sql

# Assets build edin
npm run build