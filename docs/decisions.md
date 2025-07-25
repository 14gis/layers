# Entscheidungen zur Umsetzung: 14gis, layers & Projektstruktur

## 🧭 1. **Projektstart: `14gis` & `layers`**

**Thema:** Migration von `geochron` von `14code` zu `14gis` sowie Überlegungen zur Vereinheitlichung von Projektstruktur und Namensgebung.

**Fazit:**  
- `14gis` fungiert als neue Organisation für GIS-basierte Tools  
- `layers` ersetzt `core` als zentrale Codebasis / Skeleton  
- Etablierung einer klaren, modularen Architektur

---

## 🌐 2. **Domainstrategie & Infrastruktur**

**Thema:** Registrierung und Nutzung der Domain `14g.is`, DNS-Setup, Nameserver-Delegation zu Hetzner.

**Fazit:**  
- **`14g.is`** dient als charakterstarke Hauptdomain  
- **`layers.14g.is`** fungiert als zentrale API-Domain  
- **`14gis.org`** wird als öffentlich sichtbarer Proxy/Alias vorgesehen  
- Erfolgreiche DNS-Umstellung zu Hetzner

---

## 📁 3. **Ordnerstruktur & Konzept-Dateien**

**Thema:** Strukturierung von Konzepten, Modellen und Layerrollen im Repository mithilfe textbasierter Formate wie YAML.

**Fazit:**  
- `schema/` enthält technische Modelle (z. B. Layerstruktur, Rollen)  
- `data/` enthält projekt- oder domainspezifische Inhalte  
- `docs/` dient der Dokumentation, Erläuterung und Vision  
- Einheitliche Struktur für Codebasis (`layers`) und Projekte wie `energy`

---

## ⚙️ 4. **Layerrollen, Projektkontext & Zugriff**

**Thema:** Mehrstufige Projektlogik mit Systemprojekten (z. B. `energy`) und Datenprojekten (z. B. `belzig`). Diskussion zu exklusiven und wiederverwendbaren Layern sowie URL-Logik.

**Fazit:**  
- `project=...` ist Pflichtparameter (Systemkontext)  
- `data=...` ist optional, aber nur gültig im passenden Projekt  
- Layerzugriff ist kontextabhängig und kann über Token abgesichert werden  
- Beispielstruktur: `?project=energy&data=belzig&token=abc123`

---

## 🔒 5. **Absicherung durch Tokens**

**Thema:** Schutz sensibler Layer und Datenprojekte durch Zugriffstokens.

**Fazit:**  
- Tokens können statisch, signiert oder zeitlich beschränkt sein  
- Optional einsetzbar pro Datenprojekt  
- Prüfung erfolgt serverseitig auf Gültigkeit und Kontextpassung

---

## 🧱 6. **Backend-Design & API-Zentrale**

**Thema:** Architekturentscheidung für zentrale Verarbeitung aller Anfragen an `layers.14g.is`.

**Fazit:**  
- Nur ein Backend für System- und Datenprojekte  
- Routing per URL oder Parameter, z. B. `/api/energy/layer/guek250`  
- `RequestContext`-Klasse extrahiert und prüft Projektkontext  
- `layers` ist Composer-kompatibel und kann als Library eingebunden werden

---

## 🧠 7. **Zukunft: öffentliche Anbieter & 14gis.org**

**Thema:** Nutzung von `14gis.org` als Plattform für externe Partner.

**Fazit:**  
- `14gis.org` fungiert als Proxy/Alias mit identischer Struktur wie `layers.14g.is`  
- Ziel: Ansprache externer Anbieter, Verwaltung öffentlicher Layer, Sichtbarkeit  
- Strategische Trennung zwischen interner API und externer Plattform
