/**
 * Admin rich-text editor bundle (Quill 2, bundled locally — no CDN).
 *
 * Loaded on demand by <x-admin.form.rich-text/> via @vite. Exposes Quill
 * globally and announces readiness so Alpine components can initialize
 * editors regardless of script ordering.
 */
import Quill from 'quill';
import 'quill/dist/quill.snow.css';

window.Quill = Quill;
window.dispatchEvent(new CustomEvent('rich-text:ready'));
