import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import localization from './pages/i18n/localization.json';

i18n.use(initReactI18next).init({
  resources: {
    localization,
  },
  lng: 'en',
  interpolation: {
    escapeValue: false,
  },
});

export default i18n;
