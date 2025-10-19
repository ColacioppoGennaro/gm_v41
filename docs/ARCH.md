# ARCHITETTURA SMARTLIFE AI ORGANIZER

## 🎯 SCOPO DELL'APP
Organizzatore eventi personale multi-utente con AI per analisi documenti e assistente conversazionale. Sincronizzazione unidirezionale con Google Calendar.

---

## 👥 RUOLI UTENTE

### **FREE (default)**
- 50 eventi/mese
- 10 MB storage documenti totali
- 20 query AI/mese
- NO sincronizzazione Google Calendar

### **PRO (€4.99/mese)**
- Eventi illimitati
- 500 MB storage documenti
- 500 query AI/mese
- Sincronizzazione Google Calendar (export)

---

## 📱 PAGINE/VISTE

### 1. **Autenticazione**
- `/register` - Registrazione (email, password, conferma password)
- `/login` - Login (email, password)
- `/logout` - Logout e distruzione sessione

### 2. **Dashboard (Home)**
- Lista eventi cronologica
- Filtri: categoria, stato (pending/completed)
- Checkbox completamento eventi
- Badge: scaduto, documento allegato, reminder
- Bottone "Oggi" (scroll automatico)
- FAB viola (AI Assistant)

### 3. **Calendario**
- Vista mensile con pallini colorati (eventi)
- Vista giornaliera timeline (ore 00:00-23:59)
- Click data → modal nuovo evento
- Click evento → modal modifica
- FAB (+) per nuovo evento

### 4. **Documenti**
- Lista PDF caricati
- Info: nome file, data upload, importo estratto
- Descrizione AI del documento
- Ricerca testuale
- Bottone "Carica documento"
- FAB AI per analisi

### 5. **Impostazioni**
- Profilo utente (email, piano FREE/PRO)
- Gestione categorie personalizzate (nome, colore, icona, contatore eventi)
- Toggle "Sincronizza Google Calendar" (solo PRO)
- Preferenze notifiche (future)
- Logout

---

## 🔄 FLUSSI PRINCIPALI

### **FLUSSO 1: Registrazione/Login**
```
1. Utente → /register → inserisce email, password, conferma
2. Backend valida (email univoca, password min 8 char)
3. Backend → hash password (bcrypt) → salva in DB
4. Backend → crea JWT token
5. Frontend → salva token in localStorage
6. Redirect → /dashboard
```

### **FLUSSO 2: Creazione Evento (Manuale)**
```
1. Click FAB (+) o data calendario
2. Modal con form: titolo, data/ora inizio, data/ora fine, categoria, importo, note, ricorrenza
3. Click "Salva"
4. Frontend → POST /api/events
5. Backend → valida, salva DB, aggiorna contatore categoria
6. Se utente PRO + Google sync attivo → esporta evento a Google Calendar
7. Modal chiude, lista eventi aggiorna
```

### **FLUSSO 3: Creazione Evento (AI Chat)**
```
1. Click FAB viola (AI Assistant)
2. Modal chat testuale: "Come posso aiutarti?"
3. Utente scrive: "Crea evento dentista domani ore 15"
4. Frontend → POST /api/ai/chat (messaggio + contesto eventi recenti)
5. Backend → chiama Gemini API con function calling
6. Gemini → restituisce JSON: {action: "create_event", data: {...}}
7. Backend → valida, crea evento DB
8. Frontend → mostra conferma in chat + aggiorna lista
```

### **FLUSSO 4: Upload + Analisi Documento**
```
1. Pagina Documenti → Click "Carica documento"
2. Utente seleziona PDF
3. Frontend → valida dimensione (FREE: max 10MB totali, PRO: max 500MB)
4. Frontend → POST /api/documents/upload (multipart/form-data)
5. Backend → salva PDF in /uploads/user_123/
6. Backend → estrae testo con pdftotext
7. Backend → chiama Gemini API per analisi:
   - Tipo documento (fattura, bolletta, ricevuta)
   - Titolo evento suggerito
   - Importo
   - Data scadenza
8. Backend → genera embedding del testo
9. Backend → salva in DB: metadati + embedding
10. Frontend → mostra form pre-compilato per creare evento associato
11. Utente conferma/modifica → salva evento
```

### **FLUSSO 5: Sincronizzazione Google Calendar**
```
1. Impostazioni → Toggle "Sincronizza Google Calendar" (solo PRO)
2. Frontend → GET /api/auth/google/start
3. Backend → redirect a Google OAuth consent screen
4. Utente autorizza accesso calendario
5. Google → callback /api/auth/google/callback?code=...
6. Backend → scambia code per access_token + refresh_token
7. Backend → salva token in tabella oauth_tokens
8. Da ora in poi: ogni evento creato/modificato → push automatico a Google Calendar via API
```

### **FLUSSO 6: Ricerca Semantica Documenti**
```
1. Pagina Documenti → campo ricerca: "bollette luce"
2. Frontend → GET /api/documents/search?q=bollette+luce
3. Backend → genera embedding della query
4. Backend → calcola similarità coseno con embeddings DB
5. Backend → restituisce documenti ordinati per rilevanza
6. Frontend → mostra risultati
```

---

## 🚫 COSA NON FARE

### **Limiti Tecnici**
- ❌ NO import bidirezionale da Google Calendar (solo export)
- ❌ NO audio real-time in FASE 1 (solo chat testuale)
- ❌ NO notifiche push browser (usa Google Calendar notifiche)
- ❌ NO modifica eventi Google Calendar dall'app
- ❌ NO condivisione eventi tra utenti

### **Sicurezza**
- ❌ NO password in chiaro nel DB (sempre bcrypt)
- ❌ NO chiavi API nel frontend (solo backend)
- ❌ NO SQL injection (usa prepared statements)
- ❌ NO upload file eseguibili (solo PDF)
- ❌ NO accesso documenti altri utenti (validazione user_id)

### **Performance**
- ❌ NO caricamento lista completa eventi (pagination 50 per volta)
- ❌ NO analisi AI sincrona (timeout 30 sec) → usa code/async
- ❌ NO embedding ogni richiesta → cache risultati

---

## 🏗️ ARCHITETTURA TECNICA

### **STACK**
```
Frontend: React 19 + TypeScript + Vite
Backend: PHP 8.2 + MySQL 8.0
Hosting: Netsons (shared hosting)
AI: Google Gemini 2.0 Flash
OAuth: Google Calendar API
```

### **STRUTTURA FILE**
```
/public_html/                 (Frontend build + entry point)
  ├── index.html
  ├── assets/                 (JS/CSS compilati)
  └── .htaccess              (redirect API a /api/)

/api/                         (Backend PHP)
  ├── index.php              (Router principale)
  ├── config/
  │   ├── database.php       (Connessione MySQL)
  │   └── gemini.php         (Client Gemini API)
  ├── middleware/
  │   ├── auth.php           (Verifica JWT)
  │   └── rate_limit.php     (Limiti FREE/PRO)
  ├── controllers/
  │   ├── AuthController.php
  │   ├── EventController.php
  │   ├── DocumentController.php
  │   ├── CategoryController.php
  │   └── AIController.php
  └── models/
      ├── User.php
      ├── Event.php
      ├── Document.php
      └── Category.php

/uploads/                     (Documenti utenti)
  └── user_123/
      ├── doc1.pdf
      └── doc2.pdf

/.env                         (SOLO su server, mai in Git)
```

### **FLUSSO RICHIESTA**
```
1. Browser → https://tuodominio.it/api/events
2. .htaccess → redirect interno a /api/index.php
3. Router → identifica route → carica controller
4. Middleware → verifica JWT → estrae user_id
5. Controller → business logic → query DB
6. Response → JSON
```

---

## 🔐 AUTENTICAZIONE

### **JWT Token**
```json
{
  "user_id": 123,
  "email": "user@example.com",
  "plan": "pro",
  "iat": 1234567890,
  "exp": 1234654290
}
```

### **Header Richieste Autenticate**
```
Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
```

### **Durata Sessione**
- Token valido: 7 giorni
- Refresh automatico: ogni richiesta rinnova se <24h scadenza
- Logout: cancella token da localStorage

---

## 📊 LIMITI E QUOTE

### **Rate Limiting**
```
FREE:
- 100 richieste/ora API generiche
- 20 richieste/mese AI

PRO:
- 500 richieste/ora API generiche
- 500 richieste/mese AI
```

### **Storage**
```
FREE: 10 MB totali documenti
PRO: 500 MB totali documenti

Calcolo: SUM(filesize) WHERE user_id = X
```

### **Validazione Limiti**
```php
// Middleware controllo quota AI
if ($user->plan === 'free' && $user->ai_queries_this_month >= 20) {
    return error(403, "Limite query AI raggiunto. Upgrade a PRO.");
}
```

---

## 🔄 SINCRONIZZAZIONE GOOGLE CALENDAR

### **Setup OAuth**
```
1. Google Cloud Console → Crea progetto
2. Abilita Google Calendar API
3. Crea credenziali OAuth 2.0
4. Redirect URI: https://tuodominio.it/api/auth/google/callback
5. Scope richiesti: https://www.googleapis.com/auth/calendar.events
```

### **Export Eventi**
```php
// Quando utente crea/modifica evento
if ($user->google_calendar_connected && $user->plan === 'pro') {
    $googleCalendar->insertEvent([
        'summary' => $event->title,
        'start' => ['dateTime' => $event->start_datetime],
        'end' => ['dateTime' => $event->end_datetime],
        'description' => $event->description,
        'reminders' => $event->reminders
    ]);
}
```

---

## 🤖 INTEGRAZIONE GEMINI AI

### **Function Calling**
```javascript
// Esempio chiamata backend
POST /api/ai/chat
{
  "message": "Crea evento compleanno mamma 25 dicembre ore 18",
  "context": {
    "user_timezone": "Europe/Rome",
    "current_date": "2025-10-19"
  }
}

// Gemini restituisce
{
  "action": "create_event",
  "data": {
    "title": "Compleanno mamma",
    "start_datetime": "2025-12-25T18:00:00",
    "category_id": "c2" // Famiglia
  }
}
```

### **Analisi Documento**
```javascript
POST /api/documents/analyze
Content-Type: multipart/form-data
file: bolletta.pdf

// Gemini restituisce
{
  "document_type": "Bolletta energia elettrica",
  "suggested_title": "Pagamento bolletta luce",
  "amount": 75.50,
  "due_date": "2025-11-15",
  "embedding": [0.123, -0.456, ...] // 768 dimensioni
}
```

---

## 📈 FASE 2 (FUTURO)

### **Audio Real-Time**
- VPS Node.js (€5/mese)
- WebSocket Gemini Live API
- Streaming audio bidirezionale

### **Import Google Calendar**
- Sync bidirezionale eventi
- Gestione conflitti (Google vince)
- Webhook notifiche modifiche

### **Mobile App Flutter**
- Stessa API backend
- Notifiche push native
- Offline-first con sync

---

## 🎨 DESIGN SYSTEM

### **Colori**
```css
--primary: #8B5CF6 (viola)
--accent: #3B82F6 (blu)
--background: #0F172A (dark)
--surface: #1E293B
--text-primary: #F1F5F9
--text-secondary: #94A3B8
--success: #10B981
--warning: #F59E0B
--error: #EF4444
```

### **Categorie Default**
```
Lavoro: 💼 #3B82F6
Famiglia: 👨‍👩‍👧‍👦 #10B981
Personale: 🧘 #8B5CF6
Altro: 📌 #6B7280
```

---

## 📝 NOTE IMPLEMENTAZIONE

### **Ricorrenze (iCalendar RRule)**
```
FREQ=DAILY → ogni giorno
FREQ=WEEKLY;BYDAY=MO,WE,FR → lun/mer/ven
FREQ=MONTHLY;BYMONTHDAY=15 → giorno 15 ogni mese
FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=25 → 25 dicembre
```

### **Gestione Timezone**
- Frontend: lavora sempre in locale (toISOString)
- Backend: salva tutto in UTC
- Display: converte in timezone utente

### **Backup Database**
- Cron giornaliero: mysqldump → storage remoto
- Retention: 30 giorni
- Script: `/scripts/backup_db.sh`

---

**FINE ARCH.MD**
