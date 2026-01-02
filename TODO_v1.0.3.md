# KELION AI - TODO LIST
## Versiune: v1.0.3
## Data: 2026-01-02

---

## ✅ REZOLVATE (Sesiunea anterioară)
- [x] Deploy automat CI/CD pe Railway
- [x] Parola admin schimbată în `Andrada_1968!`
- [x] Text "Admin: admin1234" ascuns din ecranul de Login
- [x] Buton CONTACT pe pagina principală

---

## 🔴 DE VERIFICAT / IMPLEMENTAT

### FUNCȚIONALITĂȚI CRITICE
- [ ] Login cu user `admin` și parola nouă
- [ ] Login cu user `demo` / `demo`
- [ ] Chat AI (OpenAI GPT-4.1-mini) - funcționează?
- [ ] TTS (Text-to-Speech) - funcționează?
- [ ] STT (Speech-to-Text) - funcționează?
- [ ] Hologramul 3D se încarcă corect?
- [ ] Formularul CONTACT trimite emailuri?

### PLĂȚI
- [ ] PayPal - dezactivat (config: enabled=false)
- [ ] Bank Transfer - activat dar fără IBAN/Sort code
- [ ] Subscription workflow - funcționează?

### EMAIL
- [ ] SMTP dezactivat (config: enabled=false)
- [ ] Contact form fără notificare email

### SECURITATE
- [ ] Rate limiting funcționează?
- [ ] CSRF protection activă?
- [ ] Session management OK?

### UI/UX
- [ ] Mobile responsive?
- [ ] Toate paginile se încarcă?
- [ ] Clock și date se actualizează?

---

## 📋 CREDENȚIALE DISPONIBILE

### OpenAI
- API Key: prin env var `OPENAI_API_KEY` (setat în Railway)

### PayPal
- Client ID: prin env var `PAYPAL_CLIENT_ID`
- Secret: prin env var `PAYPAL_CLIENT_SECRET`

### Admin
- Username: `admin`
- Password: `Andrada_1968!`

### Demo
- Username: `demo`
- Password: `demo`

---

## 🔍 PROBLEME IDENTIFICATE DIN CONVERSAȚIE

1. eBay - NU există integrare în KELION (poate era pentru alt proiect)
2. Email/SMTP - dezactivat în config
3. PayPal - dezactivat în config

---

## URMĂTORII PAȘI
1. Testare completă în browser
2. Verificare API-uri
3. Completare credențiale lipsă
4. Activare email dacă e necesar
