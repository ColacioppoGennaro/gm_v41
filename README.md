# 🎯 GM_V41 - SmartLife AI Organizer

> Organizzatore eventi personale multi-utente con AI per analisi documenti e assistente conversazionale.

![Status](https://img.shields.io/badge/status-in%20development-yellow)
![PHP](https://img.shields.io/badge/PHP-8.2+-blue)
![React](https://img.shields.io/badge/React-19-61DAFB)
![MySQL](https://img.shields.io/badge/MySQL-8.0-orange)

---

## 📋 Caratteristiche Principali

### ✅ Gestione Eventi
- Creazione/modifica/eliminazione eventi
- Eventi ricorrenti (giornalieri, settimanali, mensili, annuali)
- Categorie personalizzabili con colori e icone
- Stati: pending/completed
- Reminder personalizzabili

### 🤖 AI Assistant (Gemini 2.0 Flash)
- Chat conversazionale per creare eventi
- Analisi automatica documenti (fatture, bollette, ricevute)
- Estrazione dati: importo, scadenza, tipo documento
- Ricerca semantica documenti con embeddings

### 📅 Sincronizzazione Google Calendar
- Export automatico eventi (unidirezionale)
- OAuth 2.0 Google integrato
- Solo per utenti PRO

### 👥 Sistema Multi-Utente
- Piani FREE e PRO
- Limiti quote differenziati
- Autenticazione JWT sicura

---

## 🏗️ Architettura

```
Frontend: React 19 + TypeScript + Vite
Backend: PHP 8.2 + MySQL 8.0
AI: Google Gemini 2.0 Flash API
Hosting: Netsons (shared hosting)
Deploy: GitHub Actions
```

---

## 📁 Struttura Progetto

```
gm_v41/
├── docs/                       # Documentazione progetto
│   ├── ARCH.md                # Architettura completa
│   ├── DB.sql                 # Schema database
│   └── API.txt                # Endpoint API
├── frontend/                   # React application
│   ├── src/
│   │   ├── components/        # Componenti UI
│   │   ├── services/          # API calls
│   │   └── types/             # TypeScript types
│   ├── public/
│   └── package.json
├── backend/                    # PHP API
│   ├── api/
│   │   └── index.php         # Router principale
│   ├── config/
│   │   ├── database.php      # Connessione DB
│   │   └── gemini.php        # Client Gemini
│   ├── controllers/          # Business logic
│   ├── models/               # Modelli dati
│   └── middleware/           # Auth, rate limit
├── uploads/                    # Documenti utenti
├── .github/
│   └── workflows/
│       └── deploy.yml        # Auto-deploy
├── .env.example              # Template configurazione
├── .gitignore
└── README.md
```

---

## 🚀 Setup Locale

### Prerequisiti
- Node.js 18+
- PHP 8.2+
- MySQL 8.0+ / MariaDB 10.5+
- Composer
- Account Google Cloud (per API)

### 1. Clone Repository
```bash
git clone https://github.com/tuousername/gm_v41.git
cd gm_v41
```

### 2. Setup Database
```bash
# Crea database MySQL
mysql -u root -p

CREATE DATABASE smartlife_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'smartlife_user'@'localhost' IDENTIFIED BY 'password_sicura';
GRANT ALL PRIVILEGES ON smartlife_db.* TO 'smartlife_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Importa schema
mysql -u smartlife_user -p smartlife_db < docs/DB.sql
```

### 3. Configurazione Backend
```bash
cd backend

# Installa dipendenze PHP
composer install

# Copia .env e configura
cp ../.env.example .env
nano .env

# Compila:
# - DB_DATABASE, DB_USERNAME, DB_PASSWORD
# - GEMINI_API_KEY
# - JWT_SECRET (genera con: openssl rand -base64 64)
# - GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET
```

### 4. Configurazione Frontend
```bash
cd ../frontend

# Installa dipendenze
npm install

# Configura API endpoint
cp .env.example .env.local
nano .env.local

# Aggiungi:
VITE_API_URL=http://localhost:8000/api
```

### 5. Avvia Server Sviluppo
```bash
# Terminal 1 - Backend PHP
cd backend
php -S localhost:8000

# Terminal 2 - Frontend React
cd frontend
npm run dev
```

Apri browser: `http://localhost:5173`

---

## 🌐 Deploy su Netsons

### 1. Setup Hosting
- Crea database MySQL da cPanel
- Annota credenziali (host, database, user, password)
- Crea cartella `/public_html` se non esiste

### 2. Configurazione GitHub Secrets
Nel repository GitHub vai in **Settings > Secrets and variables > Actions**

Aggiungi questi secrets:
```
FTP_SERVER=ftp.tuodominio.it
FTP_USERNAME=utente_ftp
FTP_PASSWORD=password_ftp
DB_HOST=localhost
DB_DATABASE=nome_database
DB_USERNAME=utente_database
DB_PASSWORD=password_database
GEMINI_API_KEY=AIzaSy...
GOOGLE_CLIENT_ID=123456789-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abc123
JWT_SECRET=stringa_random_64_caratteri
```

### 3. Deploy Automatico
```bash
# Ogni push su main triggera deploy automatico
git add .
git commit -m "Deploy to production"
git push origin main

# GitHub Actions:
# 1. Build frontend React → /public_html
# 2. Upload backend PHP → /api
# 3. Crea .env sul server
```

### 4. Setup Database Remoto
```bash
# Accedi a phpMyAdmin su Netsons
# Importa docs/DB.sql
# Verifica tabelle create correttamente
```

### 5. Verifica Deploy
```
https://tuodominio.it → Frontend React
https://tuodominio.it/api/health → {"status":"ok"}
```

---

## 🔑 Google Cloud Setup

### 1. Gemini API
1. Vai su [Google AI Studio](https://makersuite.google.com/app/apikey)
2. Crea nuovo progetto
3. Genera API key
4. Copia in `.env` → `GEMINI_API_KEY`

### 2. Google Calendar OAuth
1. Vai su [Google Cloud Console](https://console.cloud.google.com)
2. Crea nuovo progetto "SmartLife Organizer"
3. Abilita **Google Calendar API**
4. Crea credenziali OAuth 2.0:
   - Tipo applicazione: Web application
   - Nome: SmartLife OAuth
   - URI redirect autorizzati: `https://tuodominio.it/api/auth/google/callback`
5. Copia **Client ID** e **Client Secret** in `.env`

---

## 📊 Piani Utente

### 🆓 FREE (default)
- 50 eventi/mese
- 10 MB storage documenti
- 20 query AI/mese
- NO sincronizzazione Google Calendar

### 💎 PRO (€4.99/mese)
- Eventi illimitati
- 500 MB storage documenti
- 500 query AI/mese
- Sincronizzazione Google Calendar

---

## 🧪 Testing

### Test Backend
```bash
cd backend
php -S localhost:8000

# Test endpoint
curl http://localhost:8000/api/health
```

### Test Frontend
```bash
cd frontend
npm run test
npm run build  # Verifica build produzione
```

---

## 📚 Documentazione

- **[ARCH.md](docs/ARCH.md)** - Architettura completa, flussi, limiti
- **[DB.sql](docs/DB.sql)** - Schema database con commenti
- **[API.txt](docs/API.txt)** - Tutti gli endpoint API

---

## 🛠️ Troubleshooting

### Problema: "Database connection failed"
```bash
# Verifica credenziali in .env
# Testa connessione MySQL
mysql -h DB_HOST -u DB_USERNAME -p DB_DATABASE
```

### Problema: "CORS error"
```bash
# Backend: verifica CORS_ALLOWED_ORIGINS in .env
# Deve includere dominio frontend
```

### Problema: "Gemini API error"
```bash
# Verifica API key valida
# Controlla quota giornaliera non superata
# Google AI Studio: https://makersuite.google.com/app/apikey
```

### Problema: Upload documenti fallisce
```bash
# Verifica permessi cartella uploads/
chmod 755 uploads/
chown www-data:www-data uploads/

# Verifica limiti PHP in php.ini
upload_max_filesize = 10M
post_max_size = 12M
```

---

## 🔒 Sicurezza

### ⚠️ IMPORTANTE
- **MAI** committare `.env` su GitHub
- **MAI** esporre chiavi API nel frontend
- Usa sempre **prepared statements** per query SQL
- Valida **tutti** gli input utente
- Hash password con **bcrypt** (cost factor 12+)
- Rigenera JWT secret in produzione

### Best Practices
```bash
# Genera JWT secret sicuro
openssl rand -base64 64

# Permessi file corretti
chmod 644 .env
chmod 755 uploads/
chmod 600 backend/logs/*.log
```

---

## 🚦 Roadmap

### ✅ Fase 1 (Attuale)
- [x] Documentazione architettura
- [x] Schema database
- [x] API design
- [ ] Backend PHP completo
- [ ] Frontend React aggiornato
- [ ] Deploy su Netsons

### 🔜 Fase 2 (Q1 2026)
- [ ] VPS Node.js per audio real-time
- [ ] WebSocket Gemini Live API
- [ ] Import bidirezionale Google Calendar
- [ ] Sistema pagamenti (Stripe)

### 🔮 Fase 3 (Q2 2026)
- [ ] App mobile Flutter
- [ ] Notifiche push native
- [ ] Condivisione eventi tra utenti

---

## 🤝 Contributi

Al momento progetto privato in sviluppo.

---

## 📄 Licenza

Proprietario - Tutti i diritti riservati

---

## 👤 Autore

**GM_V41 Project**
- GitHub: [@tuousername](https://github.com/tuousername)

---

## 📞 Supporto

Per problemi o domande:
- 📧 Email: support@tuodominio.it
- 📝 Issues: [GitHub Issues](https://github.com/tuousername/gm_v41/issues)

---

**Ultimo aggiornamento:** Ottobre 2025
**Versione:** 1.0.0-alpha
