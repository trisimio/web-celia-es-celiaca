# Celia es Celiaca — Web Oficial

Web de la banda madrileña de pop-rock alternativo/punk **Celia es Celiaca**.

## Stack

- HTML + CSS + JS vanilla
- GSAP + ScrollTrigger (CDN)
- Deploy: Netlify

## Desarrollo local

```bash
npx serve .
```

## Deploy

Push a `main` en el repo de GitHub conectado a Netlify.

## Reemplazar placeholders

Los placeholders de imagenes (gradientes CSS) se reemplazan con fotos reales:
- Album art: `.album-hero__placeholder`, `.album-card__placeholder`
- Fotos banda: `.photo-item__placeholder`
- Video thumbnails: `.video-thumb__placeholder` + actualizar `data-youtube` con IDs reales

## Estructura

```
├── index.html          ← Single-page (8 secciones)
├── css/style.css       ← Design system completo
├── js/app.js           ← Interacciones y animaciones
├── netlify.toml        ← Config deploy + headers
└── robots.txt
```
