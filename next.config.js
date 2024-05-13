const LocalizationGenerator = require('./scripts/localizationGenerator');

/** @type {import('next').NextConfig} */

const nextConfig = {
  output: 'export',
  images: {
    unoptimized: true, // Is necessary to prevent an error from Next.js. https://nextjs.org/docs/messages/export-image-api
  },
  reactStrictMode: true,
  swcMinify: true,

  webpack: config => {
    const localizationGenerator = new LocalizationGenerator();
    localizationGenerator.generateLocalizationFile();
    return config;
  },
};

module.exports = nextConfig;
