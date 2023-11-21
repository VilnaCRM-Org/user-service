import i18n from 'i18next';
import { initReactI18next } from 'react-i18next';
import LanguageDetector from 'i18next-browser-languagedetector';

import { landingLocalization } from '@/features/landing';

const resources = {
  en: {
    landing: landingLocalization.en,
  },
  uk: {
    landing: landingLocalization.uk,
  },
};

i18n
  .use(initReactI18next)
  .use(LanguageDetector)
  .init({
    resources,
    fallbackLng: 'en',
    interpolation: {
      escapeValue: false,
    },
  });

export default i18n;
