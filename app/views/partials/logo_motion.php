<?php
/**
 * The MANBAR logo as separate drawable layers, for the landing intro (public/assets/js/cinema.js drives it):
 * outline trace → M / mic / stage strokes draw → leaves pop → gradient fill + glass sheen → sound waves + orbit ring.
 * Geometry matches public/assets/img/logo.svg (viewBox 45 43 1162 1162), padded for the orbit.
 */
?>
<svg class="lm" viewBox="-175 -177 1602 1602" aria-hidden="true">
  <defs>
    <linearGradient id="lmBg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#46f497"/><stop offset=".45" stop-color="#14bb7a"/><stop offset="1" stop-color="#077b69"/></linearGradient>
    <radialGradient id="lmGlow" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#9ff5d6" stop-opacity=".3"/><stop offset="1" stop-color="#c8fbe6" stop-opacity=".22"/></radialGradient>
    <linearGradient id="lmLeafL" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#e2f98a"/><stop offset="1" stop-color="#f1fbb8"/></linearGradient>
    <linearGradient id="lmLeafR" x1="1" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#9cf00c"/><stop offset="1" stop-color="#ecfcc6"/></linearGradient>
    <linearGradient id="lmSheen" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".5" stop-color="#fff" stop-opacity=".55"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
    <linearGradient id="lmOrbit" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset=".8" stop-color="#fff"/><stop offset="1" stop-color="#e9ffb0"/></linearGradient>
    <clipPath id="lmClip"><rect x="48" y="46" width="1156" height="1156" rx="235"/></clipPath>
  </defs>
  <g class="lm-fill">
    <rect x="48" y="46" width="1156" height="1156" rx="235" fill="url(#lmBg)"/>
    <circle cx="905" cy="250" r="345" fill="url(#lmGlow)" clip-path="url(#lmClip)"/>
  </g>
  <rect class="lm-tile" x="48" y="46" width="1156" height="1156" rx="235"/>
  <rect class="lm-trace" x="48" y="46" width="1156" height="1156" rx="235" pathLength="100"/>
  <g clip-path="url(#lmClip)"><rect class="lm-sheen" x="-400" y="-200" width="260" height="1700" fill="url(#lmSheen)" transform="rotate(18 626 624)"/></g>
  <g class="lm-art">
    <path class="lm-draw d1" pathLength="100" d="M303 925V452c0-60 38-104 91-104 31 0 55 14 75 39L626 556 783 387c20-25 44-39 75-39 53 0 91 44 91 104v473" stroke-width="90"/>
    <path class="lm-draw d2" pathLength="100" d="M194 1031H1058" stroke-width="82"/>
    <rect class="lm-cap" x="572" y="710" width="108" height="168" rx="54"/>
    <path class="lm-draw d3" pathLength="100" d="M540 808v8a86 86 0 0 0 172 0v-8" stroke-width="26"/>
    <path class="lm-draw d4" pathLength="100" d="M626 904v42M579 948h94" stroke-width="26"/>
  </g>
  <g class="lm-leaves">
    <path class="lf-l" d="M620 358C556 350 500 300 496 219c74 9 120 60 124 139z" fill="url(#lmLeafL)"/>
    <path class="lf-r" d="M620 358c-4-98 70-176 182-187 2 101-72 178-182 187z" fill="url(#lmLeafR)"/>
  </g>
  <g class="lm-waves">
    <path class="w1" d="M498 742q-34 52 0 104"/><path class="w1" d="M754 742q34 52 0 104"/>
    <path class="w2" d="M452 712q-60 82 0 164"/><path class="w2" d="M800 712q60 82 0 164"/>
  </g>
  <ellipse class="lm-orbit" cx="626" cy="640" rx="760" ry="200" pathLength="100" transform="rotate(-11 626 640)"/>
  <g class="lm-spark"><circle cx="-60" cy="760" r="9"/><circle cx="1330" cy="430" r="7"/><circle cx="1180" cy="880" r="10"/><circle cx="40" cy="440" r="6"/><circle cx="1260" cy="700" r="5"/><circle cx="120" cy="900" r="6"/></g>
</svg>
