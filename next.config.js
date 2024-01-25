const LocalizationGenerator = require('./scripts/localizationGenerator');

/** @type {import('next').NextConfig} */
const nextConfig = {
  i18n: {
    locales: ['en', 'es'],
    defaultLocale: 'en',
  },
  reactStrictMode: true,
  swcMinify: true,
  images: {
    unoptimized: true,
  },
  webpack: config => {
    const localizationGenerator = new LocalizationGenerator();
    localizationGenerator.generateLocalizationFile();

    return config;
  },
};

module.exports = nextConfig;
