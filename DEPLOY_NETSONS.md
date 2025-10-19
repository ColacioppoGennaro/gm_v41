# 🚀 DEPLOY RAPIDO SU NETSONS

## 📋 CHECKLIST PRE-DEPLOY

- [ ] Database creato su phpMyAdmin Netsons
- [ ] Schema DB importato (`docs/DB.sql`)
- [ ] API Key Gemini ottenuta
- [ ] OAuth Google configurato (opzionale)

---

## 1️⃣ SETUP DATABASE

### Via phpMyAdmin su Netsons:

1. Accedi a phpMyAdmin
2. Crea database: `ywrloefq_gm_v41` (già fatto ✅)
3. Importa file: `docs/DB.sql`
4. Verifica tabelle create: `users`, `events`, `categories`, ecc.

---

## 2️⃣ PREPARAZIONE CODICE

### Backend:

```bash
cd gm_v41

# Copia .env.production e rinominalo in .env
cp .env.production .env

# Modifica .env e compila:
nano .env
```

**IMPORTANTISSIMO - Compila questi valori:**

```env
# JWT Secret (genera con: openssl rand -base64 64)
JWT_SECRET=LA_TUA_STRINGA_RANDOM_64_CARATTERI

# Gemini API
GEMINI_API_KEY=AIzaSy_TUA_API_KEY_QUI

# Google OAuth (se serve)
GOOGLE_CLIENT_ID=123456789-abc.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=GOCSPX-abc123def456

# URL produzione
APP_URL=https://tuodominio.it
GOOGLE_REDIRECT_URI=https://tuodominio.it/gm_v41/api/auth/google/callback
```

### Frontend:

```bash
cd frontend

# Installa dipendenze
npm install

# Build produzione
npm run build
# Genera cartella dist/ con file compilati
```

---

## 3️⃣ UPLOAD SU NETSONS

### Via FTP (FileZilla consigliato):

```
Host: ftp.tuodominio.it
Username: tuousername
Password: tuapassword
```

**Struttura upload:**

```
/public_html/gm_v41/
├── .env                      ← COPIA MANUALMENTE (non in Git!)
├── index.html                ← DA frontend/dist/
├── assets/                   ← DA frontend/dist/assets/
│   ├── index-abc123.js
│   └── index-def456.css
├── api/
│   ├── index.php             ← DA backend/api/
│   └── .htaccess            ← DA backend/
├── config/                   ← DA backend/config/
├── controllers/              ← DA backend/controllers/
├── models/                   ← DA backend/models/
├── middleware/               ← DA backend/middleware/
├── uploads/                  ← CREA CARTELLA VUOTA
└── logs/                     ← CREA CARTELLA VUOTA
```

**Permessi cartelle (chmod via FTP):**
```
uploads/  → 755
logs/     → 755
```

---

## 4️⃣ TEST APPLICAZIONE

### Test API:

```bash
# Health check
curl https://tuodominio.it/gm_v41/api/health

# Risposta attesa:
{"status":"ok","version":"1.0.0","timestamp":"2025-10-19T..."}
```

### Test Frontend:

Apri browser: `https://tuodominio.it/gm_v41/`

Dovresti vedere la pagina di login!

---

## 5️⃣ PRIMO UTILIZZO

1. **Registra account**: Click su "Registrati"
2. **Login**: Inserisci credenziali
3. **Verifica**: Dovresti vedere la dashboard con 4 categorie default

---

## 🐛 TROUBLESHOOTING

### Errore: "Database connection failed"

```bash
# Verifica credenziali in .env
# Controlla che DB_HOST sia 127.0.0.1 (NON localhost)
# Verifica che database esista in phpMyAdmin
```

### Errore: "404 Not Found" su /api/events

```bash
# Verifica che .htaccess sia in /gm_v41/backend/
# Verifica ModRewrite abilitato su Netsons
# Prova: https://tuodominio.it/gm_v41/api/index.php/events
```

### Frontend mostra pagina bianca

```bash
# Verifica console browser (F12)
# Controlla che VITE_API_URL sia corretto
# Verifica che index.html e assets/ siano caricati
```

### Errore: "CORS policy"

```bash
# Verifica .htaccess abbia:
# Header always set Access-Control-Allow-Origin "*"
# 
# Oppure modifica backend/.htaccess aggiungendo il tuo dominio
```

---

## 📊 VERIFICA FUNZIONAMENTO COMPLETO

- [ ] Login/Registrazione funziona
- [ ] Dashboard carica eventi
- [ ] Creazione nuovo evento funziona
- [ ] Categorie visibili
- [ ] Modifica/Eliminazione evento funziona

---

## 🔄 AGGIORNAMENTI FUTURI

Ogni volta che modifichi il codice:

```bash
# Frontend
cd frontend
npm run build
# Upload contenuto dist/ su /public_html/gm_v41/

# Backend
# Upload solo file .php modificati
```

---

## 📞 SUPPORTO

Se qualcosa non funziona:

1. Controlla log PHP: `/logs/error.log`
2. Controlla console browser (F12)
3. Verifica credenziali `.env`
4. Testa API health: `/api/health`

---

**FATTO! L'app dovrebbe funzionare! 🎉**
