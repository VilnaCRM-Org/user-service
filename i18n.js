import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import resources from './pages/i18n/localization.json';

i18n.use(initReactI18next).init({
  lng: 'en',
  resources,
  fallbackLng: process.env.NEXT_PUBLIC_FALLBACK_LANGUAGE,
  interpolation: {
    escapeValue: false,
  },
});

export default i18n;
