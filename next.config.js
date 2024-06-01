const withBundleAnalyzer = require('@next/bundle-analyzer')();
const LocalizationGenerator = require('./scripts/localizationGenerator');

/** @type {import('next').NextConfig} */

const nextConfig = {
  reactStrictMode: true,
  swcMinify: true,

  webpack: config => {
    const localizationGenerator = new LocalizationGenerator();
    localizationGenerator.generateLocalizationFile();
    return config;
  },
};

module.exports = process.env.ANALYZE === 'true' ? withBundleAnalyzer(nextConfig) : nextConfig;
