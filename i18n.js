import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import localizationJSON from './pages/i18n/localization.json';

const { localization } = localizationJSON;

i18n.use(initReactI18next).init({
  lng: 'uk',
  resources: {
    en: {
      translation: localization.translation.en,
    },
    uk: {
      translation: localization.translation.uk,
    },
  },
  fallbackLng: process.env.FALLBACK_LANGUAGE || 'uk',
  interpolation: {
    escapeValue: false,
  },
});

export default i18n;
