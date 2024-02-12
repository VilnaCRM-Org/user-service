const LocalizationGenerator = require('./scripts/localizationGenerator');

const withBundleAnalyzer = require('@next/bundle-analyzer')({
  enabled: process.env.ANALYZE === 'true',
});
/** @type {import('next').NextConfig} */

const nextConfig = {
  output: 'export',
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

module.exports = withBundleAnalyzer(nextConfig);
